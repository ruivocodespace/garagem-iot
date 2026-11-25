<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Filtros
$motorista = trim($_GET["motorista"] ?? "");
$box = $_GET["box"] ?? "";
$resultado = $_GET["resultado"] ?? "";
$data_inicio = $_GET["data_inicio"] ?? "";
$data_fim = $_GET["data_fim"] ?? "";

// Paginação
$pagina = max(1, (int) ($_GET["pagina"] ?? 1));
$limite = 15;
$offset = ($pagina - 1) * $limite;

// Montagem dos filtros SQL
$where = [];
$params = [];

if ($motorista !== "") {
    $where[] = "(m.nome LIKE ? OR a.uid_informado LIKE ?)";
    $params[] = "%$motorista%";
    $params[] = "%$motorista%";
}

if ($box !== "") {
    $where[] = "a.id_box = ?";
    $params[] = (int) $box;
}

if (in_array($resultado, ["AUTORIZADO", "NEGADO"], true)) {
    $where[] = "a.resultado = ?";
    $params[] = $resultado;
}

if ($data_inicio !== "") {
    $where[] = "DATE(a.data_hora) >= ?";
    $params[] = $data_inicio;
}

if ($data_fim !== "") {
    $where[] = "DATE(a.data_hora) <= ?";
    $params[] = $data_fim;
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Total de registros
$sql_total = "
    SELECT COUNT(*)
    FROM acessos a
    LEFT JOIN motoristas m ON m.id_motorista = a.id_motorista
    $where_sql
";

$stmt = $pdo->prepare($sql_total);
$stmt->execute($params);
$total_registros = (int) $stmt->fetchColumn();

$total_paginas = max(1, (int) ceil($total_registros / $limite));

// Histórico
$sql = "
    SELECT
        a.id_acesso,
        a.uid_informado,
        a.operacao,
        a.resultado,
        a.motivo,
        a.data_hora,
        m.nome AS motorista,
        b.nome AS box,
        o.numero_frota
    FROM acessos a
    LEFT JOIN motoristas m ON m.id_motorista = a.id_motorista
    LEFT JOIN boxes b ON b.id_box = a.id_box
    LEFT JOIN onibus o ON o.id_onibus = b.id_onibus
    $where_sql
    ORDER BY a.data_hora DESC, a.id_acesso DESC
    LIMIT $limite OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$acessos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Boxes para o filtro
$stmt = $pdo->query("
    SELECT id_box, nome
    FROM boxes
    ORDER BY nome
");
$boxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Indicadores
$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(resultado = 'AUTORIZADO') AS autorizados,
        SUM(resultado = 'NEGADO') AS negados
    FROM acessos
");
$indicadores = $stmt->fetch(PDO::FETCH_ASSOC);

// Preserva os filtros na paginação
$filtros_url = $_GET;
unset($filtros_url["pagina"]);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Acessos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">

    <header class="bg-slate-900 text-white px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold">Garagem IoT</h1>
                <p class="text-sm text-slate-400">Histórico de acessos</p>
            </div>

            <a href="dashboard.php"
                class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg text-sm">
                Voltar ao dashboard
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-6">

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">
                Histórico de Acessos
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Consulte as tentativas de entrada e saída da garagem.
            </p>
        </div>

        <!-- Indicadores -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

            <div class="bg-white rounded-xl p-5 shadow-sm">
                <p class="text-sm text-gray-500">Total de registros</p>
                <p class="text-2xl font-bold text-gray-800">
                    <?= (int) $indicadores["total"] ?>
                </p>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm">
                <p class="text-sm text-gray-500">Acessos autorizados</p>
                <p class="text-2xl font-bold text-green-600">
                    <?= (int) $indicadores["autorizados"] ?>
                </p>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm">
                <p class="text-sm text-gray-500">Acessos negados</p>
                <p class="text-2xl font-bold text-red-600">
                    <?= (int) $indicadores["negados"] ?>
                </p>
            </div>

        </div>

        <!-- Filtros -->
        <form method="GET" class="bg-white rounded-xl shadow-sm p-5 mb-6">

            <h3 class="font-semibold text-gray-800 mb-4">
                Filtrar histórico
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

                <div>
                    <label class="block text-sm text-gray-600 mb-1">
                        Motorista ou UID
                    </label>
                    <input type="text" name="motorista"
                        value="<?= htmlspecialchars($motorista) ?>"
                        placeholder="Nome ou UID RFID"
                        class="w-full border rounded-lg p-2 text-sm">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">
                        Box
                    </label>
                    <select name="box" class="w-full border rounded-lg p-2 text-sm">
                        <option value="">Todos</option>

                        <?php foreach ($boxes as $item): ?>
                            <option value="<?= $item["id_box"] ?>"
                                <?= (string) $box === (string) $item["id_box"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($item["nome"]) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">
                        Resultado
                    </label>
                    <select name="resultado" class="w-full border rounded-lg p-2 text-sm">
                        <option value="">Todos</option>
                        <option value="AUTORIZADO" <?= $resultado === "AUTORIZADO" ? "selected" : "" ?>>
                            Autorizado
                        </option>
                        <option value="NEGADO" <?= $resultado === "NEGADO" ? "selected" : "" ?>>
                            Negado
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">
                        Data inicial
                    </label>
                    <input type="date" name="data_inicio"
                        value="<?= htmlspecialchars($data_inicio) ?>"
                        class="w-full border rounded-lg p-2 text-sm">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">
                        Data final
                    </label>
                    <input type="date" name="data_fim"
                        value="<?= htmlspecialchars($data_fim) ?>"
                        class="w-full border rounded-lg p-2 text-sm">
                </div>

            </div>

            <div class="flex gap-2 mt-4">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm">
                    Filtrar
                </button>

                <a href="acessos.php"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded-lg text-sm">
                    Limpar
                </a>
            </div>

        </form>

        <!-- Tabela -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">

            <div class="p-5 border-b">
                <h3 class="font-semibold text-gray-800">
                    Registros encontrados: <?= $total_registros ?>
                </h3>
            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-sm text-left">

                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-5 py-3">Data/Hora</th>
                            <th class="px-5 py-3">Motorista</th>
                            <th class="px-5 py-3">UID RFID</th>
                            <th class="px-5 py-3">Box</th>
                            <th class="px-5 py-3">Ônibus</th>
                            <th class="px-5 py-3">Operação</th>
                            <th class="px-5 py-3">Resultado</th>
                            <th class="px-5 py-3">Motivo</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">

                        <?php if (empty($acessos)): ?>

                            <tr>
                                <td colspan="8" class="px-5 py-10 text-center text-gray-500">
                                    Nenhum registro encontrado.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($acessos as $acesso): ?>

                                <tr class="hover:bg-gray-50">

                                    <td class="px-5 py-3 whitespace-nowrap">
                                        <?= date("d/m/Y H:i:s", strtotime($acesso["data_hora"])) ?>
                                    </td>

                                    <td class="px-5 py-3">
                                        <?= htmlspecialchars($acesso["motorista"] ?? "Não identificado") ?>
                                    </td>

                                    <td class="px-5 py-3 font-mono">
                                        <?= htmlspecialchars($acesso["uid_informado"] ?? "-") ?>
                                    </td>

                                    <td class="px-5 py-3">
                                        <?= htmlspecialchars($acesso["box"] ?? "Não identificado") ?>
                                    </td>

                                    <td class="px-5 py-3">
                                        <?= htmlspecialchars($acesso["numero_frota"] ?? "-") ?>
                                    </td>

                                    <td class="px-5 py-3">
                                        <?= htmlspecialchars($acesso["operacao"]) ?>
                                    </td>

                                    <td class="px-5 py-3">
                                        <?php if ($acesso["resultado"] === "AUTORIZADO"): ?>
                                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">
                                                AUTORIZADO
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-semibold">
                                                NEGADO
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-5 py-3">
                                        <?= htmlspecialchars($acesso["motivo"] ?? "-") ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>

                <div class="flex justify-between items-center p-5 border-t">

                    <span class="text-sm text-gray-500">
                        Página <?= $pagina ?> de <?= $total_paginas ?>
                    </span>

                    <div class="flex gap-2">

                        <?php if ($pagina > 1): ?>
                            <a href="?<?= http_build_query(array_merge($filtros_url, ["pagina" => $pagina - 1])) ?>"
                                class="px-4 py-2 bg-gray-200 rounded-lg text-sm">
                                Anterior
                            </a>
                        <?php endif; ?>

                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?<?= http_build_query(array_merge($filtros_url, ["pagina" => $pagina + 1])) ?>"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">
                                Próxima
                            </a>
                        <?php endif; ?>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </main>

</body>

</html>