
<?php
header('Content-Type: application/json; charset=utf-8');

require_once "../config/database.php";

function responder($requestId, $autorizado, $motivo, $operacao = "INDEFINIDA")
{
    echo json_encode([
        "request_id" => $requestId,
        "authorized" => $autorizado,
        "operacao" => $operacao,
        "motivo" => $motivo,
        "servo1" => $autorizado ? 90 : null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$dados = json_decode(file_get_contents("php://input"), true);

if (!$dados || empty($dados["uid"]) || empty($dados["box"]) || empty($dados["request_id"])) {
    http_response_code(400);
    responder($dados["request_id"] ?? "", false, "Dados incompletos");
}

$uid = strtoupper(trim($dados["uid"]));
$nomeBox = trim($dados["box"]);
$requestId = trim($dados["request_id"]);

$idMotorista = null;
$idBox = null;
$idOcupacao = null;
$operacao = "INDEFINIDA";
$resultado = "NEGADO";
$motivo = "Acesso não autorizado";

try {
    $pdo->beginTransaction();

    // Evita processar novamente uma solicitação já registrada.
    $consulta = $pdo->prepare("
        SELECT resultado, operacao, motivo
        FROM acessos
        WHERE request_id = ?
        LIMIT 1
    ");
    $consulta->execute([$requestId]);
    $anterior = $consulta->fetch();

    if ($anterior) {
        $pdo->commit();

        responder(
            $requestId,
            $anterior["resultado"] === "AUTORIZADO",
            $anterior["motivo"],
            $anterior["operacao"]
        );
    }

    // Localiza o box selecionado.
    $consulta = $pdo->prepare("
        SELECT id_box, id_onibus, nome, status, ativo
        FROM boxes
        WHERE nome = ?
        LIMIT 1
        FOR UPDATE
    ");
    $consulta->execute([$nomeBox]);
    $box = $consulta->fetch();

    if ($box) {
        $idBox = $box["id_box"];
    }

    // Localiza o cartão e o motorista.
    $consulta = $pdo->prepare("
        SELECT
            c.id_credencial,
            c.id_motorista,
            c.status AS status_credencial,
            m.nome,
            m.status AS status_motorista
        FROM credenciais_rfid c
        INNER JOIN motoristas m
            ON m.id_motorista = c.id_motorista
        WHERE c.uid = ?
        LIMIT 1
        FOR UPDATE
    ");
    $consulta->execute([$uid]);
    $motorista = $consulta->fetch();

    if (!$motorista) {
        $motivo = "Cartão RFID não cadastrado";
    } else {
        $idMotorista = $motorista["id_motorista"];

        if ($motorista["status_credencial"] !== 1) {
            $motivo = "Credencial RFID inativa";
        } elseif ($motorista["status_motorista"] !== 1) {
            $motivo = "Motorista inativo";
        } elseif (!$box) {
            $motivo = "Box não encontrado";
        } else {

            // Verifica se o motorista já possui ocupação ativa.
            $consulta = $pdo->prepare("
                SELECT id_ocupacao, id_box
                FROM ocupacoes
                WHERE id_motorista = ?
                  AND status = 'ATIVA'
                LIMIT 1
                FOR UPDATE
            ");
            $consulta->execute([$idMotorista]);
            $ocupacao = $consulta->fetch();

            if ($ocupacao) {
                $idOcupacao = $ocupacao["id_ocupacao"];

                // A saída precisa ocorrer no mesmo box da entrada.
                if ($ocupacao["id_box"] != $idBox) {
                    $motivo = "Finalize a ocupação atual antes de selecionar outro box";
                } else {
                    $operacao = "SAIDA";

                    // Finaliza a ocupação.
                    $consulta = $pdo->prepare("
                        UPDATE ocupacoes
                        SET saida = NOW(),
                            status = 'FINALIZADA'
                        WHERE id_ocupacao = ?
                    ");
                    $consulta->execute([$idOcupacao]);

                    // Libera o box.
                    $consulta = $pdo->prepare("
                        UPDATE boxes
                        SET status = 'LIVRE'
                        WHERE id_box = ?
                    ");
                    $consulta->execute([$idBox]);

                    $resultado = "AUTORIZADO";
                    $motivo = "Saída autorizada";
                }
            } else {
                $operacao = "ENTRADA";

                // Verifica permissão do motorista.
                $consulta = $pdo->prepare("
                    SELECT id_permissao
                    FROM permissoes
                    WHERE id_motorista = ?
                      AND id_box = ?
                      AND status = 1
                    LIMIT 1
                ");
                $consulta->execute([$idMotorista, $idBox]);
                $permissao = $consulta->fetch();

                if (!$permissao) {
                    $motivo = "Motorista sem permissão para este box";
                } elseif ((int)$box["ativo"] !== 1) {
                    $motivo = "Box desativado";
                } elseif ($box["status"] !== "LIVRE") {
                    $motivo = "Box indisponível";
                } else {

                    // Verifica o ônibus associado ao box.
                    $consulta = $pdo->prepare("
                        SELECT status
                        FROM onibus
                        WHERE id_onibus = ?
                        LIMIT 1
                        FOR UPDATE
                    ");
                    $consulta->execute([$box["id_onibus"]]);
                    $onibus = $consulta->fetch();

                    if (!$onibus || $onibus["status"] !== "ATIVO") {
                        $motivo = "Ônibus indisponível";
                    } else {

                        // Registra a nova ocupação.
                        $consulta = $pdo->prepare("
                            INSERT INTO ocupacoes
                                (id_motorista, id_box, id_onibus, entrada, status)
                            VALUES
                                (?, ?, ?, NOW(), 'ATIVA')
                        ");
                        $consulta->execute([
                            $idMotorista,
                            $idBox,
                            $box["id_onibus"]
                        ]);

                        $idOcupacao = $pdo->lastInsertId();

                        // Atualiza o status do box.
                        $consulta = $pdo->prepare("
                            UPDATE boxes
                            SET status = 'OCUPADO'
                            WHERE id_box = ?
                        ");
                        $consulta->execute([$idBox]);

                        $resultado = "AUTORIZADO";
                        $motivo = "Entrada autorizada";
                    }
                }
            }
        }
    }

    // Registra todas as tentativas de acesso.
    $consulta = $pdo->prepare("
        INSERT INTO acessos (
            id_motorista,
            id_box,
            id_ocupacao,
            uid_informado,
            request_id,
            operacao,
            resultado,
            motivo,
            data_hora
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $consulta->execute([
        $idMotorista,
        $idBox,
        $idOcupacao,
        $uid,
        $requestId,
        $operacao,
        $resultado,
        $motivo
    ]);

    $pdo->commit();

    responder(
        $requestId,
        $resultado === "AUTORIZADO",
        $motivo,
        $operacao
    );
} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Erro processar_acesso.php: " . $e->getMessage());

    http_response_code(500);

    responder(
        $requestId,
        false,
        "Erro interno ao processar acesso"
    );
}
