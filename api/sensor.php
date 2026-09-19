<?php

/*
 * LEGADO: endpoint de ingestão da antiga tabela `projects`/
 * `sensor_data`. O ESP32 deve enviar leituras para api/receive.php,
 * que grava em `devices`/`sensor_readings` (o que o dashboard lê).
 * Mantido só por compatibilidade com firmwares antigos.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../model/Connection.php';
require_once __DIR__ . '/../model/Project.php';
require_once __DIR__ . '/../model/ProjectDAO.php';
require_once __DIR__ . '/../model/SensorDataDAO.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'sucesso' => false,
        'erro' => 'Método não permitido. Use POST.'
    ]);

    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);

if (!is_array($dados)) {
    $dados = $_POST;
}

$apiKey = trim($dados['api_key'] ?? '');
$ppm = $dados['ppm'] ?? null;
$rawValue = $dados['raw_value'] ?? null;
$wifiStatus = $dados['wifi_status'] ?? 'online';
$esp32Id = $dados['esp32_id'] ?? null;

if ($apiKey === '') {
    http_response_code(401);

    echo json_encode([
        'sucesso' => false,
        'erro' => 'API key não informada.'
    ]);

    exit;
}

if ($ppm === null || !is_numeric($ppm)) {
    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'erro' => 'PPM inválido.'
    ]);

    exit;
}

try {

    $projectDAO = new ProjectDAO();

    $project = $projectDAO->getProjectByApiKey($apiKey);

    if (!$project) {
        http_response_code(401);

        echo json_encode([
            'sucesso' => false,
            'erro' => 'Dispositivo não encontrado ou API key inválida.'
        ]);

        exit;
    }

    $sensorDAO = new SensorDataDAO();

    $sensorDAO->salvar(
        $project->getId(),
        (float)$ppm,
        $rawValue !== null ? (int)$rawValue : null
    );

    $projectDAO->atualizarStatus(
        $project->getId(),
        'online',
        $wifiStatus
    );

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Dados recebidos.',
        'device_id' => $project->getId(),
        'ppm' => (float)$ppm,
        'horario' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}