<?php
try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', 'senaisp');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "CONEXAO_OK";
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage();
}
