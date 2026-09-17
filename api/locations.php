<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();
$userId = 0;
foreach (['user_id', 'id', 'login_id'] as $key) {
    if (isset($_SESSION[$key]) && is_numeric($_SESSION[$key])) {
        $userId = (int)$_SESSION[$key];
        break;
    }
}

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../Controller/LocationController.php';

try {
    $locations = (new LocationController())->getByUser($userId);
    $data = array_map(static function ($location) {
        return [
            'id' => $location->getId(),
            'name' => $location->getName(),
            'description' => $location->getDescription(),
            'sector' => $location->getSector(),
            'floor' => $location->getFloor()
        ];
    }, $locations);

    echo json_encode(['success' => true, 'locations' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar locais.'], JSON_UNESCAPED_UNICODE);
}
