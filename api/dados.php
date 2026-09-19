<?php

/*
 * LEGADO: endpoint da antiga tabela `projects`/`sensor_data`.
 * O dashboard atual (dashboard.php) usa api/data.php, que lê da
 * tabela `devices`/`sensor_readings`. Mantido só por compatibilidade
 * com integrações antigas que ainda apontem para este arquivo.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../model/Connection.php';
require_once __DIR__ . '/../model/SensorDataDAO.php';
require_once __DIR__ . '/../model/ProjectDAO.php';

$projectId = isset($_GET['project_id'])
    ? (int)$_GET['project_id']
    : 0;

if ($projectId <= 0) {
    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'erro' => 'project_id inválido.'
    ]);

    exit;
}

try {

    $projectDAO = new ProjectDAO();
    $sensorDAO = new SensorDataDAO();

    $project = $projectDAO->getProjectById($projectId);

    if (!$project) {
        http_response_code(404);

        echo json_encode([
            'sucesso' => false,
            'erro' => 'Dispositivo não encontrado.'
        ]);

        exit;
    }

    $ultimo = $sensorDAO->getUltimo($projectId);
    $historico = $sensorDAO->getHistorico($projectId, 100);

    echo json_encode([
        'sucesso' => true,

        'dispositivo' => [
            'id' => $project->getId(),
            'nome' => $project->getName(),
            'codigo' => $project->getManufacturerCode(),
            'local' => $project->getLocation(),
            'descricao' => $project->getDescription(),
            'status' => $project->getStatus(),
            'wifi' => $project->getWifiStatus(),
            'esp32_id' => $project->getEsp32Id(),
            'firmware' => $project->getFirmwareVersion(),
            'ultimo_online' => $project->getLastOnline()
        ],

        'ultimo_dado' => $ultimo,

        'historico' => $historico
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}