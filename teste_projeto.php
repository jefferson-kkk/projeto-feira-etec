<?php
require __DIR__ . '/model/Connection.php';

try {
    $conn = Connection::getConnection();
    echo "CONEXAO_OK\n";
    $conn->exec("CREATE DATABASE IF NOT EXISTS login CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->exec("USE login");
    $conn->exec("CREATE TABLE IF NOT EXISTS login (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        senha VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        datacriacao DATETIME NOT NULL
    )");
    echo "TABELA_OK\n";
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage();
}
