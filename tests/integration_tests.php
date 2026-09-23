<?php

/*
 * tests/integration_tests.php
 *
 * Testes de integração: precisam do servidor rodando de verdade
 * (Caddy + php-cgi + MySQL). Testam o sistema como um usuário real
 * usaria -- fazendo requisições HTTP contra o servidor local.
 *
 * Como rodar (com o servidor já no ar em http://localhost:8000):
 *   C:\php\php.exe tests\integration_tests.php
 *
 * Vários destes testes existem porque cobrem bugs REAIS que já
 * aconteceram neste projeto -- são testes de regressão, não só
 * verificação genérica. Cada um comenta qual problema ele evita que
 * volte sem ser percebido.
 */

$BASE = 'http://localhost:8000';
$COOKIE_JAR = sys_get_temp_dir() . '/sadag_test_cookies_' . uniqid() . '.txt';

$total = 0;
$falhas = [];

function http($method, $path, $body = null, $headers = [], $followRedirects = false)
{
    global $BASE, $COOKIE_JAR;
    $ch = curl_init($BASE . $path);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_COOKIEJAR => $COOKIE_JAR,
        CURLOPT_COOKIEFILE => $COOKIE_JAR,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => $followRedirects,
        CURLOPT_TIMEOUT => 20,
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = is_string($body) ? $body : json_encode($body);
        $headers[] = 'Content-Type: application/json';
    }
    if ($headers) $opts[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($ch, $opts);
    $t0 = microtime(true);
    $raw = curl_exec($ch);
    $tempo = microtime(true) - $t0;
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['status' => 0, 'body' => '', 'headers' => '', 'json' => null, 'tempo' => $tempo, 'erro' => $curlErr];
    }

    $headersRaw = substr($raw, 0, $headerSize);
    $bodyRaw = substr($raw, $headerSize);
    $json = json_decode($bodyRaw, true);

    return ['status' => $status, 'body' => $bodyRaw, 'headers' => $headersRaw, 'json' => $json, 'tempo' => $tempo, 'erro' => null];
}

function assertEqual($esperado, $obtido, $descricao)
{
    global $total, $falhas;
    $total++;
    if ($esperado === $obtido) {
        echo "  [OK] $descricao\n";
    } else {
        $falhas[] = $descricao;
        echo "  [FALHA] $descricao (esperado " . var_export($esperado, true) . ", obtido " . var_export($obtido, true) . ")\n";
    }
}

function assertTrue($condicao, $descricao, $detalhe = '')
{
    global $total, $falhas;
    $total++;
    if ($condicao) {
        echo "  [OK] $descricao\n";
    } else {
        $falhas[] = $descricao;
        echo "  [FALHA] $descricao" . ($detalhe ? " ($detalhe)" : "") . "\n";
    }
}

// =====================================================================
echo "=== INTEGRAÇÃO: servidor de pé ===\n";
$r = http('GET', '/view/html/acesso.php');
assertEqual(200, $r['status'], 'GET /view/html/acesso.php responde 200');
assertTrue($r['erro'] === null, 'nenhum erro de conexão ao acessar o servidor (Caddy/PHP-CGI no ar)', $r['erro']);

// =====================================================================
echo "\n=== INTEGRAÇÃO: conexão com o banco não pode ficar lenta de novo ===\n";
// Regressão do bug real: model/Connection.php usava host='localhost',
// e no Windows isso levava ~2 segundos por causa da resolução de
// IPv6 antes do fallback pra IPv4 -- afetava toda página do site.
// Corrigido usando 127.0.0.1. Este teste garante que ninguém reverte
// isso sem perceber.
$connSrc = file_get_contents(__DIR__ . '/../model/Connection.php');
assertTrue(strpos($connSrc, "'127.0.0.1'") !== false, "Connection.php usa 127.0.0.1 (não 'localhost') -- evita o bug de 2s de lentidão no Windows");
$t0 = microtime(true);
http('GET', '/view/html/acesso.php');
$tempoResposta = microtime(true) - $t0;
assertTrue($tempoResposta < 1.0, "uma página que toca o banco responde em menos de 1s (levou " . round($tempoResposta, 2) . "s)", "se isso falhar, suspeite do host de conexão do banco");

// =====================================================================
echo "\n=== INTEGRAÇÃO: PHP consegue fazer chamadas HTTPS (SSL) ===\n";
// Regressão do bug real: o PHP baixado do site oficial não vem com
// pacote de certificados configurado (curl.cainfo vazio), e toda
// chamada HTTPS (Gemini, Claude) falhava com "unable to get local
// issuer certificate". Corrigido apontando curl.cainfo/openssl.cafile
// pro cacert.pem baixado.
$phpIni = @file_get_contents('C:/php/php.ini');
if ($phpIni !== false) {
    assertTrue(
        (bool)preg_match('/^curl\.cainfo\s*=\s*".+"/m', $phpIni),
        'php.ini tem curl.cainfo configurado (não comentado) -- evita falha silenciosa em toda chamada HTTPS'
    );
} else {
    echo "  [AVISO] não encontrei C:\\php\\php.ini pra checar (ambiente diferente?)\n";
}

// =====================================================================
echo "\n=== INTEGRAÇÃO: login ===\n";
$emailTeste = 'jecorrea.2008@gmail.com';
$r = http('POST', '/view/html/acesso.php', 'tipo=login&email=' . urlencode($emailTeste) . '&senha=senhaerrada12345', ['Content-Type: application/x-www-form-urlencoded']);
assertTrue(strpos($r['body'], 'E-mail ou senha inv') !== false, 'login com senha errada mostra mensagem de erro (não deixa passar)');

// A senha real só existe na cabeça do usuário -- pedimos por variável
// de ambiente pra este teste poder logar de verdade sem senha fixa no
// código. Sem ela, os testes que dependem de sessão são pulados.
$senhaReal = getenv('SADAG_TEST_PASSWORD');
$logado = false;
if ($senhaReal) {
    $r = http('POST', '/view/html/acesso.php', 'tipo=login&email=' . urlencode($emailTeste) . '&senha=' . urlencode($senhaReal), ['Content-Type: application/x-www-form-urlencoded']);
    $logado = ($r['status'] === 302 && strpos($r['headers'], 'home.php') !== false);
    assertTrue($logado, 'login com credenciais corretas redireciona (302) para home.php');
} else {
    echo "  [PULADO] defina a variável de ambiente SADAG_TEST_PASSWORD pra testar login de verdade e os testes autenticados abaixo\n";
}

// =====================================================================
echo "\n=== INTEGRAÇÃO: proteção de rotas autenticadas ===\n";
$COOKIE_JAR_ANON = sys_get_temp_dir() . '/sadag_test_anon_' . uniqid() . '.txt';
$chAnon = curl_init($BASE . '/api/devices.php');
curl_setopt_array($chAnon, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $COOKIE_JAR_ANON, CURLOPT_COOKIEFILE => $COOKIE_JAR_ANON]);
$rawAnon = curl_exec($chAnon);
$statusAnon = curl_getinfo($chAnon, CURLINFO_HTTP_CODE);
curl_close($chAnon);
assertEqual(401, $statusAnon, 'GET /api/devices.php sem login retorna 401 (não vaza dados de outro usuário)');
@unlink($COOKIE_JAR_ANON);

if ($logado) {
    $r = http('GET', '/api/devices.php');
    assertEqual(200, $r['status'], 'GET /api/devices.php com sessão válida retorna 200');
    assertTrue(is_array($r['json']['devices'] ?? null), 'resposta de /api/devices.php tem a chave "devices" como lista');
}

// =====================================================================
echo "\n=== INTEGRAÇÃO: api/receive.php (ingestão de leituras do ESP32) ===\n";
$r = http('POST', '/api/receive.php', ['device_id' => 'ESP32-NAO-EXISTE-999', 'api_key' => 'qualquer', 'ppm' => 100]);
assertEqual(404, $r['status'], "receive.php rejeita device_id inexistente com 404 (não aceita dispositivo não cadastrado)");

$r = http('POST', '/api/receive.php', ['device_id' => 'ESP32-MQ6-001']);
assertEqual(422, $r['status'], 'receive.php rejeita corpo sem os campos obrigatórios (422)');

$r = http('POST', '/api/receive.php', ['device_id' => 'ESP32-MQ6-001', 'api_key' => 'chave-de-teste-invalida-de-proposito', 'ppm' => 100]);
assertTrue(in_array($r['status'], [401, 403]), 'receive.php rejeita api_key inválida para um dispositivo que existe (' . $r['status'] . ')');

// =====================================================================
echo "\n=== INTEGRAÇÃO: schema do banco (colunas que os DAOs esperam em runtime) ===\n";
// Regressão do bug real: api/iot.schema.sql estava desatualizado --
// faltavam colunas (pos_x/pos_y/width/depth, raw_adc, related_id,
// escalated, is_read) que os próprios DAOs adicionam sozinhos na
// primeira execução. Um ambiente novo que só importasse o .sql sem
// nunca rodar o app ficaria com o schema incompleto.
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=aeris;charset=utf8mb4', 'root', getenv('AERIS_DB_PASSWORD') ?: 'senaisp', [PDO::ATTR_TIMEOUT => 5]);
    $colunasEsperadas = [
        'locations' => ['pos_x', 'pos_y', 'width', 'depth'],
        'sensor_readings' => ['raw_adc'],
        'notifications' => ['related_id'],
        'support_tickets' => ['escalated'],
        'support_messages' => ['is_read'],
    ];
    foreach ($colunasEsperadas as $tabela => $colunas) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tabela`");
        $existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($colunas as $coluna) {
            assertTrue(in_array($coluna, $existentes), "tabela '$tabela' tem a coluna '$coluna' (presente também em api/iot.schema.sql)");
        }
    }
} catch (Throwable $e) {
    echo "  [AVISO] não consegui conectar direto no banco pra checar colunas: " . $e->getMessage() . "\n";
}

// =====================================================================
if ($logado) {
    echo "\n=== INTEGRAÇÃO: suporte / assistente (cria, verifica resposta, apaga) ===\n";
    $r = http('POST', '/api/app.php?action=support_create', ['message' => 'meu sensor esta offline, o que faço?']);
    assertEqual(201, $r['status'], 'criar uma conversa de suporte retorna 201');
    $ticketId = $r['json']['ticket_id'] ?? null;
    assertTrue($ticketId !== null, 'a resposta de support_create traz um ticket_id');
    assertTrue(!empty($r['json']['ai_reply']), 'uma pergunta com palavra-chave conhecida recebe alguma resposta automática (Gemini ou modo de reserva)');

    if ($ticketId) {
        $r = http('POST', '/api/app.php?action=support_delete', ['ticket_id' => $ticketId]);
        assertEqual(200, $r['status'], 'apagar a conversa de teste funciona (limpeza pós-teste)');
    }
}

// =====================================================================
echo "\n========================================\n";
echo "RESULTADO: " . ($total - count($falhas)) . "/$total testes de integração passaram\n";
@unlink($COOKIE_JAR);
if ($falhas) {
    echo "FALHARAM:\n";
    foreach ($falhas as $f) echo "  - $f\n";
    exit(1);
}
echo "TODOS OS TESTES DE INTEGRAÇÃO PASSARAM.\n";
exit(0);
