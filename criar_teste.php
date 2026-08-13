<?php
/**
 * Script para inserir dados de teste no banco Aeris
 */

require_once __DIR__ . '/model/Connection.php';
require_once __DIR__ . '/Controller/Controller.php';
require_once __DIR__ . '/model/ProjectDAO.php';
require_once __DIR__ . '/model/Project.php';

try {
    $conn = Connection::getConnection();
    
    // Limpar dados antigos (opcional)
    $conn->exec("DELETE FROM projects");
    $conn->exec("DELETE FROM login");
    
    $controller = new LoginController();
    
    // Criar usuários de teste
    echo "🔐 Criando usuários de teste...\n\n";
    
    $users = [
        ['João Silva', 'joao@aeris.com', 'senha123'],
        ['Maria Santos', 'maria@aeris.com', 'senha456'],
        ['Carlos Oliveira', 'carlos@aeris.com', 'senha789'],
    ];
    
    $userIds = [];
    foreach ($users as $user) {
        $novoUser = $controller->criarLogin($user[0], $user[2], $user[1], date('Y-m-d H:i:s'));
        $userIds[] = $novoUser->getid();
        echo "✅ Usuário criado: {$user[1]}\n";
    }
    
    echo "\n";
    
    // Criar projetos de teste
    echo "📱 Criando projetos/dispositivos de teste...\n\n";
    
    $projectDAO = new ProjectDAO();
    
    $projects = [
        [1, 'Monitor Temperatura Sala 1', 'ESP32-TEMP-001', 'online', 'v1.2.3', 'Good'],
        [1, 'Sensor Umidade Lab', 'ESP32-HUMID-002', 'offline', 'v1.1.0', 'Weak'],
        [2, 'Detector Vazamento', 'ESP32-WATER-003', 'online', 'v1.0.5', 'Good'],
        [3, 'Monitor Pressão', 'ESP32-PRESS-004', 'offline', null, 'Disconnected'],
        [2, 'Estação Meteo Completa', 'ESP32-METEO-005', 'online', 'v2.0.1', 'Excellent'],
    ];
    
    foreach ($projects as $proj) {
        $project = new Project(
            null,
            $proj[0],
            $proj[1],
            $proj[2],
            date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')),
            $proj[3],
            $proj[4],
            $proj[5],
            'ESP32-' . rand(100000, 999999),
            $proj[3] === 'online' ? date('Y-m-d H:i:s', strtotime('-' . rand(0, 24) . ' hours')) : null
        );
        $projectDAO->createProject($project);
        echo "✅ Projeto criado: {$proj[1]} ({$proj[2]})\n";
    }
    
    echo "\n✨ Dados de teste inseridos com sucesso!\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
