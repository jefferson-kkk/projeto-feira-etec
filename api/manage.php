<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../model/Connection.php';
require_once __DIR__ . '/../model/DeviceDAO.php';
require_once __DIR__ . '/../Controller/LocationController.php';

session_start();

function currentUserId()
{
    foreach (['user_id', 'id', 'login_id'] as $key) {

        if (
            isset($_SESSION[$key]) &&
            is_numeric($_SESSION[$key]) &&
            (int)$_SESSION[$key] > 0
        ) {
            return (int)$_SESSION[$key];
        }
    }

    return 0;
}

function out($status, $data)
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function auditManage(
    $conn,
    $userId,
    $action,
    $entity,
    $entityId = null,
    $details = null,
    $deviceId = null
) {

    try {

        $q = $conn->prepare("
            INSERT INTO audit_logs
            (
                user_id,
                device_id,
                action,
                entity,
                entity_id,
                details,
                ip_address
            )
            VALUES
            (
                :user_id,
                :device_id,
                :action,
                :entity,
                :entity_id,
                :details,
                :ip_address
            )
        ");

        $q->execute([

            ':user_id' => $userId,

            ':device_id' => $deviceId,

            ':action' => $action,

            ':entity' => $entity,

            ':entity_id' =>
                $entityId !== null
                    ? (string)$entityId
                    : null,

            ':details' =>
                $details !== null
                    ? json_encode(
                        $details,
                        JSON_UNESCAPED_UNICODE
                    )
                    : null,

            ':ip_address' =>
                $_SERVER['REMOTE_ADDR'] ?? null
        ]);

    } catch (Throwable $e) {

        // A auditoria não deve impedir
        // o cadastro do dispositivo.
    }
}

$userId = currentUserId();

if ($userId <= 0) {

    out(401, [
        'success' => false,
        'message' => 'Sessão do usuário não encontrada.'
    ]);
}

$raw = file_get_contents('php://input');

$body = json_decode(
    $raw,
    true
);

if (!is_array($body)) {

    out(400, [
        'success' => false,
        'message' => 'JSON inválido.'
    ]);
}

$action = trim(
    $body['action'] ?? ''
);

try {

    /*
    |--------------------------------------------------------------------------
    | CRIAR LOCAL
    |--------------------------------------------------------------------------
    */

    if ($action === 'create_location') {

        $name = trim(
            $body['name'] ?? ''
        );

        if ($name === '') {

            out(422, [
                'success' => false,
                'message' => 'Informe o nome do local.'
            ]);
        }

        $controller = new LocationController();

        $locationId = $controller->create(
            $userId,
            $name,
            trim($body['description'] ?? ''),
            trim($body['sector'] ?? ''),
            trim($body['floor'] ?? ''),
            isset($body['pos_x']) ? (float)$body['pos_x'] : 0,
            isset($body['pos_y']) ? (float)$body['pos_y'] : 0,
            isset($body['width']) ? max(0.5, (float)$body['width']) : 3,
            isset($body['depth']) ? max(0.5, (float)$body['depth']) : 3
        );

        $dao = new DeviceDAO();

        auditManage(
            $dao->getConnection(),
            $userId,
            'location_created',
            'locations',
            $locationId,
            $body
        );

        out(201, [

            'success' => true,

            'location_id' => $locationId,

            'message' =>
                'Local cadastrado com sucesso.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SALVAR POSIÇÃO/TAMANHO DO CÔMODO NA PLANTA (editor visual 3D)
    |--------------------------------------------------------------------------
    */

    if ($action === 'update_location_layout') {

        $locationId = (int)($body['id'] ?? 0);

        if ($locationId <= 0) {
            out(422, [
                'success' => false,
                'message' => 'ID do ambiente obrigatório.'
            ]);
        }

        $controller = new LocationController();

        $ok = $controller->updateLayout(
            $locationId,
            $userId,
            (float)($body['pos_x'] ?? 0),
            (float)($body['pos_y'] ?? 0),
            max(0.5, (float)($body['width'] ?? 3)),
            max(0.5, (float)($body['depth'] ?? 3))
        );

        out(200, [
            'success' => (bool)$ok,
            'message' => 'Planta atualizada.'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DAO
    |--------------------------------------------------------------------------
    */

    $dao = new DeviceDAO();


    /*
    |--------------------------------------------------------------------------
    | VINCULAR DISPOSITIVO JÁ CADASTRADO (outra conta)
    |--------------------------------------------------------------------------
    |
    | Não duplica o cadastro do ESP32 (esp32_id continua único). Serve
    | para permitir que mais de uma conta acompanhe o mesmo dispositivo
    | físico — por exemplo, várias pessoas testando o mesmo sensor na
    | feira. Só funciona se a conta souber a api_key do dispositivo,
    | então continua seguro: sem a chave, ninguém enxerga o dispositivo
    | de outra pessoa.
    |--------------------------------------------------------------------------
    */

    if ($action === 'join_device') {

        $esp32Id = trim($body['esp32_id'] ?? '');
        $apiKey = trim($body['api_key'] ?? '');

        if ($esp32Id === '' || $apiKey === '') {
            out(422, [
                'success' => false,
                'message' => 'Informe o ID do ESP32 e a API key do dispositivo.'
            ]);
        }

        $device = $dao->getByEsp32Id($esp32Id);

        if (!$device) {
            out(404, [
                'success' => false,
                'message' => 'Nenhum dispositivo com esse ESP32 ID foi encontrado.'
            ]);
        }

        $row = $dao->getConnection()->prepare("
            SELECT api_key_hash FROM devices WHERE id = :id LIMIT 1
        ");
        $row->execute([':id' => $device->getId()]);
        $hash = $row->fetchColumn();

        if (!$hash || !DeviceDAO::verifyApiKey($apiKey, $hash)) {
            out(401, [
                'success' => false,
                'message' => 'API key incorreta para esse dispositivo.'
            ]);
        }

        if ((int)$device->getUserId() === (int)$userId) {
            out(200, [
                'success' => true,
                'device_id' => $device->getId(),
                'message' => 'Você já é o dono deste dispositivo.'
            ]);
        }

        $dao->shareWithUser($device->getId(), $userId);

        auditManage(
            $dao->getConnection(),
            $userId,
            'device_shared',
            'devices',
            $device->getId(),
            ['esp32_id' => $esp32Id],
            $device->getId()
        );

        out(200, [
            'success' => true,
            'device_id' => $device->getId(),
            'message' => 'Dispositivo vinculado à sua conta com sucesso.'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CRIAR DISPOSITIVO
    |--------------------------------------------------------------------------
    */

    if ($action === 'create_device') {

        $name = trim(
            $body['name'] ?? ''
        );

        $esp32Id = trim(
            $body['esp32_id'] ?? ''
        );

        $manufacturerCode = trim(
            $body['manufacturer_code'] ?? ''
        );

        if (
            $name === '' ||
            $esp32Id === '' ||
            $manufacturerCode === ''
        ) {

            out(422, [

                'success' => false,

                'message' =>
                    'Nome, ESP32 ID e código do fabricante são obrigatórios.'
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Verificar ESP32
        |--------------------------------------------------------------------------
        */

        if ($dao->getByEsp32Id($esp32Id)) {

            out(409, [

                'success' => false,

                'message' =>
                    'Esse ESP32 ID já está cadastrado.'
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Verificar código
        |--------------------------------------------------------------------------
        */

        if (
                $dao->getByManufacturerCode(
                    $manufacturerCode
            )
        ) {

            out(409, [

                'success' => false,

                'message' =>
                    'Esse código do fabricante já está cadastrado.'
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | API KEY
        |--------------------------------------------------------------------------
        */

        $apiKey = bin2hex(
            random_bytes(24)
        );

        /*
         * SENSOR ATUAL DO PROJETO
         */
        $sensorType = trim($body['sensor_type'] ?? 'MQ-6');


        /*
        |--------------------------------------------------------------------------
        | Criar dispositivo
        |--------------------------------------------------------------------------
        */

        $deviceId = $dao->create([

            ':user_id' => $userId,

            ':name' => $name,

            ':manufacturer_code' =>
                $manufacturerCode,

            ':description' =>
                trim(
                    $body['description'] ?? ''
                ),

            ':location_id' =>
                !empty($body['location_id'])
                    ? (int)$body['location_id']
                    : null,

            ':sensor_type' => $sensorType,

            ':status' =>
                'offline',

            ':firmware_version' =>
                trim(
                    $body['firmware_version'] ?? ''
                ),

            ':wifi_status' =>
                null,

            ':esp32_id' =>
                $esp32Id,

            ':api_key_hash' =>
                DeviceDAO::hashApiKey($apiKey)
        ]);


        auditManage(

            $dao->getConnection(),

            $userId,

            'device_created',

            'devices',

            $deviceId,

            [
                'name' => $name,

                'esp32_id' => $esp32Id,

                'sensor_type' => $sensorType
            ],

            $deviceId
        );


        out(201, [

            'success' => true,

            'device_id' => $deviceId,

            /*
             * Essa chave é mostrada UMA VEZ
             * para configurar o ESP32.
             */
            'api_key' => $apiKey,

            'message' =>
                'Dispositivo cadastrado com sucesso.'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ATUALIZAR DISPOSITIVO
    |--------------------------------------------------------------------------
    */

    if ($action === 'update_device') {

        $deviceId = (int)(
            $body['id'] ?? 0
        );

        if ($deviceId <= 0) {

            out(422, [

                'success' => false,

                'message' =>
                    'ID do dispositivo obrigatório.'
            ]);
        }


        $device = $dao->getById(
            $deviceId,
            $userId
        );

        if (!$device) {

            out(404, [

                'success' => false,

                'message' =>
                    'Dispositivo não encontrado.'
            ]);
        }


        $sensorType = trim(
            $body['sensor_type']
            ?? $device->getSensorType()
            ?? 'MQ-6'
        );


        $ok = $dao->update([

            ':id' =>
                $deviceId,

            ':user_id' =>
                $userId,

            ':name' =>
                trim(
                    $body['name']
                    ?? $device->getName()
                ),

            ':manufacturer_code' =>
                trim(
                    $body['manufacturer_code']
                    ?? $device->getManufacturerCode()
                ),

            ':description' =>
                trim(
                    $body['description'] ?? ''
                ),

            ':location_id' =>
                !empty($body['location_id'])
                    ? (int)$body['location_id']
                    : null,

            ':sensor_type' =>
                $sensorType,

            ':status' =>
                trim(
                    $body['status']
                    ?? $device->getStatus()
                    ?? 'offline'
                ),

            ':firmware_version' =>
                trim(
                    $body['firmware_version'] ?? ''
                ),

            ':esp32_id' =>
                trim(
                    $body['esp32_id']
                    ?? $device->getEsp32Id()
                )
        ]);


        auditManage(

            $dao->getConnection(),

            $userId,

            'device_updated',

            'devices',

            $deviceId,

            $body,

            $deviceId
        );


        out(200, [

            'success' => (bool)$ok,

            'message' =>
                'Informações do dispositivo atualizadas.'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | AÇÃO INVÁLIDA
    |--------------------------------------------------------------------------
    */

    out(400, [

        'success' => false,

        'message' =>
            'Ação não reconhecida.'
    ]);

} catch (Throwable $e) {

    out(500, [

        'success' => false,

        'message' =>
            'Erro interno ao processar a solicitação.',

        'error' =>
            $e->getMessage()
    ]);
}