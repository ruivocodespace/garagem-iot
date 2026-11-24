<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

// Indicadores
$livres = $pdo->query("SELECT COUNT(*) FROM boxes WHERE status = 'LIVRE' AND ativo = 1")->fetchColumn();
$ocupados = $pdo->query("SELECT COUNT(*) FROM boxes WHERE status = 'OCUPADO' AND ativo = 1")->fetchColumn();
$indisponiveis = $pdo->query("SELECT COUNT(*) FROM boxes WHERE status IN ('MANUTENCAO', 'INTERDITADO') AND ativo = 1")->fetchColumn();
$motoristas = $pdo->query("SELECT COUNT(*) FROM motoristas WHERE status = 1")->fetchColumn();

// Boxes e ônibus
$sql_boxes = "SELECT
                b.nome,
                b.status,
                b.ativo,
                o.numero_frota,
                o.placa,
                o.status AS status_onibus
              FROM boxes b
              INNER JOIN onibus o ON b.id_onibus = o.id_onibus
              ORDER BY b.id_box";
$boxes = $pdo->query($sql_boxes)->fetchAll(PDO::FETCH_ASSOC);

// Últimos acessos
$sql_acessos = "SELECT
                    a.uid_informado,
                    a.operacao,
                    a.resultado,
                    a.motivo,
                    a.data_hora,
                    m.nome AS motorista,
                    b.nome AS box
                FROM acessos a
                LEFT JOIN motoristas m ON a.id_motorista = m.id_motorista
                LEFT JOIN boxes b ON a.id_box = b.id_box
                ORDER BY a.data_hora DESC
                LIMIT 8";

$acessos = $pdo->query($sql_acessos)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Garagem IoT</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <header class="bg-slate-900 text-white px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold">Garagem IoT</h1>
                <p class="text-sm text-slate-400">Sistema de controle de acesso</p>
            </div>
            <span class="text-sm bg-slate-700 px-3 py-2 rounded-lg">
                Painel administrativo
            </span>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-6">

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
            <p class="text-gray-500 text-sm">Visão geral da garagem</p>
        </div>

        <!-- Indicadores -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-green-500">
                <p class="text-gray-500 text-sm">Boxes livres</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2">
                    <?= $livres ?>
                </h3>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-red-500">
                <p class="text-gray-500 text-sm">Boxes ocupados</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2">
                    <?= $ocupados ?>
                </h3>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-yellow-500">
                <p class="text-gray-500 text-sm">Indisponíveis</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2">
                    <?= $indisponiveis ?>
                </h3>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-blue-500">
                <p class="text-gray-500 text-sm">Motoristas ativos</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2">
                    <?= $motoristas ?>
                </h3>
            </div>

        </section>

        <!-- Situação dos boxes -->
        <section class="bg-white rounded-xl shadow-sm p-5 mb-8">

            <div class="mb-5">
                <h3 class="text-lg font-bold text-gray-800">Situação dos boxes</h3>
                <p class="text-sm text-gray-500">Ocupação e ônibus associados</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <?php foreach ($boxes as $box): ?>

                    <?php
                    $cores = [
                        'LIVRE' => 'bg-green-100 text-green-700',
                        'OCUPADO' => 'bg-red-100 text-red-700',
                        'MANUTENCAO' => 'bg-yellow-100 text-yellow-700',
                        'INTERDITADO' => 'bg-gray-200 text-gray-700'
                    ];

                    $status = !$box['ativo'] ? 'INATIVO' : $box['status'];

                    $cor = $cores[$status] ?? 'bg-gray-100 text-gray-700';
                    ?>

                    <div class="border border-gray-200 rounded-lg p-4">

                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-bold text-gray-800">
                                <?= htmlspecialchars($box['nome']) ?>
                            </h4>

                            <span class="text-xs font-semibold px-2 py-1 rounded-full <?= $cor ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </div>

                        <p class="text-sm text-gray-500">Ônibus associado</p>

                        <p class="text-xl font-bold text-gray-800">
                            <?= htmlspecialchars($box['numero_frota']) ?>
                        </p>

                        <p class="text-sm text-gray-500 mt-1">
                            Placa:
                            <?= htmlspecialchars($box['placa'] ?? 'Não cadastrada') ?>
                        </p>

                        <p class="text-xs text-gray-400 mt-3">
                            Ônibus: <?= htmlspecialchars($box['status_onibus']) ?>
                        </p>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>

        <!-- Histórico de acessos -->
        <section class="bg-white rounded-xl shadow-sm p-5">

            <div class="mb-5">
                <h3 class="text-lg font-bold text-gray-800">Últimos acessos</h3>
                <p class="text-sm text-gray-500">Histórico das últimas movimentações</p>
            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-sm text-left">

                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="p-3">Motorista</th>
                            <th class="p-3">Box</th>
                            <th class="p-3">Operação</th>
                            <th class="p-3">Resultado</th>
                            <th class="p-3">Data e hora</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        <?php if (count($acessos) > 0): ?>

                            <?php foreach ($acessos as $acesso): ?>

                                <tr class="hover:bg-gray-50">

                                    <td class="p-3 font-medium text-gray-800">
                                        <?= htmlspecialchars($acesso['motorista'] ?? 'Não identificado') ?>
                                    </td>

                                    <td class="p-3 text-gray-600">
                                        <?= htmlspecialchars($acesso['box'] ?? '-') ?>
                                    </td>

                                    <td class="p-3 text-gray-600">
                                        <?= htmlspecialchars($acesso['operacao']) ?>
                                    </td>

                                    <td class="p-3">
                                        <?php if ($acesso['resultado'] === 'AUTORIZADO'): ?>
                                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-semibold">
                                                AUTORIZADO
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-semibold">
                                                NEGADO
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($acesso['motivo']): ?>
                                            <p class="text-xs text-gray-400 mt-1">
                                                <?= htmlspecialchars($acesso['motivo']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-3 text-gray-500 whitespace-nowrap">
                                        <?= date('d/m/Y H:i', strtotime($acesso['data_hora'])) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="p-6 text-center text-gray-400">
                                    Nenhum acesso registrado.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <footer class="text-center text-xs text-gray-400 mt-8">
            Garagem IoT - Sistema de gerenciamento de acesso
        </footer>

    </main>

</body>

</html>