<?php
/**
 * Script de Verificação do Banco Aeris
 * Execute este arquivo em um navegador ou via CLI
 */

require_once __DIR__ . '/model/Connection.php';
require_once __DIR__ . '/model/LoginDAO.php';
require_once __DIR__ . '/model/ProjectDAO.php';

echo "<h1>🔧 Verificação do Banco de Dados Aeris</h1>";

try {
    $conn = Connection::getConnection();
    echo "<p>✅ Conexão com MySQL estabelecida</p>";
    
    // Verifica se o banco existe
    $stmt = $conn->query("SELECT DATABASE()");
    $currentDB = $stmt->fetchColumn();
    echo "<p>✅ Banco atual: <strong>$currentDB</strong></p>";
    
    // Inicializa as tabelas
    $loginDAO = new LoginDAO();
    echo "<p>✅ Tabela 'login' inicializada</p>";
    
    $projectDAO = new ProjectDAO();
    echo "<p>✅ Tabela 'projects' inicializada</p>";
    
    // Verifica tabelas
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h2>Tabelas do Banco:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Verifica colunas da tabela login
    $columns = $conn->query("DESCRIBE login")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Colunas da Tabela 'login':</h2>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Chave</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verifica colunas da tabela projects
    $projectColumns = $conn->query("DESCRIBE projects")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Colunas da Tabela 'projects':</h2>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Chave</th><th>Default</th></tr>";
    foreach ($projectColumns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>✅ Banco 'aeris' pronto para uso!</h2>";
    echo "<p><a href='view/html/acesso.php'>Ir para a página de login</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
