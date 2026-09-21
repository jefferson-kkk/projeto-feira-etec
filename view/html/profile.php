<?php
require_once __DIR__ . '/_auth.php';

function jsonResponse($ok, $message, $extra = [])
{
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
  exit;
}

function normalizeText($value, $max = 255)
{
  $value = trim((string) $value);
  return substr($value, 0, $max);
}

function splitName($name)
{
  $parts = preg_split('/\s+/', trim($name));
  $first = $parts[0] ?? '';
  $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
  return [$first, $last];
}

function uploadDirPath()
{
  return __DIR__ . '/../../uploads/avatars';
}

function deleteAvatarFile($filename)
{
  if (!$filename) {
    return;
  }

  $path = uploadDirPath() . '/' . basename($filename);
  if (is_file($path)) {
    unlink($path);
  }
}

function saveAvatarUpload($currentUser)
{
  if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'Selecione uma imagem válida.');
  }

  $file = $_FILES['avatar'];
  if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(false, 'A imagem deve ter no máximo 5 MB.');
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($file['tmp_name']);
  $allowed = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp',
  ];

  if (!isset($allowed[$mime]) || !is_uploaded_file($file['tmp_name'])) {
    jsonResponse(false, 'Formato não permitido. Use PNG, JPG, JPEG ou WEBP.');
  }

  $dir = uploadDirPath();
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }

  $filename = 'avatar_' . $currentUser->getid() . '_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
  $destination = $dir . '/' . $filename;

  if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(false, 'Não foi possível salvar a imagem.');
  }

  deleteAvatarFile($currentUser->getavatar());
  return $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  try {
    if ($action === 'update_profile') {
      $firstName = normalizeText($_POST['first_name'] ?? '', 80);
      $lastName = normalizeText($_POST['last_name'] ?? '', 120);
      $displayName = normalizeText($_POST['display_name'] ?? '', 160);
      $email = normalizeText($_POST['email'] ?? '', 180);
      $phone = normalizeText($_POST['phone'] ?? '', 32);
      $biography = normalizeText($_POST['biography'] ?? '', 1000);
      $language = normalizeText($_POST['language'] ?? 'pt-BR', 16);
      $timezone = normalizeText($_POST['timezone'] ?? 'America/Sao_Paulo', 64);

      if ($firstName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Informe nome e e-mail válido.');
      }

      $existingEmailUser = $controller->getLoginByEmail($email);
      if ($existingEmailUser && (int) $existingEmailUser->getid() !== (int) $currentUser->getid()) {
        jsonResponse(false, 'Este e-mail já está em uso.');
      }

      $fullName = trim($firstName . ' ' . $lastName);
      $updatedUser = new Login(
        $fullName,
        $currentUser->getsenha(),
        $email,
        $currentUser->getdatacriacao(),
        $currentUser->getavatar(),
        $currentUser->getid(),
        $currentUser->getusername(),
        $displayName !== '' ? $displayName : $fullName,
        $phone !== '' ? $phone : null,
        $biography !== '' ? $biography : null,
        $language,
        $timezone,
        $currentUser->getLastLogin(),
        $currentUser->getemailVerified(),
        $currentUser->getnotifyEmail(),
        $currentUser->getnotifySecurity(),
        $currentUser->getnotifySystem(),
        $currentUser->getnotifyProject(),
        $currentUser->getnotifyAlerts(),
        $currentUser->getcreatedAt(),
        date('Y-m-d H:i:s')
      );

      $currentUser = $controller->atualizarPerfil($updatedUser);
      $_SESSION['usuario'] = $currentUser->getnome();
      jsonResponse(true, 'Perfil atualizado com sucesso.', [
        'name' => $currentUser->getnome(),
        'display_name' => $currentUser->getdisplayName(),
        'email' => $currentUser->getemail(),
        'phone' => $currentUser->getphone(),
        'biography' => $currentUser->getbiography(),
        'language' => $currentUser->getlanguage(),
        'timezone' => $currentUser->gettimezone(),
      ]);
    }

    if ($action === 'change_password') {
      $currentPassword = (string) ($_POST['current_password'] ?? '');
      $newPassword = (string) ($_POST['new_password'] ?? '');
      $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

      if (!password_verify($currentPassword, $currentUser->getsenha()) && $currentPassword !== $currentUser->getsenha()) {
        jsonResponse(false, 'Senha atual incorreta.');
      }

      if (strlen($newPassword) < 8 || $newPassword !== $confirmPassword) {
        jsonResponse(false, 'A nova senha deve ter ao menos 8 caracteres e confirmação igual.');
      }

      $controller->atualizarSenha($currentUser->getid(), $newPassword);
      jsonResponse(true, 'Senha alterada com sucesso.');
    }

    if ($action === 'update_notifications') {
      $prefs = [
        'notify_email' => isset($_POST['notify_email']) ? 1 : 0,
        'notify_security' => isset($_POST['notify_security']) ? 1 : 0,
        'notify_system' => isset($_POST['notify_system']) ? 1 : 0,
        'notify_project' => isset($_POST['notify_project']) ? 1 : 0,
        'notify_alerts' => isset($_POST['notify_alerts']) ? 1 : 0,
      ];

      $currentUser = $controller->atualizarNotificacoes($currentUser->getid(), $prefs);
      jsonResponse(true, 'Preferências salvas com sucesso.');
    }

    if ($action === 'upload_avatar') {
      $filename = saveAvatarUpload($currentUser);
      $updatedUser = new Login(
        $currentUser->getnome(),
        $currentUser->getsenha(),
        $currentUser->getemail(),
        $currentUser->getdatacriacao(),
        $filename,
        $currentUser->getid(),
        $currentUser->getusername(),
        $currentUser->getdisplayName(),
        $currentUser->getphone(),
        $currentUser->getbiography(),
        $currentUser->getlanguage(),
        $currentUser->gettimezone(),
        $currentUser->getLastLogin(),
        $currentUser->getemailVerified(),
        $currentUser->getnotifyEmail(),
        $currentUser->getnotifySecurity(),
        $currentUser->getnotifySystem(),
        $currentUser->getnotifyProject(),
        $currentUser->getnotifyAlerts(),
        $currentUser->getcreatedAt(),
        date('Y-m-d H:i:s')
      );
      $currentUser = $controller->atualizarPerfil($updatedUser);
      jsonResponse(true, 'Foto atualizada com sucesso.', ['avatar' => '../../uploads/avatars/' . $filename]);
    }

    if ($action === 'delete_avatar') {
      deleteAvatarFile($currentUser->getavatar());
      $updatedUser = new Login(
        $currentUser->getnome(),
        $currentUser->getsenha(),
        $currentUser->getemail(),
        $currentUser->getdatacriacao(),
        null,
        $currentUser->getid(),
        $currentUser->getusername(),
        $currentUser->getdisplayName(),
        $currentUser->getphone(),
        $currentUser->getbiography(),
        $currentUser->getlanguage(),
        $currentUser->gettimezone(),
        $currentUser->getLastLogin(),
        $currentUser->getemailVerified(),
        $currentUser->getnotifyEmail(),
        $currentUser->getnotifySecurity(),
        $currentUser->getnotifySystem(),
        $currentUser->getnotifyProject(),
        $currentUser->getnotifyAlerts(),
        $currentUser->getcreatedAt(),
        date('Y-m-d H:i:s')
      );
      $currentUser = $controller->atualizarPerfil($updatedUser);
      jsonResponse(true, 'Imagem removida.', ['initials' => userInitials($currentUser->getnome())]);
    }

    if ($action === 'refresh_session') {
      session_regenerate_id(true);
      jsonResponse(true, 'Sessão atual protegida e renovada.');
    }

    if ($action === 'logout_all') {
      session_regenerate_id(true);
      jsonResponse(true, 'Outras sessões foram encerradas. Sua sessão atual foi mantida.');
    }
  } catch (Exception $e) {
    jsonResponse(false, 'Erro ao processar solicitação: ' . $e->getMessage());
  }

  jsonResponse(false, 'Ação inválida.');
}

$avatarSrc = avatarPath($currentUser);
[$firstName, $lastName] = splitName($currentUser->getnome());
$memberSince = $currentUser->getcreatedAt() ?: $currentUser->getdatacriacao();
$lastLogin = $currentUser->getLastLogin();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Navegador não identificado';
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'IP não disponível';
$browser = 'Navegador atual';
$os = 'Sistema atual';

if (stripos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
elseif (stripos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
elseif (stripos($userAgent, 'Safari') !== false) $browser = 'Safari';
elseif (stripos($userAgent, 'Edg') !== false) $browser = 'Edge';

if (stripos($userAgent, 'Windows') !== false) $os = 'Windows';
elseif (stripos($userAgent, 'Mac OS') !== false) $os = 'macOS';
elseif (stripos($userAgent, 'Linux') !== false) $os = 'Linux';
elseif (stripos($userAgent, 'Android') !== false) $os = 'Android';
elseif (stripos($userAgent, 'iPhone') !== false) $os = 'iOS';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Perfil — Sadag</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      color-scheme: dark;
      --bg: #04080f;
      --surface: rgba(7, 13, 24, 0.94);
      --surface-2: rgba(255, 255, 255, 0.04);
      --border: rgba(255, 255, 255, 0.08);
      --accent: #22c55e;
      --accent-soft: rgba(34, 197, 94, 0.14);
      --text: #eef3f8;
      --muted: #9eb1c7;
      --danger: #f87171;
      --radius: 24px;
      --ease: cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
      box-sizing: border-box;
    }

    html,
    body {
      margin: 0;
      min-height: 100%;
    }

    body {
      font-family: 'Inter', sans-serif;
      color: var(--text);
      background: radial-gradient(circle at top, rgba(34, 197, 94, 0.06), transparent 22%), linear-gradient(180deg, #02050a 0%, #060b14 100%);
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: radial-gradient(circle at 18% 16%, rgba(34, 197, 94, 0.12), transparent 14%);
      pointer-events: none;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    .page {
      position: relative;
      z-index: 1;
      width: min(1120px, calc(100% - 32px));
      margin: 0 auto;
      padding: 28px 0 44px;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      margin-bottom: 24px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .brand-circle {
      width: 44px;
      height: 44px;
      border-radius: 14px;
      display: grid;
      place-items: center;
      background: var(--accent-soft);
      color: var(--accent);
      font-weight: 800;
    }

    .brand span {
      display: block;
      color: var(--muted);
      font-size: 12px;
      letter-spacing: .16em;
      text-transform: uppercase;
    }

    .brand strong,
    h1,
    h2 {
      font-family: 'Sora', sans-serif;
    }

    .top-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .button {
      border: 0;
      border-radius: 14px;
      padding: 12px 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: linear-gradient(135deg, #22c55e, #16a34a);
      color: #071f12;
      font-weight: 800;
      cursor: pointer;
      transition: transform .2s var(--ease), opacity .2s var(--ease);
    }

    .button:hover {
      transform: translateY(-1px);
    }

    .button.secondary {
      background: rgba(255, 255, 255, 0.06);
      color: var(--text);
      border: 1px solid var(--border);
    }

    .button.danger {
      background: rgba(248, 113, 113, 0.12);
      color: #fecaca;
      border: 1px solid rgba(248, 113, 113, 0.2);
    }

    .button[disabled] {
      opacity: .62;
      cursor: wait;
      transform: none;
    }

    .hero {
      display: grid;
      grid-template-columns: 320px minmax(0, 1fr);
      gap: 22px;
      align-items: stretch;
      margin-bottom: 22px;
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 24px;
      box-shadow: 0 30px 80px rgba(0, 0, 0, .24);
      backdrop-filter: blur(24px);
    }

    .identity {
      display: grid;
      justify-items: center;
      text-align: center;
      gap: 14px;
    }

    .avatar-wrap {
      position: relative;
      width: 168px;
      height: 168px;
      border: 1px solid rgba(255, 255, 255, .12);
      border-radius: 50%;
      overflow: hidden;
      background: rgba(34, 197, 94, .14);
      display: grid;
      place-items: center;
      cursor: pointer;
    }

    .avatar-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .avatar-initials {
      font-size: 46px;
      font-weight: 800;
      color: var(--accent);
    }

    .avatar-overlay {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      background: rgba(2, 6, 12, .62);
      color: #fff;
      opacity: 0;
      transition: opacity .2s var(--ease);
      font-weight: 800;
    }

    .avatar-wrap:hover .avatar-overlay {
      opacity: 1;
    }

    .identity h1 {
      margin: 8px 0 0;
      font-size: 28px;
    }

    .identity p {
      margin: 0;
      color: var(--muted);
    }

    .online {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #bdf7d0;
      background: var(--accent-soft);
      padding: 7px 12px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 700;
    }

    .online::before {
      content: '';
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--accent);
      box-shadow: 0 0 0 5px rgba(34, 197, 94, .1);
    }

    .summary {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }

    .summary-item {
      background: var(--surface-2);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 18px;
    }

    .summary-item span {
      display: block;
      color: var(--muted);
      font-size: 13px;
      margin-bottom: 8px;
    }

    .summary-item strong {
      font-size: 15px;
      overflow-wrap: anywhere;
    }

    .tabs {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin: 22px 0;
    }

    .tab {
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, .04);
      color: var(--muted);
      padding: 11px 14px;
      border-radius: 14px;
      cursor: pointer;
      font-weight: 800;
    }

    .tab.active {
      color: var(--text);
      background: var(--accent-soft);
      border-color: rgba(34, 197, 94, .28);
    }

    .section {
      display: none;
    }

    .section.active {
      display: grid;
      gap: 18px;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .full {
      grid-column: 1 / -1;
    }

    .field label {
      display: block;
      margin-bottom: 8px;
      color: var(--muted);
      font-size: 13px;
      font-weight: 700;
    }

    input,
    textarea,
    select {
      width: 100%;
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, .08);
      background: rgba(255, 255, 255, .05);
      color: var(--text);
      padding: 13px 14px;
      font: inherit;
      outline: none;
    }

    textarea {
      min-height: 110px;
      resize: vertical;
    }

    input:focus,
    textarea:focus,
    select:focus {
      border-color: rgba(34, 197, 94, .55);
      box-shadow: 0 0 0 5px rgba(34, 197, 94, .08);
    }

    .info-list {
      display: grid;
      gap: 12px;
    }

    .info-row {
      display: grid;
      grid-template-columns: 180px 1fr;
      gap: 12px;
      padding: 14px 0;
      border-bottom: 1px solid var(--border);
    }

    .info-row:last-child {
      border-bottom: 0;
    }

    .label {
      color: var(--muted);
    }

    .value {
      overflow-wrap: anywhere;
    }

    .switch-list {
      display: grid;
      gap: 12px;
    }

    .switch-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 16px;
      border: 1px solid var(--border);
      border-radius: 18px;
      background: var(--surface-2);
    }

    .switch-row strong {
      display: block;
    }

    .switch-row span {
      color: var(--muted);
      font-size: 13px;
    }

    .switch-row input {
      width: 20px;
      height: 20px;
      accent-color: var(--accent);
    }

    .strength {
      height: 8px;
      border-radius: 999px;
      background: rgba(255, 255, 255, .08);
      overflow: hidden;
    }

    .strength span {
      display: block;
      height: 100%;
      width: 0;
      background: var(--danger);
      transition: width .2s var(--ease), background .2s var(--ease);
    }

    .session-card {
      display: grid;
      gap: 12px;
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 18px;
      background: var(--surface-2);
    }

    .toast {
      position: fixed;
      right: 22px;
      bottom: 22px;
      z-index: 30;
      min-width: 260px;
      max-width: min(420px, calc(100vw - 32px));
      padding: 14px 16px;
      border-radius: 16px;
      border: 1px solid rgba(34, 197, 94, .24);
      background: rgba(7, 13, 24, .96);
      color: var(--text);
      box-shadow: 0 24px 60px rgba(0, 0, 0, .3);
      opacity: 0;
      transform: translateY(12px);
      pointer-events: none;
      transition: opacity .24s var(--ease), transform .24s var(--ease);
    }

    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    .toast.error {
      border-color: rgba(248, 113, 113, .28);
      color: #fecaca;
    }

    .viewer,
    .cropper {
      position: fixed;
      inset: 0;
      z-index: 40;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(1, 4, 9, .78);
      backdrop-filter: blur(14px);
      padding: 24px;
    }

    .viewer.open,
    .cropper.open {
      display: flex;
    }

    .viewer img {
      max-width: min(88vw, 920px);
      max-height: 82vh;
      border-radius: 20px;
      cursor: grab;
      transition: transform .18s var(--ease);
      box-shadow: 0 40px 100px rgba(0, 0, 0, .5);
    }

    .close {
      position: fixed;
      top: 22px;
      right: 22px;
      width: 44px;
      height: 44px;
      border-radius: 50%;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, .08);
      color: var(--text);
      cursor: pointer;
      font-size: 22px;
    }

    .crop-card {
      width: min(560px, 100%);
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 22px;
      padding: 20px;
      display: grid;
      gap: 16px;
    }

    .crop-stage {
      position: relative;
      width: 100%;
      aspect-ratio: 1;
      overflow: hidden;
      border-radius: 18px;
      background: #02050a;
      border: 1px solid var(--border);
      cursor: grab;
    }

    .crop-stage img {
      position: absolute;
      top: 50%;
      left: 50%;
      max-width: none;
      transform-origin: center;
      user-select: none;
      pointer-events: none;
    }

    .crop-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      flex-wrap: wrap;
    }

    .hidden-file {
      display: none;
    }

    @media (max-width: 860px) {

      .hero,
      .grid {
        grid-template-columns: 1fr;
      }

      .summary {
        grid-template-columns: 1fr;
      }

      .info-row {
        grid-template-columns: 1fr;
      }

      .page {
        width: min(100% - 24px, 1120px);
        padding-top: 18px;
      }

      .topbar {
        align-items: flex-start;
        flex-direction: column;
      }
    }
  </style>
</head>

<body>
  <main class="page">
    <div class="topbar">
      <a class="brand" href="home.php">
        <div class="brand-circle">S</div>
        <div><span>Monitoramento IoT</span><strong>Sadag</strong></div>
      </a>
      <div class="top-actions">
        <a class="button secondary" href="home.php">Voltar ao dashboard</a>
        <a class="button secondary" href="project-connect.php">Monitor Project</a>
        <a class="button danger" href="logout.php">Logout</a>
      </div>
    </div>

    <section class="hero">
      <div class="card identity">
        <div class="avatar-wrap" id="avatarWrap" title="Abrir imagem">
          <?php if ($avatarSrc): ?>
            <img id="avatarImage" src="<?php echo escapeHtml($avatarSrc); ?>" alt="Foto de perfil">
          <?php else: ?>
            <div id="avatarInitials" class="avatar-initials"><?php echo escapeHtml(userInitials($currentUser->getnome())); ?></div>
          <?php endif; ?>
          <button class="avatar-overlay" id="changePhoto" type="button">📷 Change Photo</button>
        </div>
        <input class="hidden-file" id="avatarInput" type="file" accept="image/png,image/jpeg,image/webp">
        <div>
          <span class="online">Online</span>
          <h1 id="profileName"><?php echo escapeHtml($currentUser->getdisplayName() ?: $currentUser->getnome()); ?></h1>
          <p><?php echo escapeHtml($currentUser->getemail()); ?></p>
        </div>
        <button class="button secondary" id="deletePhoto" type="button">Remover imagem</button>
      </div>

      <div class="card">
        <div class="summary">
          <div class="summary-item"><span>Usuário</span><strong>@<?php echo escapeHtml($currentUser->getusername()); ?></strong></div>
          <div class="summary-item"><span>Membro desde</span><strong><?php echo date('d/m/Y', strtotime($memberSince)); ?></strong></div>
          <div class="summary-item"><span>Último login</span><strong><?php echo $lastLogin ? date('d/m/Y H:i', strtotime($lastLogin)) : 'Primeiro acesso'; ?></strong></div>
        </div>

        <div class="tabs" role="tablist">
          <button class="tab active" type="button" data-tab="profile">Meu perfil</button>
          <button class="tab" type="button" data-tab="settings">Account Settings</button>
          <button class="tab" type="button" data-tab="security">Security</button>
          <button class="tab" type="button" data-tab="sessions">Sessões</button>
          <button class="tab" type="button" data-tab="notifications">Notificações</button>
        </div>

        <section class="section active" id="profile">
          <div class="info-list">
            <div class="info-row">
              <div class="label">Nome completo</div>
              <div class="value"><?php echo escapeHtml($currentUser->getnome()); ?></div>
            </div>
            <div class="info-row">
              <div class="label">Username</div>
              <div class="value">@<?php echo escapeHtml($currentUser->getusername()); ?></div>
            </div>
            <div class="info-row">
              <div class="label">E-mail</div>
              <div class="value"><?php echo escapeHtml($currentUser->getemail()); ?></div>
            </div>
            <div class="info-row">
              <div class="label">Telefone</div>
              <div class="value"><?php echo escapeHtml($currentUser->getphone() ?: 'Não informado'); ?></div>
            </div>
            <div class="info-row">
              <div class="label">Conta criada em</div>
              <div class="value"><?php echo date('d/m/Y H:i', strtotime($currentUser->getdatacriacao())); ?></div>
            </div>
            <div class="info-row">
              <div class="label">Membro desde</div>
              <div class="value"><?php echo date('d/m/Y', strtotime($memberSince)); ?></div>
            </div>
          </div>
        </section>

        <section class="section" id="settings">
          <form class="ajax-form grid" data-action="update_profile">
            <div class="field"><label>First Name</label><input name="first_name" value="<?php echo escapeHtml($firstName); ?>" required></div>
            <div class="field"><label>Last Name</label><input name="last_name" value="<?php echo escapeHtml($lastName); ?>"></div>
            <div class="field"><label>Display Name</label><input name="display_name" value="<?php echo escapeHtml($currentUser->getdisplayName()); ?>"></div>
            <div class="field"><label>Email</label><input type="email" name="email" value="<?php echo escapeHtml($currentUser->getemail()); ?>" required></div>
            <div class="field"><label>Phone</label><input name="phone" value="<?php echo escapeHtml($currentUser->getphone()); ?>"></div>
            <div class="field"><label>Language</label><select name="language">
                <option value="pt-BR" <?php echo $currentUser->getlanguage() === 'pt-BR' ? 'selected' : ''; ?>>Português (Brasil)</option>
                <option value="en" <?php echo $currentUser->getlanguage() === 'en' ? 'selected' : ''; ?>>English</option>
                <option value="es" <?php echo $currentUser->getlanguage() === 'es' ? 'selected' : ''; ?>>Español</option>
              </select></div>
            <div class="field full"><label>Timezone</label><select name="timezone">
                <option value="America/Sao_Paulo" <?php echo $currentUser->gettimezone() === 'America/Sao_Paulo' ? 'selected' : ''; ?>>America/Sao_Paulo</option>
                <option value="UTC" <?php echo $currentUser->gettimezone() === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                <option value="America/New_York" <?php echo $currentUser->gettimezone() === 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
              </select></div>
            <div class="field full"><label>Biography</label><textarea name="biography"><?php echo escapeHtml($currentUser->getbiography()); ?></textarea></div>
            <div class="full"><button class="button" type="submit">Salvar alterações</button></div>
          </form>
        </section>

        <section class="section" id="security">
          <form class="ajax-form grid" data-action="change_password">
            <div class="field full"><label>Current password</label><input type="password" name="current_password" required></div>
            <div class="field"><label>New password</label><input id="newPassword" type="password" name="new_password" required minlength="8"></div>
            <div class="field"><label>Confirm password</label><input type="password" name="confirm_password" required minlength="8"></div>
            <div class="field full"><label>Força da senha</label>
              <div class="strength"><span id="strengthBar"></span></div>
            </div>
            <div class="full top-actions"><button class="button secondary" type="button" id="togglePasswords">Mostrar/Ocultar</button><button class="button" type="submit">Alterar senha</button></div>
          </form>
        </section>

        <section class="section" id="sessions">
          <div class="session-card">
            <strong>Dispositivo atual</strong>
            <div class="info-list">
              <div class="info-row">
                <div class="label">Browser</div>
                <div class="value"><?php echo escapeHtml($browser); ?></div>
              </div>
              <div class="info-row">
                <div class="label">Sistema operacional</div>
                <div class="value"><?php echo escapeHtml($os); ?></div>
              </div>
              <div class="info-row">
                <div class="label">IP Address</div>
                <div class="value"><?php echo escapeHtml($ipAddress); ?></div>
              </div>
              <div class="info-row">
                <div class="label">Última atividade</div>
                <div class="value"><?php echo date('d/m/Y H:i'); ?></div>
              </div>
            </div>
            <div class="top-actions">
              <button class="button secondary action-button" data-action="refresh_session" type="button">Manter sessão atual</button>
              <button class="button danger action-button" data-action="logout_all" type="button">Logout from all devices</button>
            </div>
          </div>
        </section>

        <section class="section" id="notifications">
          <form class="ajax-form" data-action="update_notifications">
            <div class="switch-list">
              <label class="switch-row"><span><strong>Email notifications</strong><span>Resumo e avisos importantes por e-mail.</span></span><input type="checkbox" name="notify_email" <?php echo $currentUser->getnotifyEmail() ? 'checked' : ''; ?>></label>
              <label class="switch-row"><span><strong>Security alerts</strong><span>Alertas de senha, login e proteção da conta.</span></span><input type="checkbox" name="notify_security" <?php echo $currentUser->getnotifySecurity() ? 'checked' : ''; ?>></label>
              <label class="switch-row"><span><strong>System updates</strong><span>Atualizações da plataforma Sadag.</span></span><input type="checkbox" name="notify_system" <?php echo $currentUser->getnotifySystem() ? 'checked' : ''; ?>></label>
              <label class="switch-row"><span><strong>Project notifications</strong><span>Mudanças e eventos dos projetos conectados.</span></span><input type="checkbox" name="notify_project" <?php echo $currentUser->getnotifyProject() ? 'checked' : ''; ?>></label>
              <label class="switch-row"><span><strong>Future gas alerts</strong><span>Alertas futuros de risco e concentração de gás.</span></span><input type="checkbox" name="notify_alerts" <?php echo $currentUser->getnotifyAlerts() ? 'checked' : ''; ?>></label>
            </div>
            <div style="margin-top:16px;"><button class="button" type="submit">Salvar preferências</button></div>
          </form>
        </section>
      </div>
    </section>
  </main>

  <div class="viewer" id="viewer">
    <button class="close" type="button" data-close-viewer>×</button>
    <?php if ($avatarSrc): ?><img id="viewerImage" src="<?php echo escapeHtml($avatarSrc); ?>" alt="Foto de perfil ampliada"><?php endif; ?>
  </div>

  <div class="cropper" id="cropper">
    <div class="crop-card">
      <strong>Ajustar foto</strong>
      <div class="crop-stage" id="cropStage"><img id="cropImage" alt="Prévia da foto"></div>
      <label>Zoom <input id="zoomRange" type="range" min="1" max="3" value="1" step="0.05"></label>
      <div class="crop-actions">
        <button class="button secondary" type="button" id="cancelCrop">Cancelar</button>
        <button class="button" type="button" id="saveCrop">Salvar foto</button>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script>
    const tabs = document.querySelectorAll('.tab');
    const sections = document.querySelectorAll('.section');
    const toast = document.getElementById('toast');
    const avatarWrap = document.getElementById('avatarWrap');
    const avatarInput = document.getElementById('avatarInput');
    const changePhoto = document.getElementById('changePhoto');
    const deletePhoto = document.getElementById('deletePhoto');
    const viewer = document.getElementById('viewer');
    const cropper = document.getElementById('cropper');
    const cropStage = document.getElementById('cropStage');
    const cropImage = document.getElementById('cropImage');
    const zoomRange = document.getElementById('zoomRange');
    let viewerScale = 1;
    let viewerX = 0;
    let viewerY = 0;
    let cropScale = 1;
    let cropX = 0;
    let cropY = 0;
    let dragState = null;

    function showToast(message, error = false) {
      toast.textContent = message;
      toast.classList.toggle('error', error);
      toast.classList.add('show');
      clearTimeout(showToast.timer);
      showToast.timer = setTimeout(() => toast.classList.remove('show'), 3200);
    }

    async function postForm(form) {
      const button = form.querySelector('button[type="submit"]');
      const data = new FormData(form);
      data.append('action', form.dataset.action);
      if (button) button.disabled = true;
      const response = await fetch(location.href, {
        method: 'POST',
        body: data
      });
      const result = await response.json();
      if (button) button.disabled = false;
      showToast(result.message, !result.ok);
      if (result.ok && result.display_name) document.getElementById('profileName').textContent = result.display_name;
      return result;
    }

    function activateTab(tabName) {
      const selected = document.querySelector(`.tab[data-tab="${tabName}"]`) || document.querySelector('.tab[data-tab="profile"]');
      tabs.forEach(item => item.classList.toggle('active', item === selected));
      sections.forEach(section => section.classList.toggle('active', section.id === selected.dataset.tab));
    }

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        activateTab(tab.dataset.tab);
        history.replaceState(null, '', '#' + tab.dataset.tab);
      });
    });

    if (location.hash) {
      activateTab(location.hash.replace('#', ''));
    }

    document.querySelectorAll('.ajax-form').forEach(form => {
      form.addEventListener('submit', event => {
        event.preventDefault();
        postForm(form).catch(() => showToast('Não foi possível completar a ação.', true));
      });
    });

    document.querySelectorAll('.action-button').forEach(button => {
      button.addEventListener('click', async () => {
        button.disabled = true;
        const data = new FormData();
        data.append('action', button.dataset.action);
        try {
          const response = await fetch(location.href, {
            method: 'POST',
            body: data
          });
          const result = await response.json();
          showToast(result.message, !result.ok);
        } catch (error) {
          showToast('Não foi possível completar a ação.', true);
        }
        button.disabled = false;
      });
    });

    document.getElementById('togglePasswords').addEventListener('click', () => {
      document.querySelectorAll('#security input[type="password"], #security input[type="text"]').forEach(input => {
        input.type = input.type === 'password' ? 'text' : 'password';
      });
    });

    document.getElementById('newPassword').addEventListener('input', event => {
      const value = event.target.value;
      let score = Math.min(4, (value.length >= 8) + /[A-Z]/.test(value) + /[0-9]/.test(value) + /[^A-Za-z0-9]/.test(value));
      const bar = document.getElementById('strengthBar');
      bar.style.width = (score * 25) + '%';
      bar.style.background = score < 2 ? '#f87171' : score < 4 ? '#f0a93a' : '#22c55e';
    });

    changePhoto.addEventListener('click', event => {
      event.stopPropagation();
      avatarInput.click();
    });

    avatarInput.addEventListener('change', () => {
      const file = avatarInput.files[0];
      if (!file) return;
      if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
        showToast('Use PNG, JPG, JPEG ou WEBP com até 5 MB.', true);
        return;
      }

      const url = URL.createObjectURL(file);
      cropImage.src = url;
      cropScale = 1;
      cropX = 0;
      cropY = 0;
      zoomRange.value = 1;
      cropper.classList.add('open');
      updateCropTransform();
    });

    function updateCropTransform() {
      cropImage.style.transform = `translate(calc(-50% + ${cropX}px), calc(-50% + ${cropY}px)) scale(${cropScale})`;
      cropImage.style.width = '100%';
      cropImage.style.height = '100%';
      cropImage.style.objectFit = 'cover';
    }

    zoomRange.addEventListener('input', () => {
      cropScale = parseFloat(zoomRange.value);
      updateCropTransform();
    });

    cropStage.addEventListener('pointerdown', event => {
      dragState = {
        x: event.clientX,
        y: event.clientY,
        startX: cropX,
        startY: cropY
      };
      cropStage.setPointerCapture(event.pointerId);
    });

    cropStage.addEventListener('pointermove', event => {
      if (!dragState) return;
      cropX = dragState.startX + event.clientX - dragState.x;
      cropY = dragState.startY + event.clientY - dragState.y;
      updateCropTransform();
    });

    cropStage.addEventListener('pointerup', () => {
      dragState = null;
    });

    document.getElementById('cancelCrop').addEventListener('click', () => cropper.classList.remove('open'));

    document.getElementById('saveCrop').addEventListener('click', () => {
      const size = 640;
      const canvas = document.createElement('canvas');
      canvas.width = size;
      canvas.height = size;
      const ctx = canvas.getContext('2d');
      const image = cropImage;
      const stage = cropStage.getBoundingClientRect();
      const scale = cropScale * Math.max(size / image.naturalWidth, size / image.naturalHeight);
      const drawWidth = image.naturalWidth * scale;
      const drawHeight = image.naturalHeight * scale;
      const dx = (size - drawWidth) / 2 + cropX * (size / stage.width);
      const dy = (size - drawHeight) / 2 + cropY * (size / stage.height);
      ctx.drawImage(image, dx, dy, drawWidth, drawHeight);
      canvas.toBlob(async blob => {
        const data = new FormData();
        data.append('action', 'upload_avatar');
        data.append('avatar', blob, 'avatar.webp');
        try {
          const response = await fetch(location.href, {
            method: 'POST',
            body: data
          });
          const result = await response.json();
          showToast(result.message, !result.ok);
          if (result.ok) {
            cropper.classList.remove('open');
            setAvatar(result.avatar + '?t=' + Date.now());
          }
        } catch (error) {
          showToast('Não foi possível enviar a foto.', true);
        }
      }, 'image/webp', 0.92);
    });

    deletePhoto.addEventListener('click', async () => {
      const data = new FormData();
      data.append('action', 'delete_avatar');
      try {
        const response = await fetch(location.href, {
          method: 'POST',
          body: data
        });
        const result = await response.json();
        showToast(result.message, !result.ok);
        if (result.ok) setInitials(result.initials || 'U');
      } catch (error) {
        showToast('Não foi possível remover a imagem.', true);
      }
    });

    function setAvatar(src) {
      avatarWrap.querySelector('#avatarInitials')?.remove();
      let image = document.getElementById('avatarImage');
      if (!image) {
        image = document.createElement('img');
        image.id = 'avatarImage';
        image.alt = 'Foto de perfil';
        avatarWrap.insertBefore(image, changePhoto);
      }
      image.src = src;
      let viewerImage = document.getElementById('viewerImage');
      if (!viewerImage) {
        viewerImage = document.createElement('img');
        viewerImage.id = 'viewerImage';
        viewerImage.alt = 'Foto de perfil ampliada';
        viewer.appendChild(viewerImage);
      }
      viewerImage.src = src;
    }

    function setInitials(initials) {
      document.getElementById('avatarImage')?.remove();
      document.getElementById('viewerImage')?.remove();
      let initialBox = document.getElementById('avatarInitials');
      if (!initialBox) {
        initialBox = document.createElement('div');
        initialBox.id = 'avatarInitials';
        initialBox.className = 'avatar-initials';
        avatarWrap.insertBefore(initialBox, changePhoto);
      }
      initialBox.textContent = initials;
    }

    function updateViewerTransform() {
      const image = document.getElementById('viewerImage');
      if (image) image.style.transform = `translate(${viewerX}px, ${viewerY}px) scale(${viewerScale})`;
    }

    avatarWrap.addEventListener('click', event => {
      if (event.target === changePhoto || !document.getElementById('viewerImage')) return;
      viewerScale = 1;
      viewerX = 0;
      viewerY = 0;
      updateViewerTransform();
      viewer.classList.add('open');
    });

    viewer.addEventListener('click', event => {
      if (event.target === viewer || event.target.hasAttribute('data-close-viewer')) viewer.classList.remove('open');
    });

    viewer.addEventListener('wheel', event => {
      event.preventDefault();
      viewerScale = Math.max(1, Math.min(4, viewerScale + (event.deltaY < 0 ? .12 : -.12)));
      updateViewerTransform();
    }, {
      passive: false
    });

    viewer.addEventListener('pointerdown', event => {
      if (event.target.id !== 'viewerImage') return;
      dragState = {
        x: event.clientX,
        y: event.clientY,
        startX: viewerX,
        startY: viewerY
      };
      event.target.setPointerCapture(event.pointerId);
    });

    viewer.addEventListener('pointermove', event => {
      if (!dragState || event.target.id !== 'viewerImage') return;
      viewerX = dragState.startX + event.clientX - dragState.x;
      viewerY = dragState.startY + event.clientY - dragState.y;
      updateViewerTransform();
    });

    viewer.addEventListener('pointerup', () => {
      dragState = null;
    });

    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        viewer.classList.remove('open');
        cropper.classList.remove('open');
      }
    });
  </script>
</body>

</html>