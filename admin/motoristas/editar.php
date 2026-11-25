<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../../auth/login.php");
    exit;
}

$erro = "";

$id_motorista = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id_motorista) {
    header("Location: index.php");
    exit;
}

// Busca motorista
$st = $pdo->prepare(
    "SELECT
        m.id_motorista,
        m.nome,
        m.cpf,
        m.matricula,
        m.telefone,
        m.status,
        c.uid
     FROM motoristas m
     LEFT JOIN credenciais_rfid c
        ON m.id_motorista = c.id_motorista
     WHERE m.id_motorista = ?"
);

$st->execute([$id_motorista]);
$motorista = $st->fetch(PDO::FETCH_ASSOC);

if (!$motorista) {
    header("Location: index.php");
    exit;
}

// Dados do formulário
$nome = $motorista["nome"];
$cpf = $motorista["cpf"];
$matricula = $motorista["matricula"];
$telefone = $motorista["telefone"];
$uid = $motorista["uid"];
$status = $motorista["status"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $cpf = trim($_POST["cpf"] ?? "");
    $matricula = trim($_POST["matricula"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $uid = strtoupper(str_replace(" ", "", trim($_POST["uid"] ?? "")));
    $status = isset($_POST["status"]) ? (int) $_POST["status"] : 0;

    if ($nome === "" || $cpf === "" || $uid === "") {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif (!in_array($status, [0, 1], true)) {
        $erro = "Status inválido.";
    } else {

        try {

            $pdo->beginTransaction();

            // Verifica CPF em outro motorista
            $st = $pdo->prepare(
                "SELECT id_motorista
                 FROM motoristas
                 WHERE cpf = ? AND id_motorista <> ?"
            );

            $st->execute([$cpf, $id_motorista]);

            if ($st->fetch()) {
                throw new Exception("Este CPF já está cadastrado.");
            }

            // Verifica matrícula em outro motorista
            if ($matricula !== "") {

                $st = $pdo->prepare(
                    "SELECT id_motorista
                     FROM motoristas
                     WHERE matricula = ? AND id_motorista <> ?"
                );

                $st->execute([$matricula, $id_motorista]);

                if ($st->fetch()) {
                    throw new Exception("Esta matrícula já está cadastrada.");
                }
            }

            // Verifica UID em outra credencial
            $st = $pdo->prepare(
                "SELECT id_credencial
                 FROM credenciais_rfid
                 WHERE uid = ? AND id_motorista <> ?"
            );

            $st->execute([$uid, $id_motorista]);

            if ($st->fetch()) {
                throw new Exception("Este cartão RFID já está cadastrado.");
            }

            // Atualiza motorista
            $st = $pdo->prepare(
                "UPDATE motoristas
                 SET nome = ?, cpf = ?, matricula = ?, telefone = ?, status = ?
                 WHERE id_motorista = ?"
            );

            $st->execute([
                $nome,
                $cpf,
                $matricula !== "" ? $matricula : null,
                $telefone !== "" ? $telefone : null,
                $status,
                $id_motorista
            ]);

            // Atualiza ou cadastra credencial RFID
            $st = $pdo->prepare(
                "SELECT id_credencial
                 FROM credenciais_rfid
                 WHERE id_motorista = ?"
            );

            $st->execute([$id_motorista]);
            $credencial = $st->fetch(PDO::FETCH_ASSOC);

            if ($credencial) {

                $st = $pdo->prepare(
                    "UPDATE credenciais_rfid
                     SET uid = ?, status = 1
                     WHERE id_motorista = ?"
                );

                $st->execute([$uid, $id_motorista]);
            } else {

                $st = $pdo->prepare(
                    "INSERT INTO credenciais_rfid (id_motorista, uid)
                     VALUES (?, ?)"
                );

                $st->execute([$id_motorista, $uid]);
            }

            // Desativa a credencial se o motorista ficar inativo
            if ($status === 0) {

                $st = $pdo->prepare(
                    "UPDATE credenciais_rfid
                     SET status = 0
                     WHERE id_motorista = ?"
                );

                $st->execute([$id_motorista]);
            }

            $pdo->commit();

            header("Location: index.php?edicao=sucesso");
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

            $erro = "Não foi possível atualizar o motorista.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Motorista - Garagem IoT</title>
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

        <div class="mb-6">
            <a href="index.php" class="text-sm text-blue-700 hover:underline">
                ← Voltar para motoristas
            </a>

            <h2 class="text-2xl font-bold text-gray-800 mt-3">
                Editar motorista
            </h2>

            <p class="text-sm text-gray-500">
                Atualize os dados do motorista e sua credencial.
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
                            value="<?= htmlspecialchars($matricula ?? "") ?>"
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
                        value="<?= htmlspecialchars($telefone ?? "") ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="border-t border-gray-200 pt-5">

                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        Credencial RFID
                    </h3>

                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        UID do cartão *
                    </label>

                    <input
                        type="text"
                        name="uid"
                        required
                        maxlength="30"
                        value="<?= htmlspecialchars($uid ?? "") ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 font-mono uppercase focus:outline-none focus:ring-2 focus:ring-blue-500">

                </div>

                <div class="border-t border-gray-200 pt-5">

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Status do motorista
                    </label>

                    <select
                        name="status"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">

                        <option value="1" <?= $status == 1 ? "selected" : "" ?>>
                            Ativo
                        </option>

                        <option value="0" <?= $status == 0 ? "selected" : "" ?>>
                            Inativo
                        </option>

                    </select>

                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">

                    <button
                        type="submit"
                        class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 rounded-lg transition-colors">
                        Salvar alterações
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