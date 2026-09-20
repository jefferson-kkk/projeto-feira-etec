<?php

/*
 * Canal ao vivo (Server-Sent Events) para o PPM do dispositivo.
 *
 * Em vez do navegador PERGUNTAR "tem novidade?" a cada poucos
 * milissegundos (polling), essa conexão fica aberta e o SERVIDOR
 * AVISA assim que uma leitura nova chega no banco — sem esperar o
 * próximo ciclo do navegador.
 *
 * Só funciona com um servidor que atenda várias requisições ao
 * mesmo tempo (Caddy + php-cgi neste projeto). No servidor embutido
 * do PHP (`php -S`), que é de um processo só, essa conexão aberta
 * travaria todo o resto do site — por isso essa troca de servidor
 * veio junto com esse recurso.
 */

require_once __DIR__ . '/../model/Connection.php';
require_once __DIR__ . '/../model/DeviceDAO.php';

session_start();

function getCurrentUserId()
{
    foreach (['user_id', 'id', 'login_id'] as $key) {
        if (isset($_SESSION[$key]) && is_numeric($_SESSION[$key]) && (int)$_SESSION[$key] > 0) {
            return (int)$_SESSION[$key];
        }
    }
    return 0;
}

$userId = getCurrentUserId();

if ($userId <= 0) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

$deviceId = filter_input(INPUT_GET, 'device_id', FILTER_VALIDATE_INT);

if (!$deviceId || $deviceId <= 0) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'device_id obrigatório.']);
    exit;
}

/*
 * Libera a sessão assim que confirmamos quem é o usuário. Sessões
 * do PHP usam um arquivo travado (lock) enquanto abertas — se não
 * soltarmos aqui, essa conexão longa bloquearia OUTRAS abas/telas
 * do MESMO usuário até o stream acabar.
 */
session_write_close();

$deviceDAO = new DeviceDAO(false);
$device = $deviceDAO->getById($deviceId, $userId);

if (!$device) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Dispositivo não encontrado.']);
    exit;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

while (ob_get_level() > 0) {
    ob_end_flush();
}

set_time_limit(0);

$conn = $deviceDAO->getConnection();
$stmt = $conn->prepare("
    SELECT id, ppm, raw_adc, reading_status, wifi_rssi, created_at
    FROM sensor_readings
    WHERE device_id = :device_id
    ORDER BY id DESC
    LIMIT 1
");

$lastId = 0;
$deadline = time() + 55; // o EventSource do navegador reconecta sozinho quando cai

echo ": conectado\n\n";
flush();

while (time() < $deadline) {
    if (connection_aborted()) {
        exit;
    }

    $stmt->execute([':device_id' => $deviceId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && (int)$row['id'] !== $lastId) {
        $lastId = (int)$row['id'];

        // Status de conexão (online/offline) recalculado a cada evento.
        $statusStmt = $conn->prepare("
            SELECT status, TIMESTAMPDIFF(SECOND, last_seen_at, NOW()) AS seconds_since_seen
            FROM devices WHERE id = :id LIMIT 1
        ");
        $statusStmt->execute([':id' => $deviceId]);
        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);
        $connectionStatus = $statusRow
            ? DeviceDAO::effectiveStatus($statusRow['status'], $statusRow['seconds_since_seen'])
            : 'offline';

        $payload = json_encode([
            'id' => $row['id'],
            'ppm' => (float)$row['ppm'],
            'raw_adc' => $row['raw_adc'] !== null ? (int)$row['raw_adc'] : null,
            'reading_status' => $row['reading_status'],
            'wifi_rssi' => $row['wifi_rssi'] !== null ? (int)$row['wifi_rssi'] : null,
            'created_at' => $row['created_at'],
            'device_status' => $connectionStatus,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        echo "event: reading\n";
        echo "data: {$payload}\n\n";
        flush();
    } else {
        // Comentário SSE (linha começando com ":") mantém a conexão viva
        // sem disparar nenhum evento no navegador.
        echo ": ping\n\n";
        flush();
    }

    usleep(120000); // ~120ms: rápido o bastante para parecer instantâneo,
                     // sem martelar o MySQL sem necessidade.
}
