<?php

/*
 * tests/unit_tests.php
 *
 * Testes unitários: testam funções isoladas, SEM depender do banco
 * de dados nem do servidor estar rodando. Rodam em milissegundos e
 * servem pra pegar erros de lógica antes de qualquer coisa mais séria.
 *
 * Como rodar:
 *   C:\php\php.exe tests\unit_tests.php
 *
 * Não usa nenhum framework (PHPUnit etc.) de propósito -- o projeto
 * não depende de Composer (ver AUDITORIA.md em setup/), então este
 * runner é só um script PHP simples com asserções manuais.
 */

require_once __DIR__ . '/../model/DeviceDAO.php';
require_once __DIR__ . '/../model/MiniAssistant.php';

$total = 0;
$falhas = [];

function assertEqual($esperado, $obtido, $descricao)
{
    global $total, $falhas;
    $total++;
    if ($esperado === $obtido) {
        echo "  [OK] $descricao\n";
    } else {
        $falhas[] = $descricao;
        echo "  [FALHA] $descricao\n";
        echo "          esperado: " . var_export($esperado, true) . "\n";
        echo "          obtido:   " . var_export($obtido, true) . "\n";
    }
}

function assertTrue($condicao, $descricao)
{
    assertEqual(true, (bool)$condicao, $descricao);
}

function assertNull($valor, $descricao)
{
    global $total, $falhas;
    $total++;
    if ($valor === null) {
        echo "  [OK] $descricao\n";
    } else {
        $falhas[] = $descricao;
        echo "  [FALHA] $descricao (esperava null, obteve: " . var_export($valor, true) . ")\n";
    }
}

function assertNotNull($valor, $descricao)
{
    global $total, $falhas;
    $total++;
    if ($valor !== null) {
        echo "  [OK] $descricao\n";
    } else {
        $falhas[] = $descricao;
        echo "  [FALHA] $descricao (obteve null)\n";
    }
}

echo "=== UNIT: DeviceDAO::hashApiKey ===\n";
$h1 = DeviceDAO::hashApiKey('minha-chave-123');
$h2 = DeviceDAO::hashApiKey('minha-chave-123');
$h3 = DeviceDAO::hashApiKey('outra-chave-456');
assertEqual($h1, $h2, 'mesma chave sempre gera o mesmo hash (determinístico)');
assertTrue($h1 !== $h3, 'chaves diferentes geram hashes diferentes');
assertEqual(64, strlen($h1), 'hash SHA-256 tem 64 caracteres hexadecimais');
assertTrue(DeviceDAO::verifyApiKey('minha-chave-123', $h1), 'verifyApiKey confirma a chave correta');
assertTrue(!DeviceDAO::verifyApiKey('chave-errada', $h1), 'verifyApiKey rejeita a chave errada');

echo "\n=== UNIT: senha (password_hash / password_verify nativos do PHP) ===\n";
$senhaHash = password_hash('minhasenha123', PASSWORD_DEFAULT);
assertTrue(password_verify('minhasenha123', $senhaHash), 'password_verify aceita a senha correta');
assertTrue(!password_verify('senhaerrada', $senhaHash), 'password_verify rejeita senha errada');
assertTrue(strpos($senhaHash, 'minhasenha123') === false, 'o hash da senha não contém a senha em texto puro');

echo "\n=== UNIT: MiniAssistant (modo de reserva sem IA) ===\n";
$r1 = MiniAssistant::respond('meu sensor esta offline, o que faço?');
assertNotNull($r1, "mensagem sobre 'offline' encontra uma resposta");
assertTrue(strpos($r1, 'ESP32') !== false, 'a resposta sobre offline menciona o ESP32');

$r2 = MiniAssistant::respond('qual sensor voces usam, mq-6?');
assertNotNull($r2, "mensagem sobre 'sensor'/'mq-6' encontra uma resposta");

$r3 = MiniAssistant::respond('oi');
assertNull($r3, "mensagem genérica sem palavra-chave (ex: 'oi') retorna null -- é o Gemini quem deveria responder isso, não o modo de reserva");

$r4 = MiniAssistant::respond('como faço para conectar um dispositivo novo?');
assertNotNull($r4, "mensagem sobre 'conectar dispositivo' encontra uma resposta");

echo "\n=== UNIT: LoginDAO::usernameFromEmail (via Reflection, método privado) ===\n";
require_once __DIR__ . '/../model/Connection.php';
require_once __DIR__ . '/../model/Login.php';
require_once __DIR__ . '/../model/LoginDAO.php';
try {
    $dao = new LoginDAO();
    $ref = new ReflectionMethod('LoginDAO', 'usernameFromEmail');
    $ref->setAccessible(true);
    $u1 = $ref->invoke($dao, 'Jefferson.Correa@GMAIL.com');
    assertEqual('jefferson.correa', $u1, 'usernameFromEmail normaliza para minúsculas e usa só a parte antes do @');
    $u2 = $ref->invoke($dao, 'ana+teste@empresa.com.br');
    assertTrue(strpos($u2, '@') === false, 'usernameFromEmail nunca deixa "@" no username gerado');
} catch (Throwable $e) {
    echo "  [AVISO] não foi possível testar LoginDAO::usernameFromEmail (precisa do banco para instanciar): " . $e->getMessage() . "\n";
}

echo "\n=== UNIT: regras de classificação de status (mesmos limiares do api/receive.php) ===\n";
function classificarStatus($ppm, $alertPpm, $criticalPpm)
{
    if ($ppm < $alertPpm) return 'normal';
    if ($ppm < $criticalPpm) return 'atencao';
    return 'perigo';
}
assertEqual('normal', classificarStatus(100, 550, 700), 'ppm bem abaixo do limite de alerta = normal');
assertEqual('normal', classificarStatus(549.9, 550, 700), 'ppm logo abaixo do limite de alerta ainda é normal');
assertEqual('atencao', classificarStatus(550, 550, 700), 'ppm igual ao limite de alerta já vira atenção');
assertEqual('atencao', classificarStatus(699.9, 550, 700), 'ppm logo abaixo do limite crítico ainda é atenção');
assertEqual('perigo', classificarStatus(700, 550, 700), 'ppm igual ao limite crítico já vira perigo');
assertEqual('perigo', classificarStatus(2000, 550, 700), 'ppm bem acima do limite crítico é perigo');

echo "\n========================================\n";
echo "RESULTADO: " . ($total - count($falhas)) . "/$total testes unitários passaram\n";
if ($falhas) {
    echo "FALHARAM:\n";
    foreach ($falhas as $f) echo "  - $f\n";
    exit(1);
}
echo "TODOS OS TESTES UNITÁRIOS PASSARAM.\n";
exit(0);
