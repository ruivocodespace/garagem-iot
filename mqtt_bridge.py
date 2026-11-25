
import json
import requests
import paho.mqtt.client as mqtt

# CONFIGURACOES MQTT
MQTT_SERVER = "broker.mqtt.cool"
MQTT_PORT = 1883

MQTT_ROOT = "mango/esp32c3/peterson"

TOPIC_RFID = MQTT_ROOT + "/rfid"
TOPIC_COMMAND = MQTT_ROOT + "/command"

# ENDERECO DO PHP
PHP_URL = "http://localhost:8012/GARAGEM-IOT/api/processar_acesso.php"

# CONEXAO MQTT
def on_connect(client, userdata, flags, reason_code, properties):
    if reason_code == 0:
        print("MQTT conectado!")

        client.subscribe(TOPIC_RFID)

        print("Inscrito no topico:", TOPIC_RFID)
    else:
        print("Falha na conexao MQTT:", reason_code)


# RECEBER CARTAO RFID
def on_message(client, userdata, msg):
    print("\nMensagem recebida:", msg.topic)

    try:
        dados = json.loads(msg.payload.decode("utf-8"))

        print("Dados recebidos:", dados)

        # VALIDAR CAMPOS
        uid = dados.get("uid")
        box = dados.get("box")
        request_id = dados.get("request_id")

        if not uid or not box or not request_id:
            print("Mensagem incompleta. Ignorando.")
            return

        # ENVIAR PARA O PHP
        print("Enviando solicitacao ao PHP...")

        resposta = requests.post(
            PHP_URL,
            json={
                "uid": uid,
                "box": box,
                "request_id": request_id
            },
            timeout=10
        )

        resposta.raise_for_status()

        resultado = resposta.json()

        print("Resposta do PHP:", resultado)

        # VALIDAR RESPOSTA
        if resultado.get("request_id") != request_id:
            print("Erro: request_id diferente.")
            return

        autorizado = resultado.get("authorized") is True

        # MONTAR RESPOSTA MQTT
        comando = {
            "request_id": request_id,
            "authorized": autorizado,
            "line1": "ACESSO LIBERADO" if autorizado else "ACESSO NEGADO",
            "line2": resultado.get("operacao", ""),
            "line3": resultado.get("motivo", "")
        }

        if autorizado and resultado.get("servo1") is not None:
            comando["servo1"] = int(resultado["servo1"])

        # PUBLICAR RESPOSTA
        client.publish(
            TOPIC_COMMAND,
            json.dumps(comando, ensure_ascii=False),
            qos=1
        )

        print("Resposta publicada no MQTT:", comando)

    except requests.exceptions.RequestException as erro:
        print("Erro na comunicacao com PHP:", erro)

    except json.JSONDecodeError:
        print("Mensagem recebida nao e um JSON valido.")

    except Exception as erro:
        print("Erro no processamento:", erro)


# INICIALIZAR CLIENTE
cliente = mqtt.Client(
    callback_api_version=mqtt.CallbackAPIVersion.VERSION2,
    client_id="GARAGEM-IOT-PYTHON"
)

cliente.on_connect = on_connect
cliente.on_message = on_message

print("Iniciando servico GARAGEM-IOT...")

try:
    cliente.connect(MQTT_SERVER, MQTT_PORT, 60)
    cliente.loop_forever()

except KeyboardInterrupt:
    print("\nServico encerrado.")

finally:
    cliente.disconnect()
