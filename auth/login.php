<?php
session_start();
require_once "../config/database.php";

if (isset($_SESSION["id_usuario"])) {
    header("Location: ../admin/dashboard.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if ($email === "" || $senha === "") {
        $erro = "Preencha todos os campos.";
    } else {

        $st = $pdo->prepare(
            "SELECT id_usuario, nome, email, senha, status
             FROM usuarios
             WHERE email = ?
             LIMIT 1"
        );

        $st->execute([$email]);
        $usuario = $st->fetch(PDO::FETCH_ASSOC);

        if ($usuario && $usuario["status"] == 1 && password_verify($senha, $usuario["senha"])) {

            session_regenerate_id(true);

            $_SESSION["id_usuario"] = $usuario["id_usuario"];
            $_SESSION["usuario_nome"] = $usuario["nome"];
            $_SESSION["usuario_email"] = $usuario["email"];

            header("Location: ../admin/dashboard.php");
            exit;
        } else {
            $erro = "E-mail ou senha inválidos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Garagem IoT</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white w-full max-w-md rounded-2xl shadow-lg p-8">

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Garagem IoT</h1>
            <p class="text-sm text-gray-500 mt-2">Acesse o painel administrativo</p>
        </div>

        <?php if (isset($_GET["cadastro"]) && $_GET["cadastro"] === "sucesso"): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded-lg text-sm mb-5">
                Conta criada com sucesso! Faça login para continuar.
            </div>
        <?php endif; ?>

        <?php if ($erro !== ""): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded-lg text-sm mb-5">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                <input
                    type="email"
                    name="email"
                    required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                <input
                    type="password"
                    name="senha"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button
                type="submit"
                class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 rounded-lg transition-colors">
                Entrar
            </button>

        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Primeiro acesso?
            <a href="../admin/cadastro.php" class="text-blue-700 font-semibold hover:underline">
                Criar conta administrativa
            </a>
        </p>

    </div>

</body>

</html>