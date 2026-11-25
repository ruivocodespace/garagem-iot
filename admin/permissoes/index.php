<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../../auth/login.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];

// Busca motoristas, boxes e permissões
$sql = "
    SELECT
        m.id_motorista,
        m.nome AS motorista,
        m.matricula,
        m.status AS status_motorista,
        b.id_box,
        b.nome AS box,
        b.ativo AS status_box,
        o.numero_frota,
        o.status AS status_onibus,
        p.id_permissao,
        p.status AS permissao_ativa
    FROM motoristas m
    CROSS JOIN boxes b
    LEFT JOIN onibus o
        ON o.id_onibus = b.id_onibus
    LEFT JOIN permissoes p
        ON p.id_motorista = m.id_motorista
        AND p.id_box = b.id_box
    ORDER BY m.nome, b.nome
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiza as permissões por motorista
$motoristas = [];

foreach ($dados as $linha) {
    $id = $linha["id_motorista"];

    if (!isset($motoristas[$id])) {
        $motoristas[$id] = [
            "nome" => $linha["motorista"],
            "matricula" => $linha["matricula"],
            "status" => $linha["status_motorista"],
            "boxes" => []
        ];
    }

    $motoristas[$id]["boxes"][] = $linha;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Permissões</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">



    <main class="p-6">

        <div class="max-w-7xl mx-auto">

            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    Gerenciar Permissões
                </h1>

                <p class="text-gray-500 text-sm mt-1">
                    Controle os boxes que cada motorista pode acessar.
                </p>
            </div>

            <?php if (isset($_GET["sucesso"])): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded-lg text-sm mb-5">
                    Permissão atualizada com sucesso!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["erro"])): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded-lg text-sm mb-5">
                    <?= htmlspecialchars($_GET["erro"]) ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">

                <div class="overflow-x-auto">

                    <table class="w-full text-sm text-left">

                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-4">Motorista</th>
                                <th class="px-6 py-4">Matrícula</th>
                                <th class="px-6 py-4">Box</th>
                                <th class="px-6 py-4">Ônibus</th>
                                <th class="px-6 py-4">Permissão</th>
                                <th class="px-6 py-4 text-center">Ação</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">

                            <?php if (empty($motoristas)): ?>

                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        Nenhum motorista ou box cadastrado.
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($motoristas as $id_motorista => $motorista): ?>

                                    <?php foreach ($motorista["boxes"] as $box): ?>

                                        <?php
                                        $status_motorista = (int) $motorista["status"];
                                        $status_box = (int) $box["status_box"];
                                        $status_permissao = $box["permissao_ativa"];

                                        if ($status_permissao === null) {
                                            $situacao = "Não concedida";
                                            $cor = "bg-gray-100 text-gray-600";
                                        } elseif ((int) $status_permissao === 1) {
                                            $situacao = "Ativa";
                                            $cor = "bg-green-100 text-green-700";
                                        } else {
                                            $situacao = "Revogada";
                                            $cor = "bg-red-100 text-red-700";
                                        }

                                        $pode_alterar = (
                                            $status_motorista === 1 &&
                                            $status_box === 1 &&
                                            $box["status_onibus"] === "ATIVO"
                                        );
                                        ?>

                                        <tr class="hover:bg-gray-50">

                                            <td class="px-6 py-4">
                                                <div class="font-medium text-gray-800">
                                                    <?= htmlspecialchars($motorista["nome"]) ?>
                                                </div>

                                                <?php if ($status_motorista !== 1): ?>
                                                    <span class="text-xs text-red-600">
                                                        Motorista inativo
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="px-6 py-4 text-gray-600">
                                                <?= htmlspecialchars($motorista["matricula"] ?? "-") ?>
                                            </td>

                                            <td class="px-6 py-4 text-gray-700">
                                                <?= htmlspecialchars($box["box"]) ?>

                                                <?php if ($status_box !== 1): ?>
                                                    <span class="block text-xs text-red-600">
                                                        Box inativo
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="px-6 py-4 text-gray-700">
                                                <?= htmlspecialchars($box["numero_frota"] ?? "-") ?>

                                                <?php if ($box["status_onibus"] !== "ATIVO"): ?>
                                                    <span class="block text-xs text-red-600">
                                                        Ônibus indisponível
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="px-6 py-4">
                                                <span class="<?= $cor ?> px-3 py-1 rounded-full text-xs font-medium">
                                                    <?= $situacao ?>
                                                </span>
                                            </td>

                                            <td class="px-6 py-4 text-center">

                                                <?php if ($pode_alterar): ?>

                                                    <?php if ((int) $status_permissao === 1): ?>

                                                        <form action="alterar.php" method="POST">

                                                            <input type="hidden" name="id_motorista" value="<?= $id_motorista ?>">
                                                            <input type="hidden" name="id_box" value="<?= $box["id_box"] ?>">
                                                            <input type="hidden" name="acao" value="revogar">

                                                            <button type="submit"
                                                                class="text-red-600 hover:text-red-800 font-medium">
                                                                Revogar
                                                            </button>

                                                        </form>

                                                    <?php else: ?>

                                                        <form action="alterar.php" method="POST">

                                                            <input type="hidden" name="id_motorista" value="<?= $id_motorista ?>">
                                                            <input type="hidden" name="id_box" value="<?= $box["id_box"] ?>">
                                                            <input type="hidden" name="acao" value="conceder">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                                                            <button type="submit" class="text-green-600 hover:text-green-800 font-medium">
                                                                Conceder
                                                            </button>

                                                        </form>

                                                    <?php endif; ?>

                                                <?php else: ?>

                                                    <span class="text-gray-400 text-xs">
                                                        Indisponível
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </main>

</body>

</html>