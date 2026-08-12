<?php
require_once __DIR__ . '/../../Controller/Controller.php';

session_start();

$redirectDir = rtrim(dirname($_SERVER['PHP_SELF']), '/');
if ($redirectDir === '') {
    $redirectDir = '/';
}
$dashboardPage = $redirectDir . '/home.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . $dashboardPage);
    exit;
}

$controller = new LoginController();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';

    if ($tipo === 'login') {
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');

        $usuario = $controller->getLoginByEmail($email);
        $isValidPassword = false;
        $needsRehash = false;

        if ($usuario) {
            $storedPassword = $usuario->getsenha();
            if (password_verify($senha, $storedPassword)) {
                $isValidPassword = true;
                if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                    $needsRehash = true;
                }
            } elseif ($senha === $storedPassword) {
                // Registro antigo com senha em texto puro; atualiza para hash seguro
                $isValidPassword = true;
                $needsRehash = true;
            }
        }

        if ($isValidPassword) {
            if ($needsRehash) {
                $controller->atualizarSenha($usuario->getid(), $senha);
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $usuario->getid();
            $_SESSION['usuario'] = $usuario->getnome();
            $controller->atualizarUltimoLogin($usuario->getid());
            header('Location: ' . $dashboardPage);
            exit;
        }

        $erro = 'E-mail ou senha inválidos.';
    } elseif ($tipo === 'cadastro') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        $data = date('Y-m-d H:i:s');

        if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 6) {
            $erro = 'Informe nome, e-mail válido e uma senha com pelo menos 6 caracteres.';
        } else {
        try {
            $novoUsuario = $controller->criarLogin($nome, $senha, $email, $data);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $novoUsuario->getid();
            $_SESSION['usuario'] = $novoUsuario->getnome();
            $controller->atualizarUltimoLogin($novoUsuario->getid());
            header('Location: ' . $dashboardPage);
            exit;
        } catch (Exception $e) {
            $erro = 'Erro ao cadastrar: ' . $e->getMessage();
        }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aeris — Autenticação corporativa</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      color-scheme: dark;
      --bg: #05070d;
      --surface: rgba(8, 15, 28, 0.84);
      --surface-strong: rgba(7, 12, 22, 0.92);
      --surface-glow: rgba(34, 197, 94, 0.12);
      --border: rgba(255, 255, 255, 0.08);
      --text: #edf2f7;
      --text-muted: #a3b1c7;
      --accent: #22c55e;
      --accent-soft: rgba(34, 197, 94, 0.22);
      --shadow: 0 45px 120px rgba(2, 7, 18, 0.45);
      --radius: 26px;
      --ease: cubic-bezier(0.4, 0, 0.2, 1);
    }

    * { box-sizing: border-box; }
    html, body { margin: 0; min-height: 100%; }
    body { font-family: 'Inter', sans-serif; color: var(--text); background: radial-gradient(circle at top, rgba(34,197,94,0.04), transparent 22%), linear-gradient(180deg, #03050b 0%, #070b13 100%); overflow-x: hidden; }
    body::after {
      content: '';
      position: fixed;
      inset: 0;
      background: radial-gradient(circle at 20% 18%, rgba(34,197,94,0.12), transparent 14%), radial-gradient(circle at 85% 15%, rgba(34,197,94,0.08), transparent 10%);
      pointer-events: none;
      z-index: 0;
    }

    .page-shell {
      position: relative;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 28px 16px;
      z-index: 1;
    }

    canvas#background-canvas { position: fixed; inset: 0; width: 100%; height: 100%; z-index: 0; }

    .auth-card {
      position: relative;
      z-index: 2;
      width: min(520px, 95vw);
      padding: 40px 34px 34px;
      border-radius: var(--radius);
      background: rgba(8, 15, 28, 0.88);
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      backdrop-filter: blur(28px);
      overflow: hidden;
      min-height: auto;
      max-width: 520px;
    }

    .auth-card::before {
      content: '';
      position: absolute;
      inset: -100px;
      background: radial-gradient(circle at 50% 20%, rgba(34,197,94,0.14), transparent 24%);
      filter: blur(34px);
      pointer-events: none;
      z-index: 0;
    }

    .auth-inner {
      position: relative;
      z-index: 1;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 24px;
    }

    .brand-badge {
      display: grid;
      place-items: center;
      width: 50px;
      height: 50px;
      border-radius: 18px;
      border: 1px solid rgba(34,197,94,0.2);
      background: rgba(34,197,94,0.08);
      color: var(--accent);
      font-size: 20px;
      font-weight: 700;
      backdrop-filter: blur(12px);
    }

    .brand-title span { font-size: 12px; letter-spacing: 0.22em; text-transform: uppercase; color: var(--text-muted); }
    .brand-title strong { font-size: 22px; font-family: 'Sora', sans-serif; letter-spacing: -0.03em; }

    .panel-header { margin-bottom: 12px; }
    .panel-header h1 { font-size: clamp(30px, 4vw, 36px); margin: 0; line-height: 1.08; }

    .panel-copy { max-width: 460px; margin-bottom: 26px; color: var(--text-muted); line-height: 1.75; font-size: 15px; }

    .tab-list {
      position: relative;
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 999px;
      padding: 6px;
      margin-bottom: 26px;
      overflow: hidden;
    }

    .tab-indicator {
      position: absolute;
      top: 6px;
      left: 6px;
      width: calc(50% - 10px);
      height: calc(100% - 12px);
      border-radius: 999px;
      background: rgba(34,197,94,0.18);
      transition: transform 0.38s var(--ease), width 0.38s var(--ease);
      pointer-events: none;
    }

    .tab-button {
      position: relative;
      z-index: 1;
      background: transparent;
      border: 0;
      color: var(--text-muted);
      padding: 18px 16px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: color 0.3s var(--ease);
    }

    .tab-button.active { color: #f8fff7; }

    .tab-button:focus-visible { outline: 2px solid rgba(34,197,94,0.35); outline-offset: 3px; }

    .form-shell { position: relative; min-height: 360px; }

    .form-panel { position: absolute; inset: 0; opacity: 0; transform: translateX(24px); transition: opacity 0.38s var(--ease), transform 0.38s var(--ease); pointer-events: none; display: grid; gap: 16px; }

    .form-panel.hidden { display: none; }
    .form-panel.visible { opacity: 1; transform: translateX(0); pointer-events: auto; }

    .form-panel.outgoing { opacity: 0; transform: translateX(-24px); pointer-events: none; }

    .input-group { margin-bottom: 18px; }
    .input-group label { display: block; margin-bottom: 10px; color: var(--text-muted); font-size: 13px; font-weight: 600; }

    .input-control { width: 100%; border-radius: 18px; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.05); color: var(--text); font-size: 15px; padding: 16px 18px; transition: border-color 0.25s ease, box-shadow 0.25s ease, background 0.2s ease; backdrop-filter: blur(12px); }

    .input-control:focus { outline: none; border-color: rgba(34,197,94,0.55); box-shadow: 0 0 0 6px rgba(34,197,94,0.08); background: rgba(255,255,255,0.08); }

    .form-meta { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; color: var(--text-muted); font-size: 13px; }

    .form-meta a { color: var(--accent); text-decoration: none; }
    .form-meta a:hover { text-decoration: underline; }

    .submit-button { width: 100%; border: 0; border-radius: 18px; padding: 16px 18px; font-size: 15px; font-weight: 700; color: #071f12; background: linear-gradient(135deg, #22c55e, #16a34a); cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease; box-shadow: 0 20px 40px rgba(34,197,94,0.18); }

    .submit-button:hover { transform: translateY(-1px); filter: brightness(1.03); }

    .status-bar { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 24px; }

    .status-chip { padding: 12px 14px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.04); color: var(--text-muted); font-size: 12px; display: flex; align-items: center; justify-content: center; }

    .status-chip strong { color: var(--text); margin-right: 6px; font-weight: 700; }

    .erro { margin-bottom: 18px; color: #f87171; font-weight: 600; }

    @media (max-width: 740px) {
      .auth-card { padding: 30px 20px 28px; min-height: 640px; width: min(100%, 100%); }
      .panel-header { flex-direction: column; align-items: flex-start; gap: 12px; }
      .form-shell { min-height: 320px; }
      .tab-button { padding: 14px 10px; font-size: 14px; }
      .status-bar { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="page-shell">
    <canvas id="background-canvas"></canvas>

    <div class="auth-card" role="main" aria-labelledby="auth-title">
      <div class="auth-inner">
        <div class="brand">
          <div class="brand-badge">A</div>
          <div class="brand-title">
            <span>Enterprise IoT</span>
            <strong>Aeris Secure</strong>
          </div>
        </div>

        <div class="panel-header">
          <h1 id="auth-title">Acesso seguro ao painel</h1>
        </div>

        <p class="panel-copy">A plataforma conecta redes de sensores e qualidade de gás com uma experiência premium, segura e pronta para empresas modernas.</p>

        <?php if (!empty($erro)) echo '<p class="erro">' . htmlspecialchars($erro) . '</p>'; ?>

        <div class="tab-list" role="tablist" aria-label="Modo de autenticação">
          <span class="tab-indicator"></span>
          <button class="tab-button active" data-panel="login" type="button" role="tab" aria-selected="true">Login</button>
          <button class="tab-button" data-panel="create" type="button" role="tab" aria-selected="false">Criar conta</button>
        </div>

        <div class="form-shell">
          <div id="login" class="form-panel visible" aria-hidden="false">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
              <input type="hidden" name="tipo" value="login">
              <div class="input-group">
                <label for="email-login">E-mail corporativo</label>
                <input id="email-login" type="email" name="email" class="input-control" placeholder="nome@empresa.com" required>
              </div>
              <div class="input-group">
                <label for="senha-login">Senha</label>
                <input id="senha-login" type="password" name="senha" class="input-control" placeholder="••••••••" required>
              </div>
              <div class="form-meta">
                <span></span>
                <a href="#">Esqueceu a senha?</a>
              </div>
              <button type="submit" class="submit-button">Entrar</button>
            </form>
          </div>

          <div id="create" class="form-panel hidden" aria-hidden="true">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
              <input type="hidden" name="tipo" value="cadastro">
              <div class="input-group">
                <label for="nome-create">Nome completo</label>
                <input id="nome-create" type="text" name="nome" class="input-control" placeholder="Seu nome" required>
              </div>
              <div class="input-group">
                <label for="email-create">E-mail</label>
                <input id="email-create" type="email" name="email" class="input-control" placeholder="seu@empresa.com" required>
              </div>
              <div class="input-group">
                <label for="senha-create">Senha</label>
                <input id="senha-create" type="password" name="senha" class="input-control" placeholder="••••••••" required>
              </div>
              <div class="form-meta"><small>Ao continuar, você aceita os termos de uso.</small></div>
              <button type="submit" class="submit-button">Criar conta</button>
            </form>
          </div>
        </div>

        <div class="status-bar">
          <div class="status-chip"><strong>24/7</strong> monitoring</div>
          <div class="status-chip"><strong>IoT</strong> sensor network</div>
          <div class="status-chip"><strong>AI</strong> predictive alerts</div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const tabs = document.querySelectorAll('.tab-button');
    const panels = document.querySelectorAll('.form-panel');
    const indicator = document.querySelector('.tab-indicator');
    const formShell = document.querySelector('.form-shell');

    function updateIndicator(activeTab) {
      const rect = activeTab.getBoundingClientRect();
      const parentRect = activeTab.parentElement.getBoundingClientRect();
      indicator.style.transform = `translateX(${rect.left - parentRect.left}px)`;
      indicator.style.width = `${rect.width}px`;
    }

    function activateTab(tab) {
      tabs.forEach(button => {
        const panel = document.getElementById(button.dataset.panel);
        const isActive = button === tab;
        button.classList.toggle('active', isActive);
        button.setAttribute('aria-selected', isActive);
        if (isActive) {
          panel.classList.remove('hidden', 'outgoing');
          panel.classList.add('visible');
          setTimeout(() => {
            panel.querySelector('input').focus();
          }, 320);
        } else if (!panel.classList.contains('hidden')) {
          panel.classList.add('outgoing');
          panel.classList.remove('visible');
          setTimeout(() => {
            panel.classList.add('hidden');
          }, 380);
        }
      });
      updateIndicator(tab);
    }

    tabs.forEach(tab => {
      tab.addEventListener('click', () => activateTab(tab));
    });

    window.addEventListener('load', () => updateIndicator(document.querySelector('.tab-button.active')));
    window.addEventListener('resize', () => updateIndicator(document.querySelector('.tab-button.active')));

    const canvas = document.getElementById('background-canvas');
    const ctx = canvas.getContext('2d');
    let width = window.innerWidth;
    let height = window.innerHeight;
    const deviceFactor = Math.min(1, (navigator.hardwareConcurrency || 4) / 8);
    let particleCount = 180;
    const particles = [];
    const nodes = [];
    const links = [];
    const waves = [];
    const pointer = { x: width / 2, y: height / 2, active: false };
    const maxLinks = 3;

    function resizeCanvas() {
      width = window.innerWidth;
      height = window.innerHeight;
      const dpr = window.devicePixelRatio || 1;
      canvas.width = width * dpr;
      canvas.height = height * dpr;
      canvas.style.width = width + 'px';
      canvas.style.height = height + 'px';
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      particleCount = Math.max(120, Math.min(220, Math.round(width * height / 1600 * deviceFactor)));
      while (particles.length < particleCount) particles.push(createParticle());
      while (particles.length > particleCount) particles.pop();
      while (nodes.length < 12) nodes.push(createNode());
      while (nodes.length > 12) nodes.pop();
    }

    function createParticle() {
      const radius = Math.random() * 1.6 + 0.7;
      return {
        type: 'dot',
        x: Math.random() * width,
        y: Math.random() * height,
        vx: (Math.random() - 0.5) * 0.24,
        vy: (Math.random() - 0.5) * 0.24,
        r: radius,
        baseAlpha: 0.12 + Math.random() * 0.25,
        alpha: 0,
        phase: Math.random() * Math.PI * 2,
        glow: Math.random() * 0.02 + 0.01
      };
    }

    function createNode() {
      const nodeSize = Math.random() * 3.5 + 2.8;
      return {
        type: 'node',
        x: Math.random() * width,
        y: Math.random() * height,
        vx: (Math.random() - 0.5) * 0.18,
        vy: (Math.random() - 0.5) * 0.18,
        r: nodeSize,
        alpha: 0.22 + Math.random() * 0.18,
        pulse: 0,
        connectCount: 0
      };
    }

    function createLink(a, b) {
      return {
        a,
        b,
        alpha: 0,
        life: Math.random() * 140 + 120,
        strength: Math.random() * 0.18 + 0.12
      };
    }

    function createWave(x, y) {
      waves.push({ x, y, radius: 0, alpha: 0.45 });
      for (const particle of particles) {
        const dx = particle.x - x;
        const dy = particle.y - y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 170) {
          const force = (170 - dist) * 0.004;
          particle.vx += dx / dist * force;
          particle.vy += dy / dist * force;
          particle.baseAlpha = Math.min(0.8, particle.baseAlpha + 0.1);
        }
      }
    }

    function drawBackground() {
      ctx.clearRect(0, 0, width, height);
      ctx.fillStyle = '#04080f';
      ctx.fillRect(0, 0, width, height);

      for (const wave of waves) {
        wave.radius += 3.4;
        wave.alpha *= 0.95;
        ctx.beginPath();
        ctx.strokeStyle = `rgba(34,197,94,${wave.alpha * 0.17})`;
        ctx.lineWidth = 1.4;
        ctx.arc(wave.x, wave.y, wave.radius, 0, Math.PI * 2);
        ctx.stroke();
      }
      for (let i = waves.length - 1; i >= 0; i--) {
        if (waves[i].alpha < 0.02) waves.splice(i, 1);
      }

      for (const particle of particles) {
        particle.phase += 0.02 + Math.random() * 0.01;
        particle.alpha = Math.min(1, Math.max(0, particle.baseAlpha + Math.sin(particle.phase) * 0.08));
        particle.x += particle.vx;
        particle.y += particle.vy;
        if (particle.x < -20) particle.x = width + 20;
        if (particle.x > width + 20) particle.x = -20;
        if (particle.y < -20) particle.y = height + 20;
        if (particle.y > height + 20) particle.y = -20;
      }

      for (const node of nodes) {
        node.x += node.vx;
        node.y += node.vy;
        node.phase = (node.phase || 0) + 0.015;
        node.alpha = 0.16 + Math.sin(node.phase) * 0.1;
        node.pulse = Math.max(0, (node.pulse || 0) - 0.08);
        if (node.x < -30) node.x = width + 30;
        if (node.x > width + 30) node.x = -30;
        if (node.y < -30) node.y = height + 30;
        if (node.y > height + 30) node.y = -30;
        node.connectCount = 0;
      }

      links.length = 0;
      const allPoints = particles.concat(nodes);
      for (let i = 0; i < allPoints.length; i++) {
        const a = allPoints[i];
        if (a.type === 'dot' && Math.random() > 0.35) continue;
        for (let j = i + 1; j < allPoints.length; j++) {
          const b = allPoints[j];
          const dx = a.x - b.x;
          const dy = a.y - b.y;
          const dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < 110 && a.connectCount < maxLinks && b.connectCount < maxLinks) {
            const alpha = (1 - dist / 110) * 0.14 * Math.min(a.alpha, b.alpha);
            links.push({ a, b, alpha });
            a.connectCount += 1;
            b.connectCount += 1;
          }
        }
      }

      for (const link of links) {
        ctx.strokeStyle = `rgba(34,197,94,${link.alpha})`;
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(link.a.x, link.a.y);
        ctx.lineTo(link.b.x, link.b.y);
        ctx.stroke();
      }

      for (const particle of particles) {
        ctx.beginPath();
        ctx.fillStyle = `rgba(34,197,94,${particle.alpha * 0.75})`;
        ctx.arc(particle.x, particle.y, particle.r, 0, Math.PI * 2);
        ctx.fill();
      }

      for (const node of nodes) {
        const glow = 1 + node.pulse * 0.18;
        ctx.beginPath();
        ctx.fillStyle = `rgba(34,197,94,${node.alpha * 0.6})`;
        ctx.arc(node.x, node.y, node.r * glow, 0, Math.PI * 2);
        ctx.fill();
        ctx.beginPath();
        ctx.strokeStyle = `rgba(34,197,94,${node.alpha * 0.16})`;
        ctx.lineWidth = 1.4;
        ctx.arc(node.x, node.y, node.r * 2.6 + node.pulse, 0, Math.PI * 2);
        ctx.stroke();
      }


      requestAnimationFrame(drawBackground);
    }

    canvas.addEventListener('mousemove', event => {
      pointer.x = event.clientX;
      pointer.y = event.clientY;
      pointer.active = true;
      for (const particle of particles) {
        const dx = pointer.x - particle.x;
        const dy = pointer.y - particle.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 100) {
          const force = (100 - dist) * 0.00014;
          particle.vx += dx * force;
          particle.vy += dy * force;
          particle.baseAlpha = Math.min(0.5, particle.baseAlpha + 0.015);
        }
      }
    });

    canvas.addEventListener('mouseleave', () => { pointer.active = false; });

    canvas.addEventListener('click', event => {
      createWave(event.clientX, event.clientY);
      for (const node of nodes) {
        const dx = node.x - event.clientX;
        const dy = node.y - event.clientY;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 180) node.pulse = 1.4;
      }
    });

    function draw() {
      const activePanel = document.querySelector('.form-panel.visible');
      if (!activePanel) return;
      requestAnimationFrame(draw);
    }

    resizeCanvas();
    drawBackground();
  </script>
</body>
</html>
