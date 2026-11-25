<?php
session_start();
require_once "../config/database.php";

$erro = "";
$cadastro_liberado = (int) $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() === 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!$cadastro_liberado) {
        $erro = "O cadastro inicial já foi realizado.";
    } else {

        $nome = trim($_POST["nome"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $senha = $_POST["senha"] ?? "";
        $confirmar_senha = $_POST["confirmar_senha"] ?? "";

        if ($nome === "" || $email === "" || $senha === "" || $confirmar_senha === "") {
            $erro = "Preencha todos os campos.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = "Informe um e-mail válido.";
        } elseif (strlen($senha) < 8) {
            $erro = "A senha deve ter pelo menos 8 caracteres.";
        } elseif ($senha !== $confirmar_senha) {
            $erro = "As senhas não coincidem.";
        } else {

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            try {
                $st = $pdo->prepare(
                    "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)"
                );

                $st->execute([$nome, $email, $senha_hash]);

                header("Location: ../auth/login.php?cadastro=sucesso");
                exit;
            } catch (PDOException $e) {
                $erro = "Não foi possível realizar o cadastro.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Garagem IoT</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white w-full max-w-md rounded-2xl shadow-lg p-8">

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Garagem IoT</h1>
            <p class="text-sm text-gray-500 mt-2">Criar conta administrativa</p>
        </div>

        <?php if ($erro !== ""): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded-lg text-sm mb-5">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <?php if ($cadastro_liberado): ?>

            <form method="POST" class="space-y-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                    <input
                        type="text"
                        name="nome"
                        required
                        value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input
                        type="email"
                        name="email"
                        required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input
                        type="password"
                        name="senha"
                        required
                        minlength="8"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar senha</label>
                    <input
                        type="password"
                        name="confirmar_senha"
                        required
                        minlength="8"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <button
                    type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 rounded-lg transition-colors">
                    Criar conta
                </button>

            </form>

        <?php else: ?>

            <div class="bg-yellow-50 text-yellow-800 p-4 rounded-lg text-sm">
                O cadastro inicial já foi realizado. Não é possível criar outra conta por esta tela.
            </div>

        <?php endif; ?>

        <p class="text-center text-sm text-gray-500 mt-6">
            Já possui uma conta?
            <a href="../auth/login.php" class="text-blue-700 font-semibold hover:underline">
                Fazer login
            </a>
        </p>

    </div>

</body>

</html>