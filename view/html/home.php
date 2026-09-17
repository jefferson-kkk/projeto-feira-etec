<?php
require_once __DIR__ . '/_auth.php';

$homeFile = __DIR__ . '/teste.html';
if (!is_file($homeFile)) {
    http_response_code(500);
    exit('Arquivo da home não encontrado: teste.html');
}

readfile($homeFile);
?>
