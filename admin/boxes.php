<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Token CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];

// Atualização do box
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        empty($_SESSION["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        header("Location: boxes.php?erro=" . urlencode("Solicitação inválida. Atualize a página."));
        exit;
    }

    $id_box = (int) ($_POST["id_box"] ?? 0);
    $status = $_POST["status"] ?? "";
    $ativo = $_POST["ativo"] ?? "";

    try {

        if ($id_box <= 0) {
            throw new Exception("Box inválido.");
        }

        if (!in_array($status, ["LIVRE", "MANUTENCAO", "INTERDITADO"], true)) {
            throw new Exception("Status inválido.");
        }

        if (!in_array($ativo, ["0", "1"], true)) {
            throw new Exception("Situação de ativação inválida.");
        }

        $pdo->beginTransaction();

        // Bloqueia o box durante a atualização
        $stmt = $pdo->prepare("
            SELECT id_box
            FROM boxes
            WHERE id_box = ?
            FOR UPDATE
        ");
        $stmt->execute([$id_box]);

        if (!$stmt->fetch()) {
            throw new Exception("Box não encontrado.");
        }

        // Verifica ocupação ativa
        $stmt = $pdo->prepare("
            SELECT id_ocupacao
            FROM ocupacoes
            WHERE id_box = ?
              AND status = 'ATIVA'
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$id_box]);

        if ($stmt->fetch()) {
            throw new Exception("Não é possível alterar um box enquanto ele estiver ocupado.");
        }

        // Atualiza status e ativação
        $stmt = $pdo->prepare("
            UPDATE boxes
            SET status = ?,
                ativo = ?
            WHERE id_box = ?
        ");

        $stmt->execute([$status, (int) $ativo, $id_box]);

        $pdo->commit();

        header("Location: boxes.php?sucesso=1");
        exit;
    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        header("Location: boxes.php?erro=" . urlencode($e->getMessage()));
        exit;
    }
}

// Busca os boxes e seus ônibus associados
$sql = "
    SELECT
        b.id_box,
        b.nome,
        b.status,
        b.ativo,
        o.numero_frota,
        o.placa,
        o.modelo,
        o.status AS status_onibus,
        (
            SELECT COUNT(*)
            FROM ocupacoes oc
            WHERE oc.id_box = b.id_box
              AND oc.status = 'ATIVA'
        ) AS ocupacao_ativa
    FROM boxes b
    INNER JOIN onibus o ON o.id_onibus = b.id_onibus
    ORDER BY b.id_box
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$boxes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Boxes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">

    <header class="bg-slate-900 text-white px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold">Garagem IoT</h1>
                <p class="text-sm text-slate-400">Gerenciamento de boxes</p>
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
                Gerenciar Boxes
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Consulte e atualize a situação dos boxes da garagem.
            </p>
        </div>

        <?php if (isset($_GET["sucesso"])): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded-lg text-sm mb-5">
                Box atualizado com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["erro"])): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded-lg text-sm mb-5">
                <?= htmlspecialchars($_GET["erro"]) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <?php if (empty($boxes)): ?>

                <div class="col-span-full bg-white rounded-xl p-8 text-center text-gray-500">
                    Nenhum box cadastrado.
                </div>

            <?php else: ?>

                <?php foreach ($boxes as $box): ?>

                    <?php
                    $ocupado = (int) $box["ocupacao_ativa"] > 0;

                    if (!(int) $box["ativo"]) {
                        $status_exibido = "INATIVO";
                        $cor = "bg-gray-200 text-gray-700";
                    } else {
                        $status_exibido = $box["status"];

                        $cores = [
                            "LIVRE" => "bg-green-100 text-green-700",
                            "OCUPADO" => "bg-red-100 text-red-700",
                            "MANUTENCAO" => "bg-yellow-100 text-yellow-700",
                            "INTERDITADO" => "bg-gray-200 text-gray-700"
                        ];

                        $cor = $cores[$status_exibido] ?? "bg-gray-100 text-gray-700";
                    }
                    ?>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">

                        <div class="flex justify-between items-center mb-4">

                            <h3 class="text-lg font-bold text-gray-800">
                                <?= htmlspecialchars($box["nome"]) ?>
                            </h3>

                            <span class="text-xs font-semibold px-3 py-1 rounded-full <?= $cor ?>">
                                <?= htmlspecialchars($status_exibido) ?>
                            </span>

                        </div>

                        <div class="border-b border-gray-100 pb-4 mb-4">

                            <p class="text-xs text-gray-500">
                                Ônibus associado
                            </p>

                            <p class="text-xl font-bold text-gray-800">
                                Frota <?= htmlspecialchars($box["numero_frota"]) ?>
                            </p>

                            <p class="text-sm text-gray-500">
                                Modelo: <?= htmlspecialchars($box["modelo"] ?? "-") ?>
                            </p>

                            <p class="text-sm text-gray-500">
                                Placa: <?= htmlspecialchars($box["placa"] ?? "Não cadastrada") ?>
                            </p>

                            <p class="text-xs text-gray-400 mt-2">
                                Situação do ônibus: <?= htmlspecialchars($box["status_onibus"]) ?>
                            </p>

                        </div>

                        <?php if ($ocupado): ?>

                            <div class="bg-red-50 text-red-700 rounded-lg p-3 text-sm">
                                Este box possui uma ocupação ativa. As alterações estão bloqueadas.
                            </div>

                        <?php else: ?>

                            <form method="POST" action="boxes.php" class="space-y-4">

                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($csrf_token) ?>">

                                <input type="hidden" name="id_box"
                                    value="<?= (int) $box["id_box"] ?>">

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Status do box
                                    </label>

                                    <select name="status"
                                        class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500">

                                        <option value="LIVRE" <?= $box["status"] === "LIVRE" ? "selected" : "" ?>>
                                            Livre
                                        </option>

                                        <option value="MANUTENCAO" <?= $box["status"] === "MANUTENCAO" ? "selected" : "" ?>>
                                            Manutenção
                                        </option>

                                        <option value="INTERDITADO" <?= $box["status"] === "INTERDITADO" ? "selected" : "" ?>>
                                            Interditado
                                        </option>

                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Ativação do box
                                    </label>

                                    <select name="ativo"
                                        class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500">

                                        <option value="1" <?= (int) $box["ativo"] === 1 ? "selected" : "" ?>>
                                            Ativo
                                        </option>

                                        <option value="0" <?= (int) $box["ativo"] === 0 ? "selected" : "" ?>>
                                            Inativo
                                        </option>

                                    </select>
                                </div>

                                <button type="submit"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition">
                                    Salvar alterações
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

        <footer class="text-center text-xs text-gray-400 mt-8">
            Garagem IoT - Sistema de gerenciamento de acesso
        </footer>

    </main>

</body>

</html>