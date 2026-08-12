<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../Controller/ProjectController.php';

$projectController = new ProjectController();
$projects = $projectController->getProjectsByUser($currentUser->getid());
$projectCount = count($projects);
$avatarSrc = avatarPath($currentUser);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Aeris Secure</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root { --bg: #04080f; --surface: rgba(7, 13, 24, 0.88); --surface-strong: rgba(10, 16, 29, 0.96); --border: rgba(255,255,255,0.08); --accent: #22c55e; --accent-soft: rgba(34,197,94,0.16); --text: #eef3f8; --muted: #9eb1c7; --radius: 28px; --shadow: 0 40px 80px rgba(4,10,20,0.38); --ease: cubic-bezier(0.4,0,0.2,1); }
    * { box-sizing: border-box; }
    html, body { margin: 0; min-height: 100%; font-family: 'Inter', sans-serif; color: var(--text); background: radial-gradient(circle at top, rgba(34,197,94,0.05), transparent 22%), linear-gradient(180deg, #02050a 0%, #060b14 100%); }
    body::before { content: ''; position: fixed; inset: 0; background: radial-gradient(circle at 18% 18%, rgba(34,197,94,0.12), transparent 14%), radial-gradient(circle at 82% 15%, rgba(34,197,94,0.08), transparent 10%); pointer-events: none; z-index: 0; }
    body { position: relative; }
    .page-shell { position: relative; z-index: 1; display: flex; flex-direction: column; gap: 22px; padding: 28px 28px 40px; }
    .topbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
    .brand { display: flex; align-items: center; gap: 12px; }
    .brand-circle { width: 48px; height: 48px; border-radius: 14px; background: rgba(34,197,94,0.14); display: grid; place-items: center; color: var(--accent); font-weight: 700; }
    .brand-title { display: flex; flex-direction: column; gap: 4px; }
    .brand-title span { font-size: 11px; letter-spacing: 0.24em; text-transform: uppercase; color: var(--muted); }
    .brand-title strong { font-size: 20px; font-family: 'Sora', sans-serif; }
    .header-panel { display: grid; gap: 20px; max-width: 960px; margin: 0 auto; }
    .header-copy { display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; }
    .header-copy h1 { font-size: clamp(34px, 4vw, 52px); margin: 0; line-height: 1.02; }
    .header-copy p { margin: 12px 0 0; max-width: 560px; color: var(--muted); line-height: 1.8; font-size: 15px; }
    .nav-actions { display: flex; align-items: center; gap: 14px; }
    .primary-button { border: 0; border-radius: 16px; padding: 14px 20px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #071f12; font-weight: 700; cursor: pointer; transition: transform 0.2s var(--ease), box-shadow 0.2s var(--ease); box-shadow: 0 20px 40px rgba(34,197,94,0.18); }
    .primary-button:hover { transform: translateY(-1px); }
    .profile-wrapper { position: relative; }
    .profile-trigger { width: 48px; height: 48px; border-radius: 18px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.05); display: grid; place-items: center; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .profile-trigger:hover { transform: translateY(-1px); }
    .profile-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
    .profile-initials { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 50%; background: rgba(34,197,94,0.16); color: var(--accent); font-weight: 700; }
    .profile-menu { position: absolute; right: 0; top: calc(100% + 14px); width: 300px; padding: 18px; border-radius: 24px; background: rgba(10,14,25,0.96); border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 30px 80px rgba(0,0,0,0.25); backdrop-filter: blur(26px); opacity: 0; visibility: hidden; transform: translateY(-12px); transition: opacity 0.28s var(--ease), transform 0.28s var(--ease), visibility 0.28s; z-index: 5; }
    .profile-menu.visible { opacity: 1; visibility: visible; transform: translateY(0); }
    .profile-card { display: grid; grid-template-columns: 44px 1fr; gap: 12px; align-items: center; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.08); }
    .profile-card-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; display: grid; place-items: center; background: rgba(34,197,94,0.16); color: var(--accent); font-weight: 700; }
    .profile-card-meta { min-width: 0; display: grid; gap: 6px; }
    .profile-card strong { font-size: 16px; overflow-wrap: anywhere; }
    .profile-card small { color: var(--muted); overflow-wrap: anywhere; }
    .account-status { display: inline-flex; align-items: center; gap: 7px; color: #bbf7d0; font-size: 12px; font-weight: 700; }
    .account-status::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--accent); }
    .profile-actions { display: grid; gap: 10px; margin-top: 16px; }
    .menu-link { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 16px; color: var(--text); text-decoration: none; background: rgba(255,255,255,0.03); transition: background 0.2s ease; }
    .menu-link:hover { background: rgba(34,197,94,0.12); }
    .menu-link span { font-size: 16px; }
    .content-grid { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(300px, 1fr); gap: 24px; width: 100%; max-width: 960px; margin: 0 auto; }
    .panel { border-radius: 32px; background: rgba(7, 13, 24, 0.96); border: 1px solid rgba(255,255,255,0.08); box-shadow: var(--shadow); padding: 28px; }
    .panel h2 { margin: 0 0 18px; font-size: 22px; }
    .panel p { color: var(--muted); line-height: 1.75; }
    .project-list { display: grid; gap: 18px; }
    .project-card { display: grid; gap: 14px; padding: 22px; border-radius: 24px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); }
    .project-card h3 { margin: 0; font-size: 18px; }
    .project-chip { display: inline-flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 999px; background: rgba(34,197,94,0.12); color: #c8f5d8; font-size: 13px; }
    .status-pill { padding: 8px 10px; border-radius: 999px; background: rgba(34,197,94,0.14); color: #ddf4e7; font-size: 13px; }
    .empty-state { display: grid; gap: 18px; justify-items: center; text-align: center; padding: 42px 24px; border-radius: 28px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); }
    .empty-state h2 { margin: 0; }
    .empty-state p { margin: 0; color: var(--muted); }
    @media (max-width: 900px) { .content-grid { grid-template-columns: 1fr; } }
    @media (max-width: 640px) { .page-shell { padding: 18px 16px 30px; } .header-copy { flex-direction: column; align-items: flex-start; gap: 16px; } .nav-actions { width: 100%; justify-content: flex-start; } .profile-menu { right: auto; left: 0; } }
  </style>
</head>
<body>
  <div class="page-shell">
    <div class="topbar">
      <div class="brand">
        <div class="brand-circle">A</div>
        <div class="brand-title">
          <span>Enterprise IoT Platform</span>
          <strong>Aeris Secure</strong>
        </div>
      </div>
      <div class="nav-actions">
        <button class="primary-button" onclick="location.href='project-connect.php'">Conectar projeto</button>
        <div class="profile-wrapper" id="profileWrapper">
          <div class="profile-trigger" id="profileTrigger" aria-haspopup="true" aria-expanded="false">
            <?php if ($avatarSrc): ?>
              <img src="<?php echo escapeHtml($avatarSrc); ?>" alt="Avatar" class="profile-avatar">
            <?php else: ?>
              <div class="profile-initials"><?php echo escapeHtml(userInitials($currentUser->getnome())); ?></div>
            <?php endif; ?>
          </div>
          <div class="profile-menu" id="profileMenu" role="menu" aria-label="Perfil do usuário">
            <div class="profile-card">
              <?php if ($avatarSrc): ?>
                <img src="<?php echo escapeHtml($avatarSrc); ?>" alt="Avatar" class="profile-card-avatar">
              <?php else: ?>
                <div class="profile-card-avatar"><?php echo escapeHtml(userInitials($currentUser->getnome())); ?></div>
              <?php endif; ?>
              <div class="profile-card-meta">
                <strong><?php echo escapeHtml($currentUser->getdisplayName() ?: $currentUser->getnome()); ?></strong>
                <small><?php echo escapeHtml($currentUser->getemail()); ?></small>
                <span class="account-status">Online</span>
              </div>
            </div>
            <div class="profile-actions">
              <a class="menu-link" href="profile.php"><span>👤</span> My Profile</a>
              <a class="menu-link" href="profile.php#settings"><span>⚙</span> Account Settings</a>
              <a class="menu-link" href="profile.php#security"><span>🔒</span> Security</a>
              <a class="menu-link" href="project-connect.php"><span>📊</span> Monitor Project</a>
              <a class="menu-link" href="logout.php"><span>🚪</span> Logout</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <section class="header-panel">
      <div class="header-copy">
        <div>
          <h1>Bem-vindo de volta, <?php echo escapeHtml(explode(' ', $currentUser->getnome())[0]); ?>.</h1>
          <p>Seu centro de controle Industrial IoT está ativo. Acompanhe seus dispositivos de gás e conecte seu primeiro projeto com confiança.</p>
        </div>
      </div>

      <div class="content-grid">
        <div class="panel">
          <h2>Resumo da conta</h2>
          <p>Você está autenticado com o email <?php echo escapeHtml($currentUser->getemail()); ?>. Conecte dispositivos e visualize o status em tempo real.</p>
        </div>

        <div class="panel">
          <h2>Atividades recentes</h2>
          <div class="status-card">
            <p style="margin:0;color:var(--muted);">Nenhum projeto conectado ainda? Comece com a configuração do seu primeiro dispositivo.</p>
          </div>
        </div>
      </div>

      <?php if ($projectCount > 0): ?>
        <div class="panel">
          <h2>Projetos conectados</h2>
          <div class="project-list">
            <?php foreach ($projects as $project): ?>
              <div class="project-card">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                  <h3><?php echo escapeHtml($project->getName()); ?></h3>
                  <span class="status-pill"><?php echo escapeHtml(ucfirst($project->getStatus())); ?></span>
                </div>
                <div style="display:grid;gap:10px;">
                  <span class="project-chip"><?php echo escapeHtml($project->getManufacturerCode()); ?></span>
                  <p style="margin:0;color:var(--muted);">Última comunicação: <?php echo $project->getLastOnline() ? date('d/m/Y H:i', strtotime($project->getLastOnline())) : 'Ainda não sincronizado'; ?></p>
                  <p style="margin:0;color:var(--muted);">Firmware: <?php echo escapeHtml($project->getFirmwareVersion() ?: 'Aguardando'); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="panel empty-state">
          <h2>Nenhum projeto conectado</h2>
          <p>Comece agora mesmo registrando seu primeiro dispositivo ESP32 e conectando-o ao seu ambiente.</p>
          <button class="primary-button" onclick="location.href='project-connect.php'">Conectar seu primeiro projeto</button>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <script>
    const wrapper = document.getElementById('profileWrapper');
    const trigger = document.getElementById('profileTrigger');
    const menu = document.getElementById('profileMenu');

    function openMenu() {
      menu.classList.add('visible');
      trigger.setAttribute('aria-expanded', 'true');
    }

    function closeMenu() {
      menu.classList.remove('visible');
      trigger.setAttribute('aria-expanded', 'false');
    }

    function toggleMenu() {
      menu.classList.contains('visible') ? closeMenu() : openMenu();
    }

    trigger.addEventListener('click', event => {
      event.stopPropagation();
      toggleMenu();
    });

    wrapper.addEventListener('mouseenter', openMenu);
    wrapper.addEventListener('mouseleave', closeMenu);

    document.addEventListener('click', event => {
      if (!menu.contains(event.target) && !trigger.contains(event.target)) {
        closeMenu();
      }
    });

    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        closeMenu();
      }
    });
  </script>
</body>
</html>
