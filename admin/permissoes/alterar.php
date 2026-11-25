
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

if (
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $csrf_token)
) {
    header("Location: index.php?erro=" . urlencode("Solicitação inválida. Atualize a página e tente novamente."));
    exit;
}

$id_motorista = (int) ($_POST["id_motorista"] ?? 0);
$id_box = (int) ($_POST["id_box"] ?? 0);
$acao = $_POST["acao"] ?? "";
$id_usuario = (int) $_SESSION["id_usuario"];

try {

    if ($id_motorista <= 0 || $id_box <= 0) {
        throw new Exception("Motorista ou box inválido.");
    }

    if (!in_array($acao, ["conceder", "revogar"], true)) {
        throw new Exception("Ação inválida.");
    }

    $pdo->beginTransaction();

    // Verifica o motorista
    $stmt = $pdo->prepare("
        SELECT id_motorista, status
        FROM motoristas
        WHERE id_motorista = ?
        FOR UPDATE
    ");
    $stmt->execute([$id_motorista]);
    $motorista = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$motorista) {
        throw new Exception("Motorista não encontrado.");
    }

    // Verifica o box e o ônibus associado
    $stmt = $pdo->prepare("
        SELECT
            b.id_box,
            b.ativo,
            b.status AS status_box,
            o.status AS status_onibus
        FROM boxes b
        INNER JOIN onibus o ON o.id_onibus = b.id_onibus
        WHERE b.id_box = ?
        FOR UPDATE
    ");
    $stmt->execute([$id_box]);
    $box = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$box) {
        throw new Exception("Box não encontrado.");
    }

    // Verifica a permissão existente
    $stmt = $pdo->prepare("
        SELECT id_permissao, status
        FROM permissoes
        WHERE id_motorista = ? AND id_box = ?
        FOR UPDATE
    ");
    $stmt->execute([$id_motorista, $id_box]);
    $permissao = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($acao === "conceder") {

        if ((int) $motorista["status"] !== 1) {
            throw new Exception("Não é possível conceder acesso a um motorista inativo.");
        }

        if (
            (int) $box["ativo"] !== 1 ||
            in_array($box["status_box"], ["MANUTENCAO", "INTERDITADO"], true) ||
            $box["status_onibus"] !== "ATIVO"
        ) {
            throw new Exception("Não é possível conceder acesso a esse box. Verifique a situação do box e do ônibus.");
        }

        if ($permissao && (int) $permissao["status"] === 1) {
            throw new Exception("Essa permissão já está ativa.");
        }

        if ($permissao) {

            $stmt = $pdo->prepare("
                UPDATE permissoes
                SET status = 1,
                    data_revogacao = NULL,
                    id_usuario_concessao = ?,
                    id_usuario_revogacao = NULL
                WHERE id_permissao = ?
            ");

            $stmt->execute([$id_usuario, $permissao["id_permissao"]]);
        } else {

            $stmt = $pdo->prepare("
                INSERT INTO permissoes
                    (id_motorista, id_box, status, id_usuario_concessao)
                VALUES (?, ?, 1, ?)
            ");

            $stmt->execute([$id_motorista, $id_box, $id_usuario]);
        }
    } else {

        if (!$permissao || (int) $permissao["status"] !== 1) {
            throw new Exception("Essa permissão já está revogada ou não existe.");
        }

        // Verifica se o motorista ainda ocupa o box
        $stmt = $pdo->prepare("
            SELECT id_ocupacao
            FROM ocupacoes
            WHERE id_motorista = ?
              AND id_box = ?
              AND status = 'ATIVA'
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$id_motorista, $id_box]);

        if ($stmt->fetch()) {
            throw new Exception("Não é possível revogar a permissão enquanto o motorista estiver ocupando esse box.");
        }

        $stmt = $pdo->prepare("
            UPDATE permissoes
            SET status = 0,
                data_revogacao = NOW(),
                id_usuario_revogacao = ?
            WHERE id_permissao = ?
        ");

        $stmt->execute([$id_usuario, $permissao["id_permissao"]]);
    }

    $pdo->commit();

    header("Location: index.php?sucesso=1");
    exit;
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: index.php?erro=" . urlencode($e->getMessage()));
    exit;
}
