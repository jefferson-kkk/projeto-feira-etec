<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../model/Connection.php';
require_once __DIR__ . '/../model/DeviceDAO.php';
require_once __DIR__ . '/../model/ReadingDAO.php';


/**
 * Retorna uma resposta JSON e encerra a execução.
 */
function responseJson($statusCode, $data)
{
    http_response_code($statusCode);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}


/**
 * Só aceita requisições POST.
 */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {

    responseJson(405, [
        'success' => false,
        'message' => 'Método não permitido. Use POST.'
    ]);
}


/**
 * Lê o JSON enviado pelo ESP32.
 */
$rawBody = file_get_contents('php://input');

$body = json_decode($rawBody, true);

if (!is_array($body)) {

    responseJson(400, [
        'success' => false,
        'message' => 'JSON inválido.'
    ]);
}


/**
 * Identificação do ESP32.
 *
 * O ESP32 deve enviar:
 *
 * {
 *   "device_id": "ESP32_001",
 *   "api_key": "...",
 *   "ppm": 350,
 *   "rssi": -55
 * }
 */
$esp32Id = trim(
    (string)($body['device_id'] ?? $body['esp32_id'] ?? '')
);


/**
 * API Key.
 *
 * Primeiro tenta pegar pelo cabeçalho X-API-KEY.
 * Caso não exista, tenta pegar pelo JSON.
 */
$apiKey = trim(
    (string)(
        $_SERVER['HTTP_X_API_KEY']
        ?? ($body['api_key'] ?? '')
    )
);


/**
 * Valor de ppm enviado pelo sensor.
 */
$ppm = $body['ppm'] ?? null;


/**
 * RSSI do Wi-Fi.
 */
$rssi = isset($body['rssi'])
    ? (int)$body['rssi']
    : null;


/**
 * Leitura bruta do ADC (0-4095), separada da estimativa em ppm.
 * O MQ-6 não possui calibração (curva Rs/R0) neste projeto, então
 * o ppm enviado é apenas uma estimativa; o raw_adc é o dado real.
 */
$rawAdc = isset($body['raw_adc'])
    ? (int)$body['raw_adc']
    : null;


/**
 * IP do ESP32.
 *
 * O servidor web identifica o IP da conexão.
 */
$ip = $_SERVER['REMOTE_ADDR'] ?? null;


/**
 * Validação dos dados obrigatórios.
 */
if (
    $esp32Id === '' ||
    $apiKey === '' ||
    !is_numeric($ppm)
) {

    responseJson(422, [
        'success' => false,
        'message' =>
            'device_id, api_key e ppm são obrigatórios.'
    ]);
}


/**
 * Converte ppm para número.
 */
$ppm = (float)$ppm;


/**
 * Proteção contra valores absurdos.
 */
if ($ppm < 0 || $ppm > 1000000) {

    responseJson(422, [
        'success' => false,
        'message' =>
            'Valor de ppm fora do limite permitido.'
    ]);
}


try {

    /*
     * Conecta ao DAO de dispositivos. Sem checar o schema aqui (caminho
     * quente, chamado a cada poucos segundos pelo ESP32) — quem garante
     * que as tabelas existem é qualquer outra tela do sistema.
     */
    $deviceDAO = new DeviceDAO(false);


    /*
     * Procura o ESP32 pelo identificador.
     */
    $device = $deviceDAO->getByEsp32Id($esp32Id);


    if (!$device) {

        responseJson(404, [
            'success' => false,
            'message' =>
                'Dispositivo não cadastrado.'
        ]);
    }


    /*
     * Obtém a conexão com o banco.
     */
    $conn = $deviceDAO->getConnection();


    /*
     * Busca a API Key cadastrada para o dispositivo.
     */
    $stmt = $conn->prepare("
        SELECT
            api_key_hash,
            last_alert_status
        FROM devices
        WHERE id = :id
        LIMIT 1
    ");


    $stmt->execute([
        ':id' => $device->getId()
    ]);


    $row = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
     * Verifica se o dispositivo possui
     * uma API Key válida.
     */
    if (
        !$row ||
        empty($row['api_key_hash']) ||
        !DeviceDAO::verifyApiKey(
            $apiKey,
            $row['api_key_hash']
        )
    ) {

        responseJson(401, [
            'success' => false,
            'message' => 'API key inválida.'
        ]);
    }


    /*
     * Classificação da leitura usando os limites configurados
     * pelo usuário em user_settings (tela de Configurações do
     * dashboard). Caso o usuário nunca tenha salvado uma
     * configuração, usa os mesmos padrões do dashboard.
     */
    $alertPpm = 550;
    $criticalPpm = 700;

    $settingsStmt = $conn->prepare("
        SELECT alert_ppm, critical_ppm
        FROM user_settings
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $settingsStmt->execute([
        ':user_id' => $device->getUserId()
    ]);

    $userSettings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

    if ($userSettings) {
        $alertPpm = (float) $userSettings['alert_ppm'];
        $criticalPpm = (float) $userSettings['critical_ppm'];
    }

    if ($ppm < $alertPpm) {

        $status = 'normal';

    } elseif ($ppm < $criticalPpm) {

        $status = 'atencao';

    } else {

        $status = 'perigo';
    }


    /*
     * Cria o DAO responsável pelas leituras (sem checar schema, mesmo
     * motivo do DeviceDAO acima).
     */
    $readingDAO = new ReadingDAO(false);


    /*
     * Registra a leitura no banco.
     */
    $readingId = $readingDAO->create(
        $device->getId(),
        $ppm,
        $status,
        $rssi,
        $rawAdc
    );


    /*
     * Informação de Wi-Fi.
     */
    if ($rssi !== null) {

        $wifiStatus = 'RSSI ' . $rssi . ' dBm';

    } else {

        $wifiStatus = 'online';
    }


    /*
     * Atualiza o estado do dispositivo.
     */
    $deviceDAO->updateOnline(
        $device->getId(),
        $wifiStatus,
        $ip,
        $status
    );


    /*
     * Resposta para o ESP32.
     */
    responseJson(200, [

        'success' => true,

        'message' => 'Leitura registrada.',

        'data' => [

            'reading_id' => $readingId,

            'device_id' => $device->getId(),

            'esp32_id' => $esp32Id,

            'ppm' => $ppm,

            'raw_adc' => $rawAdc,

            'status' => $status,

            'rssi' => $rssi,

            'ip' => $ip,

            'received_at' =>
                date('Y-m-d H:i:s')
        ]
    ]);


} catch (PDOException $e) {

    /*
     * Erro relacionado ao banco de dados.
     */
    responseJson(500, [

        'success' => false,

        'message' =>
            'Erro de banco de dados.',

        'error' =>
            $e->getMessage()
    ]);


} catch (Throwable $e) {

    /*
     * Outros erros da aplicação.
     */
    responseJson(500, [

        'success' => false,

        'message' =>
            'Erro interno.',

        'error' =>
            $e->getMessage()
    ]);
}