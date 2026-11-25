<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../../login.php");
    exit;
}

$erro = "";

$nome = "";
$cpf = "";
$matricula = "";
$telefone = "";
$uid = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $cpf = trim($_POST["cpf"] ?? "");
    $matricula = trim($_POST["matricula"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $uid = strtoupper(str_replace(" ", "", trim($_POST["uid"] ?? "")));

    if ($nome === "" || $cpf === "" || $uid === "") {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {

        try {

            $pdo->beginTransaction();

            // Verifica CPF
            $st = $pdo->prepare(
                "SELECT id_motorista FROM motoristas WHERE cpf = ?"
            );
            $st->execute([$cpf]);

            if ($st->fetch()) {
                throw new Exception("Este CPF já está cadastrado.");
            }

            // Verifica matrícula
            if ($matricula !== "") {
                $st = $pdo->prepare(
                    "SELECT id_motorista FROM motoristas WHERE matricula = ?"
                );
                $st->execute([$matricula]);

                if ($st->fetch()) {
                    throw new Exception("Esta matrícula já está cadastrada.");
                }
            }

            // Verifica cartão RFID
            $st = $pdo->prepare(
                "SELECT id_credencial FROM credenciais_rfid WHERE uid = ?"
            );
            $st->execute([$uid]);

            if ($st->fetch()) {
                throw new Exception("Este cartão RFID já está cadastrado.");
            }

            // Cadastra motorista
            $st = $pdo->prepare(
                "INSERT INTO motoristas (nome, cpf, matricula, telefone)
                 VALUES (?, ?, ?, ?)"
            );

            $st->execute([
                $nome,
                $cpf,
                $matricula !== "" ? $matricula : null,
                $telefone !== "" ? $telefone : null
            ]);

            $id_motorista = $pdo->lastInsertId();

            // Cadastra cartão RFID
            $st = $pdo->prepare(
                "INSERT INTO credenciais_rfid (id_motorista, uid)
                 VALUES (?, ?)"
            );

            $st->execute([$id_motorista, $uid]);

            // Concede acesso aos quatro boxes
            $st = $pdo->prepare(
                "INSERT INTO permissoes (id_motorista, id_box, id_usuario_concessao)
                 SELECT ?, id_box, ?
                 FROM boxes
                 WHERE ativo = 1"
            );

            $st->execute([
                $id_motorista,
                $_SESSION["id_usuario"]
            ]);

            $pdo->commit();

            header("Location: index.php?cadastro=sucesso");
            exit;
        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erro = $e->getMessage();
        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erro = "Não foi possível realizar o cadastro. Verifique os dados informados.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Motorista - Garagem IoT</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">

    <header class="bg-slate-900 text-white px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold">Garagem IoT</h1>
                <p class="text-sm text-slate-400">Gerenciamento de motoristas</p>
            </div>

            <a href="../dashboard.php"
                class="text-sm bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg">
                Dashboard
            </a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto p-6">
        <?php if (isset($_GET["edicao"]) && $_GET["edicao"] === "sucesso"): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded-lg text-sm mb-5">
                Motorista atualizado com sucesso!
            </div>
        <?php endif; ?>

        <div class="mb-6">
            <a href="index.php" class="text-sm text-blue-700 hover:underline">
                ← Voltar para motoristas
            </a>

            <h2 class="text-2xl font-bold text-gray-800 mt-3">
                Cadastrar motorista
            </h2>

            <p class="text-sm text-gray-500">
                Preencha os dados para cadastrar um novo motorista.
            </p>
        </div>

        <section class="bg-white rounded-xl shadow-sm p-6">

            <?php if ($erro !== ""): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded-lg text-sm mb-5">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nome completo *
                    </label>

                    <input
                        type="text"
                        name="nome"
                        required
                        maxlength="100"
                        value="<?= htmlspecialchars($nome) ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            CPF *
                        </label>

                        <input
                            type="text"
                            name="cpf"
                            required
                            maxlength="14"
                            placeholder="000.000.000-00"
                            value="<?= htmlspecialchars($cpf) ?>"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Matrícula
                        </label>

                        <input
                            type="text"
                            name="matricula"
                            maxlength="30"
                            value="<?= htmlspecialchars($matricula) ?>"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Telefone
                    </label>

                    <input
                        type="text"
                        name="telefone"
                        maxlength="20"
                        placeholder="(00) 00000-0000"
                        value="<?= htmlspecialchars($telefone) ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="border-t border-gray-200 pt-5">

                    <h3 class="text-lg font-semibold text-gray-800 mb-1">
                        Credencial RFID
                    </h3>

                    <p class="text-sm text-gray-500 mb-4">
                        Informe o UID do cartão que será associado ao motorista.
                    </p>

                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        UID do cartão *
                    </label>

                    <input
                        type="text"
                        name="uid"
                        required
                        maxlength="30"
                        placeholder="Ex.: A1B2C3D4"
                        value="<?= htmlspecialchars($uid) ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 font-mono uppercase focus:outline-none focus:ring-2 focus:ring-blue-500">

                </div>

                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                    <p class="text-sm text-blue-800">
                        <strong>Permissões iniciais:</strong>
                        o motorista receberá acesso a todos os boxes ativos cadastrados no sistema.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">

                    <button
                        type="submit"
                        class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 rounded-lg transition-colors">
                        Cadastrar motorista
                    </button>

                    <a href="index.php"
                        class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 rounded-lg transition-colors">
                        Cancelar
                    </a>

                </div>

            </form>

        </section>

    </main>

</body>

</html>