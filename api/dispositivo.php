<?php

header('Content-Type: application/json; charset=utf-8');

session_start();

require_once __DIR__ . '/../Controller/ProjectController.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);

    exit;
}

$controller = new ProjectController();

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

if ($acao === 'criar') {

    $nome = trim($_POST['name'] ?? '');
    $codigo = trim($_POST['manufacturer_code'] ?? '');
    $local = trim($_POST['location'] ?? '');
    $descricao = trim($_POST['description'] ?? '');
    $firmware = trim($_POST['firmware_version'] ?? '');
    $esp32Id = trim($_POST['esp32_id'] ?? '');

    if ($nome === '' || $codigo === '') {
        echo json_encode([
            'sucesso' => false,
            'erro' => 'Nome e código do dispositivo são obrigatórios.'
        ]);

        exit;
    }

    try {

        $apiKey = $controller->createProject(
            $_SESSION['user_id'],
            $nome,
            $codigo,
            $firmware ?: null,
            null,
            $esp32Id ?: null,
            null,
            $local ?: null,
            $descricao ?: null
        );

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Dispositivo criado.',
            'api_key' => $apiKey
        ]);

    } catch (Exception $e) {

        http_response_code(500);

        echo json_encode([
            'sucesso' => false,
            'erro' => $e->getMessage()
        ]);
    }

    exit;
}

if ($acao === 'editar') {

    $id = (int)($_POST['id'] ?? 0);

    $nome = trim($_POST['name'] ?? '');
    $local = trim($_POST['location'] ?? '');
    $descricao = trim($_POST['description'] ?? '');
    $firmware = trim($_POST['firmware_version'] ?? '');
    $wifi = trim($_POST['wifi_status'] ?? '');

    if ($id <= 0 || $nome === '') {
        echo json_encode([
            'sucesso' => false,
            'erro' => 'Dados inválidos.'
        ]);

        exit;
    }

    try {

        $resultado = $controller->atualizarProjeto(
            $id,
            $nome,
            $local,
            $descricao,
            $firmware,
            $wifi
        );

        echo json_encode([
            'sucesso' => $resultado,
            'mensagem' => 'Dispositivo atualizado.'
        ]);

    } catch (Exception $e) {

        http_response_code(500);

        echo json_encode([
            'sucesso' => false,
            'erro' => $e->getMessage()
        ]);
    }

    exit;
}

echo json_encode([
    'sucesso' => false,
    'erro' => 'Ação inválida.'
]);