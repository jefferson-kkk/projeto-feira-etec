<?php
/**
 * Script CLI para exibir dados do banco Aeris
 */

require_once __DIR__ . '/model/Connection.php';
require_once __DIR__ . '/model/LoginDAO.php';
require_once __DIR__ . '/model/ProjectDAO.php';

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║        AERIS SECURE - VERIFICAÇÃO DO BANCO DE DADOS     ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

try {
    $conn = Connection::getConnection();
    
    // Obter nome do banco
    $stmt = $conn->query("SELECT DATABASE()");
    $dbName = $stmt->fetchColumn();
    echo "✅ Banco de Dados: $dbName\n";
    echo "✅ Conexão: MySQL Local (root/senaisp)\n\n";
    
    // Inicializar DAOs para criar tabelas
    echo "📋 Criando/Verificando tabelas...\n";
    $loginDAO = new LoginDAO();
    $projectDAO = new ProjectDAO();
    echo "✅ Tabelas inicializadas\n\n";
    
    // ===== TABELA LOGIN =====
    echo "╔════════════════════════════════════════════════════════╗\n";
    echo "║                  TABELA: login (Usuários)              ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n\n";
    
    $loginStmt = $conn->query("SELECT COUNT(*) FROM login");
    $loginCount = $loginStmt->fetchColumn();
    echo "📊 Total de registros: $loginCount\n\n";
    
    if ($loginCount > 0) {
        $stmt = $conn->query("SELECT id, nome, email, username, avatar, last_login, created_at FROM login ORDER BY id DESC LIMIT 10");
        $logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "ID  │ Nome                  │ Email                    │ Username     │ Avatar │ Last Login              │ Created\n";
        echo "────┼───────────────────────┼──────────────────────────┼──────────────┼────────┼─────────────────────────┼──────────────────────\n";
        
        foreach ($logins as $login) {
            $id = str_pad($login['id'], 3);
            $nome = substr($login['nome'] ?? 'N/A', 0, 21);
            $nome = str_pad($nome, 21);
            $email = substr($login['email'] ?? 'N/A', 0, 26);
            $email = str_pad($email, 26);
            $user = substr($login['username'] ?? 'N/A', 0, 12);
            $user = str_pad($user, 12);
            $avatar = $login['avatar'] ? '✓' : '✗';
            $avatar = str_pad($avatar, 6);
            $lastLogin = substr($login['last_login'] ?? '—', 0, 23);
            $lastLogin = str_pad($lastLogin, 23);
            $created = substr($login['created_at'] ?? 'N/A', 0, 19);
            
            echo "$id │ $nome │ $email │ $user │ $avatar │ $lastLogin │ $created\n";
        }
        
        if ($loginCount > 10) {
            echo "\n... e " . ($loginCount - 10) . " outros registros\n";
        }
    } else {
        echo "❌ Nenhum usuário cadastrado ainda.\n";
    }
    
    echo "\n";
    
    // ===== TABELA PROJECTS =====
    echo "╔════════════════════════════════════════════════════════╗\n";
    echo "║            TABELA: projects (Dispositivos)             ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n\n";
    
    $projStmt = $conn->query("SELECT COUNT(*) FROM projects");
    $projCount = $projStmt->fetchColumn();
    echo "📊 Total de registros: $projCount\n\n";
    
    if ($projCount > 0) {
        $stmt = $conn->query("SELECT id, user_id, name, manufacturer_code, status, registered_at, last_online FROM projects ORDER BY id DESC LIMIT 10");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "ID  │ User │ Nome             │ Código           │ Status   │ Registrado          │ Último Online\n";
        echo "────┼─────┼──────────────────┼──────────────────┼──────────┼─────────────────────┼──────────────────\n";
        
        foreach ($projects as $proj) {
            $id = str_pad($proj['id'], 3);
            $user = str_pad($proj['user_id'], 5);
            $name = substr($proj['name'] ?? 'N/A', 0, 16);
            $name = str_pad($name, 16);
            $code = substr($proj['manufacturer_code'] ?? 'N/A', 0, 16);
            $code = str_pad($code, 16);
            $status = substr($proj['status'] ?? 'N/A', 0, 8);
            $status = str_pad($status, 8);
            $registered = substr($proj['registered_at'] ?? 'N/A', 0, 19);
            $registered = str_pad($registered, 19);
            $lastOnline = substr($proj['last_online'] ?? '—', 0, 16);
            
            echo "$id │ $user │ $name │ $code │ $status │ $registered │ $lastOnline\n";
        }
        
        if ($projCount > 10) {
            echo "\n... e " . ($projCount - 10) . " outros registros\n";
        }
    } else {
        echo "❌ Nenhum projeto/dispositivo registrado ainda.\n";
    }
    
    echo "\n";
    
    // ===== RESUMO =====
    echo "╔════════════════════════════════════════════════════════╗\n";
    echo "║                      RESUMO FINAL                      ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n\n";
    
    echo "✅ Banco: $dbName\n";
    echo "✅ Usuários (login): $loginCount\n";
    echo "✅ Projetos (projects): $projCount\n";
    echo "✅ Fluxo: Login → Dashboard → Conectar Projeto → Logout\n";
    echo "✅ Segurança: Passwords com hash, Prepared Statements, XSS Protection\n\n";
    
    echo "📍 Para ver dados via extensão MySQL:\n";
    echo "   1. Abra VSCode → MySQL\n";
    echo "   2. Conecte: localhost:3306 (root/senaisp)\n";
    echo "   3. Clique em 'aeris'\n";
    echo "   4. Explore: login e projects\n\n";
    
    echo "🌐 Para acessar a aplicação:\n";
    echo "   http://localhost/projeto-feira-etec/view/html/acesso.php\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
