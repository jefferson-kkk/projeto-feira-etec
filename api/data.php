<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../model/Connection.php';
require_once __DIR__ . '/../../model/DeviceDAO.php';
require_once __DIR__ . '/../../model/ReadingDAO.php';

session_start();

function out($status, $data)
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function getCurrentUserId()
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

$userId = getCurrentUserId();

if ($userId <= 0) {
    out(401, [
        'success' => false,
        'message' => 'Usuário não autenticado.'
    ]);
}

$deviceId = filter_input(
    INPUT_GET,
    'device_id',
    FILTER_VALIDATE_INT
);

if (!$deviceId || $deviceId <= 0) {
    out(422, [
        'success' => false,
        'message' => 'device_id obrigatório.'
    ]);
}

try {

    $deviceDAO = new DeviceDAO();

    $device = $deviceDAO->getById(
        $deviceId,
        $userId
    );

    if (!$device) {
        out(404, [
            'success' => false,
            'message' => 'Dispositivo não encontrado.'
        ]);
    }

    $readingDAO = new ReadingDAO();

    $limit = filter_input(
        INPUT_GET,
        'limit',
        FILTER_VALIDATE_INT
    );

    if (!$limit) {
        $limit = 300;
    }

    $limit = max(1, min($limit, 1000));

    $history = $readingDAO->getHistory(
        $deviceId,
        $limit
    );

    $latest = $readingDAO->getLatest(
        $deviceId
    );

    $stats = $readingDAO->getStats(
        $deviceId
    );

    out(200, [

        'success' => true,

        'device' => [
            'id' => $device->getId(),
            'name' => $device->getName(),
            'manufacturer_code' => $device->getManufacturerCode(),
            'esp32_id' => $device->getEsp32Id(),
            'sensor_type' => $device->getSensorType(),
            'status' => $device->getStatus(),
            'wifi_status' => $device->getWifiStatus(),
            'last_online' => $device->getLastOnline()
        ],

        'latest' => $latest,

        'stats' => $stats,

        'history' => $history
    ]);

} catch (Throwable $e) {

    out(500, [
        'success' => false,
        'message' => 'Erro ao carregar os dados do dispositivo.',
        'error' => $e->getMessage()
    ]);
}