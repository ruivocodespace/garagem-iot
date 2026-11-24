<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../../login.php");
    exit;
}

// Pesquisa
$busca = trim($_GET["busca"] ?? "");

// Consulta dos motoristas
$sql = "SELECT
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
        WHERE (
            m.nome LIKE :busca
            OR m.cpf LIKE :busca
            OR m.matricula LIKE :busca
            OR c.uid LIKE :busca
        )
        ORDER BY m.nome ASC";

$st = $pdo->prepare($sql);
$st->execute([
    "busca" => "%{$busca}%"
]);

$motoristas = $st->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoristas - Garagem IoT</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">

    <header class="bg-slate-900 text-white px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold">Garagem IoT</h1>
                <p class="text-sm text-slate-400">Gerenciamento de motoristas</p>
            </div>

            <a href="../../dashboard.php"
                class="text-sm bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg">
                Dashboard
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-6">

        <?php if (isset($_GET["cadastro"]) && $_GET["cadastro"] === "sucesso"): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded-lg text-sm mb-5">
                Motorista cadastrado com sucesso!
            </div>
        <?php endif; ?>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">

            <div>
                <h2 class="text-2xl font-bold text-gray-800">Motoristas</h2>
                <p class="text-sm text-gray-500">Gerencie os motoristas e seus cartões RFID.</p>
            </div>

            <a href="cadastrar.php"
                class="bg-slate-900 hover:bg-slate-800 text-white font-semibold px-5 py-2.5 rounded-lg text-center">
                + Cadastrar motorista
            </a>

        </div>

        <!-- Pesquisa -->
        <section class="bg-white rounded-xl shadow-sm p-5 mb-6">

            <form method="GET" class="flex flex-col sm:flex-row gap-3">

                <input
                    type="text"
                    name="busca"
                    value="<?= htmlspecialchars($busca) ?>"
                    placeholder="Pesquisar por nome, CPF, matrícula ou cartão RFID..."
                    class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">

                <button
                    type="submit"
                    class="bg-blue-700 hover:bg-blue-800 text-white font-semibold px-6 py-2.5 rounded-lg">
                    Pesquisar
                </button>

                <?php if ($busca !== ""): ?>
                    <a href="index.php"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-6 py-2.5 rounded-lg text-center">
                        Limpar
                    </a>
                <?php endif; ?>

            </form>

        </section>

        <!-- Listagem -->
        <section class="bg-white rounded-xl shadow-sm p-5">

            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-bold text-gray-800">Lista de motoristas</h3>

                <span class="text-sm text-gray-500">
                    <?= count($motoristas) ?> resultado(s)
                </span>
            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-sm text-left">

                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="p-3">Nome</th>
                            <th class="p-3">CPF</th>
                            <th class="p-3">Matrícula</th>
                            <th class="p-3">Telefone</th>
                            <th class="p-3">Cartão RFID</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        <?php if (count($motoristas) > 0): ?>

                            <?php foreach ($motoristas as $motorista): ?>

                                <tr class="hover:bg-gray-50">

                                    <td class="p-3 font-medium text-gray-800">
                                        <?= htmlspecialchars($motorista["nome"]) ?>
                                    </td>

                                    <td class="p-3 text-gray-600">
                                        <?= htmlspecialchars($motorista["cpf"]) ?>
                                    </td>

                                    <td class="p-3 text-gray-600">
                                        <?= htmlspecialchars($motorista["matricula"] ?? "-") ?>
                                    </td>

                                    <td class="p-3 text-gray-600">
                                        <?= htmlspecialchars($motorista["telefone"] ?? "-") ?>
                                    </td>

                                    <td class="p-3 text-gray-600 font-mono">
                                        <?= htmlspecialchars($motorista["uid"] ?? "Não cadastrado") ?>
                                    </td>

                                    <td class="p-3">
                                        <?php if ($motorista["status"] == 1): ?>
                                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-semibold">
                                                ATIVO
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-semibold">
                                                INATIVO
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-3">
                                        <a
                                            href="editar.php?id=<?= (int) $motorista["id_motorista"] ?>"
                                            class="text-blue-700 hover:underline font-semibold">
                                            Editar
                                        </a>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-400">
                                    Nenhum motorista encontrado.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</body>

</html>