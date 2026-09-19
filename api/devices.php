<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../model/DeviceDAO.php';

function responseJson($code, $data)
{
    http_response_code($code);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

/*
 * Temporariamente usamos o usuário da sessão.
 * Depois vamos padronizar a autenticação.
 */
session_start();

$userId = $_SESSION['id']
    ?? $_SESSION['user_id']
    ?? null;

if (!$userId) {
    responseJson(401, [
        'success' => false,
        'message' => 'Usuário não autenticado.'
    ]);
}

try {

    $dao = new DeviceDAO();

    $devices = $dao->getAllByUser(
        (int) $userId
    );

    foreach ($devices as &$device) {
        unset($device['api_key_hash']);
    }
    unset($device);

    responseJson(200, [
        'success' => true,
        'devices' => $devices
    ]);

} catch (Throwable $e) {

    responseJson(500, [
        'success' => false,
        'message' => 'Erro ao carregar dispositivos.',
        'error' => $e->getMessage()
    ]);
}