<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../model/Connection.php';
session_start();

function out($status, $data)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function uid()
{
    foreach (['user_id', 'id', 'login_id'] as $k) {
        if (isset($_SESSION[$k]) && is_numeric($_SESSION[$k])) return (int)$_SESSION[$k];
    }
    return 0;
}
function body()
{
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
function setup($db)
{
    $db->exec("CREATE TABLE IF NOT EXISTS user_settings (
        user_id INT PRIMARY KEY,
        language VARCHAR(10) NOT NULL DEFAULT 'pt-BR',
        safe_ppm DECIMAL(10,2) NOT NULL DEFAULT 400,
        alert_ppm DECIMAL(10,2) NOT NULL DEFAULT 550,
        critical_ppm DECIMAL(10,2) NOT NULL DEFAULT 700,
        reading_interval INT NOT NULL DEFAULT 5,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        device_id INT DEFAULT NULL,
        type VARCHAR(40) NOT NULL,
        title VARCHAR(180) NOT NULL,
        message VARCHAR(500) NOT NULL,
        severity VARCHAR(20) NOT NULL DEFAULT 'info',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notifications_user (user_id, is_read, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        device_id INT DEFAULT NULL,
        action VARCHAR(80) NOT NULL,
        entity VARCHAR(80) DEFAULT NULL,
        entity_id VARCHAR(80) DEFAULT NULL,
        details TEXT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(500) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_audit_user_time (user_id, created_at),
        INDEX idx_audit_device_time (device_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS support_tickets (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        device_id INT DEFAULT NULL,
        subject VARCHAR(200) NOT NULL,
        category VARCHAR(80) NOT NULL DEFAULT 'Problema tecnico',
        status VARCHAR(30) NOT NULL DEFAULT 'aberto',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ticket_user (user_id, updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS support_messages (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ticket_id BIGINT NOT NULL,
        user_id INT DEFAULT NULL,
        sender_type VARCHAR(20) NOT NULL DEFAULT 'user',
        message TEXT NOT NULL,
        email_sent TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_support_ticket (ticket_id, created_at),
        CONSTRAINT fk_support_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS device_presence (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        esp32_id VARCHAR(64) NOT NULL,
        manufacturer_code VARCHAR(64) DEFAULT NULL,
        hostname VARCHAR(120) DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        wifi_rssi INT DEFAULT NULL,
        firmware_version VARCHAR(64) DEFAULT NULL,
        seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_presence_esp_time (esp32_id, seen_at),
        INDEX idx_presence_time (seen_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (
        [
            ['last_seen_ip', "VARCHAR(45) DEFAULT NULL"],
            ['last_seen_at', "DATETIME DEFAULT NULL"],
            ['last_alert_status', "VARCHAR(32) DEFAULT 'normal'"]
        ] as $c
    ) {
        $check = $db->query("SHOW COLUMNS FROM devices LIKE " . $db->quote($c[0]));
        if (!$check->fetch(PDO::FETCH_ASSOC)) $db->exec("ALTER TABLE devices ADD COLUMN {$c[0]} {$c[1]}");
    }
}
function audit($db, $action, $entity = null, $entityId = null, $details = null, $userId = null, $deviceId = null)
{
    $stmt = $db->prepare("INSERT INTO audit_logs (user_id,device_id,action,entity,entity_id,details,ip_address,user_agent) VALUES (:u,:d,:a,:e,:ei,:details,:ip,:ua)");
    $stmt->execute([
        ':u' => $userId ?: null,
        ':d' => $deviceId ?: null,
        ':a' => $action,
        ':e' => $entity,
        ':ei' => $entityId === null ? null : (string)$entityId,
        ':details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
    ]);
}
if (!defined('SUPPORT_EMAIL')) define('SUPPORT_EMAIL', 'COLOQUE_SEU_EMAIL_DE_SUPORTE');
function mailSupport($to, $subject, $message, $replyTo = null)
{
    if (!$to || strpos($to, 'COLOQUE_SEU_EMAIL') !== false) return false;
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: Aeris Guard <no-reply@localhost>\r\n";
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $headers .= "Reply-To: {$replyTo}\r\n";
    return @mail($to, 'Aeris Guard - ' . $subject, $message, $headers);
}

$db = Connection::getConnection();
setup($db);
$action = $_GET['action'] ?? '';
$userId = uid();

if ($action === 'announce') {
    $data = body();
    $esp = trim($data['esp32_id'] ?? '');
    if ($esp === '') out(422, ['success' => false, 'message' => 'esp32_id obrigatório.']);
    $ip = trim($data['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''));
    $stmt = $db->prepare("INSERT INTO device_presence (esp32_id,manufacturer_code,hostname,ip_address,wifi_rssi,firmware_version) VALUES (:esp,:code,:host,:ip,:rssi,:fw)");
    $stmt->execute([
        ':esp' => $esp,
        ':code' => trim($data['manufacturer_code'] ?? ''),
        ':host' => trim($data['hostname'] ?? ''),
        ':ip' => $ip,
        ':rssi' => isset($data['rssi']) ? (int)$data['rssi'] : null,
        ':fw' => trim($data['firmware_version'] ?? '')
    ]);
    $find = $db->prepare("SELECT id FROM devices WHERE esp32_id=:esp LIMIT 1");
    $find->execute([':esp' => $esp]);
    $deviceId = $find->fetchColumn();
    if ($deviceId) {
        $up = $db->prepare("UPDATE devices SET status='online',wifi_status=:wifi,last_online=NOW(),last_seen_ip=:ip,last_seen_at=NOW(),updated_at=NOW() WHERE id=:id");
        $up->execute([':wifi' => isset($data['rssi']) ? 'RSSI ' . (int)$data['rssi'] . ' dBm' : 'WiFi', ':ip' => $ip, ':id' => $deviceId]);
    }
    out(200, ['success' => true, 'registered' => (bool)$deviceId, 'device_id' => $deviceId ? (int)$deviceId : null]);
}

/*
 * Varre a rede local (mesma sub-rede /24 da máquina que roda o
 * backend) procurando ESP32 da Aeris, batendo no endpoint local
 * /info que o firmware expõe (ver esp32-firmware/esp32-firmware.ino).
 * Isso encontra dispositivos ligados na rede mesmo que ainda não
 * tenham enviado nenhum anúncio para o servidor.
 *
 * Limitação real: só funciona se o PC e o ESP32 estiverem na MESMA
 * rede Wi-Fi/sub-rede, e se o roteador não tiver "isolamento de
 * clientes" (AP isolation) ativado — comum em Wi-Fi de escola/evento.
 * Nesse caso, peça para desativar o isolamento ou use um roteador à
 * parte para a demonstração.
 */
/*
 * Descobre as sub-redes IPv4 privadas (RFC1918) em que esta máquina
 * está presente. No Apache/XAMPP, $_SERVER['SERVER_ADDR'] já resolve
 * isso sozinho; isso aqui é um reforço para quando ele vem vazio
 * (acontece no servidor embutido do PHP quando ligado em 0.0.0.0).
 */
function subnetsDestaMaquina()
{
    $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';

    if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $serverAddr) && $serverAddr !== '127.0.0.1') {
        $p = explode('.', $serverAddr);
        return ["{$p[0]}.{$p[1]}.{$p[2]}."];
    }

    if (!function_exists('net_get_interfaces')) {
        return [];
    }

    $subnets = [];

    foreach (net_get_interfaces() as $iface) {
        if (empty($iface['up'])) {
            continue;
        }

        foreach ($iface['unicast'] ?? [] as $addr) {
            $ip = $addr['address'] ?? '';

            $isPrivada =
                preg_match('/^10\./', $ip) ||
                preg_match('/^192\.168\./', $ip) ||
                preg_match('/^172\.(1[6-9]|2\d|3[0-1])\./', $ip);

            if ($isPrivada) {
                $p = explode('.', $ip);
                $subnets["{$p[0]}.{$p[1]}.{$p[2]}."] = true;
            }
        }
    }

    return array_keys($subnets);
}

function scanLanForAerisDevices()
{
    $subnets = subnetsDestaMaquina();

    if (empty($subnets)) {
        return [];
    }

    $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
    $mh = curl_multi_init();
    $handles = [];

    foreach ($subnets as $subnetBase) {
        for ($i = 1; $i <= 254; $i++) {
            $ip = $subnetBase . $i;
            if ($ip === $serverAddr) {
                continue;
            }

            $ch = curl_init("http://{$ip}/info");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT_MS => 250,
                CURLOPT_TIMEOUT_MS => 400,
                CURLOPT_FAILONERROR => false,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$ip] = $ch;
        }
    }

    $running = null;
    do {
        curl_multi_exec($mh, $running);
        if ($running > 0) {
            curl_multi_select($mh, 0.2);
        }
    } while ($running > 0);

    $found = [];

    foreach ($handles as $ip => $ch) {
        $body = curl_multi_getcontent($ch);

        if ($body) {
            $data = json_decode($body, true);

            if (is_array($data) && !empty($data['esp32_id'])) {
                $found[$data['esp32_id']] = [
                    'esp32_id' => $data['esp32_id'],
                    'manufacturer_code' => $data['manufacturer_code'] ?? null,
                    'hostname' => $data['hostname'] ?? null,
                    'ip_address' => $ip,
                    'wifi_rssi' => $data['rssi'] ?? null,
                    'firmware_version' => $data['firmware_version'] ?? null,
                    'seen_at' => date('Y-m-d H:i:s'),
                    'via' => 'scan_ao_vivo'
                ];
            }
        }

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);

    return $found;
}

if ($action === 'discover') {
    if (!$userId) out(401, ['success' => false, 'message' => 'Usuário não autenticado.']);

    $stmt = $db->prepare("SELECT p.esp32_id,p.manufacturer_code,p.hostname,p.ip_address,p.wifi_rssi,p.firmware_version,p.seen_at,d.id AS device_id,d.name,d.location_id FROM device_presence p LEFT JOIN devices d ON d.esp32_id=p.esp32_id AND d.user_id=:uid WHERE p.seen_at>=DATE_SUB(NOW(),INTERVAL 30 SECOND) ORDER BY p.seen_at DESC");
    $stmt->execute([':uid' => $userId]);

    $seen = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $r['via'] = 'anuncio_recente';
        $seen[$r['esp32_id']] = $r;
    }

    // Varredura ao vivo da rede local; não sobrescreve um anúncio
    // recente já encontrado (que tem mais dados, como device_id).
    foreach (scanLanForAerisDevices() as $esp32Id => $device) {
        if (!isset($seen[$esp32Id])) {
            $seen[$esp32Id] = $device;
        }
    }

    out(200, ['success' => true, 'devices' => array_values($seen)]);
}

if (!$userId) out(401, ['success' => false, 'message' => 'Usuário não autenticado.']);

if ($action === 'profile') {
    $q = $db->prepare("SELECT id,nome,email,username,display_name,avatar,phone,biography,language,timezone FROM login WHERE id=:id LIMIT 1");
    $q->execute([':id' => $userId]);
    out(200, ['success' => true, 'profile' => $q->fetch(PDO::FETCH_ASSOC)]);
}
if ($action === 'save_profile') {
    $d = body();
    $nome = trim($d['nome'] ?? '');
    $display = trim($d['display_name'] ?? '');
    $avatar = trim($d['avatar'] ?? '');
    $phone = trim($d['phone'] ?? '');
    $bio = trim($d['biography'] ?? '');
    if ($nome === '') out(422, ['success' => false, 'message' => 'Nome obrigatório.']);
    $s = $db->prepare("UPDATE login SET nome=:nome,display_name=:display_name,avatar=:avatar,phone=:phone,biography=:bio,updated_at=NOW() WHERE id=:id");
    $s->execute([':nome' => $nome, ':display_name' => $display, ':avatar' => $avatar, ':phone' => $phone, ':bio' => $bio, ':id' => $userId]);
    $_SESSION['usuario'] = $nome;
    audit($db, 'profile_updated', 'login', $userId, $d, $userId);
    out(200, ['success' => true, 'message' => 'Perfil atualizado.']);
}
if ($action === 'settings') {
    $s = $db->prepare("SELECT user_id,language,safe_ppm,alert_ppm,critical_ppm,reading_interval FROM user_settings WHERE user_id=:u");
    $s->execute([':u' => $userId]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $db->prepare("INSERT INTO user_settings(user_id) VALUES(:u)")->execute([':u' => $userId]);
        $s->execute([':u' => $userId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
    }
    $n = $db->prepare("SELECT notify_email,notify_security,notify_system,notify_project,notify_alerts FROM login WHERE id=:u");
    $n->execute([':u' => $userId]);
    out(200, ['success' => true, 'settings' => $row, 'notifications' => $n->fetch(PDO::FETCH_ASSOC)]);
}
if ($action === 'save_settings') {
    $d = body();
    $lang = in_array($d['language'] ?? 'pt-BR', ['pt-BR', 'en', 'es'], true) ? $d['language'] : 'pt-BR';
    $safe = max(0, (float)($d['safe_ppm'] ?? 400));
    $alert = max($safe, (float)($d['alert_ppm'] ?? 550));
    $critical = max($alert, (float)($d['critical_ppm'] ?? 700));
    $interval = max(1, min(3600, (int)($d['reading_interval'] ?? 5)));
    $db->prepare("INSERT INTO user_settings(user_id,language,safe_ppm,alert_ppm,critical_ppm,reading_interval) VALUES(:u,:l,:s,:a,:c,:i) ON DUPLICATE KEY UPDATE language=VALUES(language),safe_ppm=VALUES(safe_ppm),alert_ppm=VALUES(alert_ppm),critical_ppm=VALUES(critical_ppm),reading_interval=VALUES(reading_interval)")->execute([':u' => $userId, ':l' => $lang, ':s' => $safe, ':a' => $alert, ':c' => $critical, ':i' => $interval]);
    $db->prepare("UPDATE login SET language=:l,notify_email=:e,notify_security=:sec,notify_system=:sys,notify_project=:proj,notify_alerts=:al,updated_at=NOW() WHERE id=:u")->execute([':l' => $lang, ':e' => !empty($d['notify_email']) ? 1 : 0, ':sec' => !empty($d['notify_security']) ? 1 : 0, ':sys' => !empty($d['notify_system']) ? 1 : 0, ':proj' => !empty($d['notify_project']) ? 1 : 0, ':al' => !empty($d['notify_alerts']) ? 1 : 0, ':u' => $userId]);
    audit($db, 'settings_updated', 'user_settings', $userId, $d, $userId);
    out(200, ['success' => true, 'message' => 'Configurações salvas.']);
}
if ($action === 'notifications') {
    $s = $db->prepare("SELECT id,type,title,message,severity,is_read,created_at FROM notifications WHERE user_id=:u ORDER BY created_at DESC,id DESC LIMIT 50");
    $s->execute([':u' => $userId]);
    out(200, ['success' => true, 'notifications' => $s->fetchAll(PDO::FETCH_ASSOC)]);
}
if ($action === 'read_notifications') {
    $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=:u")->execute([':u' => $userId]);
    audit($db, 'notifications_read', null, null, null, $userId);
    out(200, ['success' => true]);
}
if ($action === 'support') {
    $sub = $action;
}
if ($action === 'support_list') {
    $s = $db->prepare("SELECT id,subject,category,status,created_at,updated_at FROM support_tickets WHERE user_id=:u ORDER BY updated_at DESC");
    $s->execute([':u' => $userId]);
    out(200, ['success' => true, 'tickets' => $s->fetchAll(PDO::FETCH_ASSOC)]);
}
if ($action === 'support_messages') {
    $ticket = (int)($_GET['ticket_id'] ?? 0);
    $s = $db->prepare("SELECT t.* FROM support_tickets t WHERE t.id=:id AND t.user_id=:u");
    $s->execute([':id' => $ticket, ':u' => $userId]);
    if (!$s->fetch()) out(404, ['success' => false, 'message' => 'Chamado não encontrado.']);
    $m = $db->prepare("SELECT id,sender_type,message,email_sent,created_at FROM support_messages WHERE ticket_id=:t ORDER BY created_at,id");
    $m->execute([':t' => $ticket]);
    out(200, ['success' => true, 'messages' => $m->fetchAll(PDO::FETCH_ASSOC)]);
}
if ($action === 'support_create') {
    $d = body();
    $subject = trim($d['subject'] ?? '');
    $message = trim($d['message'] ?? '');
    $category = trim($d['category'] ?? 'Problema técnico');
    $deviceId = (int)($d['device_id'] ?? 0) ?: null;
    if ($subject === '' || $message === '') out(422, ['success' => false, 'message' => 'Assunto e mensagem são obrigatórios.']);
    $db->beginTransaction();
    try {
        $t = $db->prepare("INSERT INTO support_tickets(user_id,device_id,subject,category) VALUES(:u,:d,:s,:c)");
        $t->execute([':u' => $userId, ':d' => $deviceId, ':s' => $subject, ':c' => $category]);
        $ticketId = (int)$db->lastInsertId();
        $m = $db->prepare("INSERT INTO support_messages(ticket_id,user_id,sender_type,message) VALUES(:t,:u,'user',:m)");
        $m->execute([':t' => $ticketId, ':u' => $userId, ':m' => $message]);
        $emailQ = $db->prepare("SELECT email,nome FROM login WHERE id=:u");
        $emailQ->execute([':u' => $userId]);
        $user = $emailQ->fetch(PDO::FETCH_ASSOC);
        $to = SUPPORT_EMAIL;
        $sent = mailSupport($to, 'Chamado #AG-' . $ticketId . ' - ' . $subject, $message, $user['email'] ?? null);
        $db->prepare("UPDATE support_messages SET email_sent=:s WHERE id=:id")->execute([':s' => $sent ? 1 : 0, ':id' => $db->lastInsertId()]);
        $db->commit();
        audit($db, 'support_ticket_created', 'support_tickets', $ticketId, ['subject' => $subject, 'email_sent' => $sent], $userId, $deviceId);
        out(201, ['success' => true, 'ticket_id' => $ticketId, 'email_sent' => $sent, 'message' => 'Chamado registrado.']);
    } catch (Throwable $e) {
        $db->rollBack();
        out(500, ['success' => false, 'message' => 'Não foi possível abrir o chamado: ' . $e->getMessage()]);
    }
}
if ($action === 'support_send') {
    $d = body();
    $ticket = (int)($d['ticket_id'] ?? 0);
    $message = trim($d['message'] ?? '');
    if (!$ticket || $message === '') out(422, ['success' => false, 'message' => 'Chamado e mensagem são obrigatórios.']);
    $q = $db->prepare("SELECT t.id,t.subject,l.email FROM support_tickets t JOIN login l ON l.id=t.user_id WHERE t.id=:t AND t.user_id=:u");
    $q->execute([':t' => $ticket, ':u' => $userId]);
    $t = $q->fetch(PDO::FETCH_ASSOC);
    if (!$t) out(404, ['success' => false, 'message' => 'Chamado não encontrado.']);
    $m = $db->prepare("INSERT INTO support_messages(ticket_id,user_id,sender_type,message) VALUES(:t,:u,'user',:m)");
    $m->execute([':t' => $ticket, ':u' => $userId, ':m' => $message]);
    $mid = (int)$db->lastInsertId();
    $sent = mailSupport(SUPPORT_EMAIL, 'Chamado #AG-' . $ticket . ' - nova mensagem', $message, $t['email']);
    $db->prepare("UPDATE support_messages SET email_sent=:s WHERE id=:id")->execute([':s' => $sent ? 1 : 0, ':id' => $mid]);
    audit($db, 'support_message_sent', 'support_messages', $mid, ['ticket_id' => $ticket, 'email_sent' => $sent], $userId);
    out(201, ['success' => true, 'email_sent' => $sent]);
}
if ($action === 'audit') {
    $s = $db->prepare("SELECT id,action,entity,entity_id,details,created_at FROM audit_logs WHERE user_id=:u ORDER BY created_at DESC,id DESC LIMIT 300");
    $s->execute([':u' => $userId]);
    out(200, ['success' => true, 'logs' => $s->fetchAll(PDO::FETCH_ASSOC)]);
}
out(404, ['success' => false, 'message' => 'Ação não encontrada.']);
