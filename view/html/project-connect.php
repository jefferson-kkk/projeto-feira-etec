<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../Controller/ProjectController.php';

$projectController = new ProjectController();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $manufacturerCode = trim($_POST['manufacturer_code'] ?? '');
    $firmwareVersion = trim($_POST['firmware_version'] ?? '');
    $wifiStatus = trim($_POST['wifi_status'] ?? '');

    if ($name === '' || $manufacturerCode === '') {
        $error = 'Nome do projeto e código do dispositivo são obrigatórios.';
    } else {
        try {
            $projectController->createProject(
                $currentUser->getid(),
                $name,
                $manufacturerCode,
                $firmwareVersion ?: null,
                $wifiStatus ?: null,
                null,
                null
            );
            header('Location: home.php');
            exit;
        } catch (Exception $e) {
            $error = 'Não foi possível criar o projeto: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Conectar Projeto — Aeris Secure</title>
  <style>
    :root { --bg: #04080f; --surface: rgba(7, 13, 24, 0.92); --border: rgba(255,255,255,0.08); --accent: #22c55e; --text: #eef3f8; --muted: #9eb1c7; --radius: 24px; }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; font-family: 'Inter', sans-serif; color: var(--text); background: radial-gradient(circle at top, rgba(34,197,94,0.06), transparent 22%), linear-gradient(180deg, #02050a 0%, #060b14 100%); }
    .page { padding: 28px; max-width: 760px; margin: 0 auto; }
    .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px; box-shadow: 0 30px 80px rgba(0,0,0,0.24); }
    h1 { margin-top: 0; font-size: clamp(28px, 4vw, 36px); }
    p { color: var(--muted); }
    .form-group { margin-bottom: 18px; }
    label { display: block; margin-bottom: 8px; color: var(--muted); font-size: 14px; }
    input { width: 100%; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.05); color: var(--text); padding: 14px 16px; font-size: 15px; }
    input:focus { outline: none; border-color: rgba(34,197,94,0.55); box-shadow: 0 0 0 6px rgba(34,197,94,0.08); }
    .button { border: 0; border-radius: 16px; padding: 14px 18px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #071f12; font-weight: 700; cursor: pointer; }
    .secondary { background: rgba(255,255,255,0.06); color: var(--text); text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
    .message { margin-bottom: 18px; padding: 14px 16px; border-radius: 16px; }
    .message.error { background: rgba(248,113,113,0.14); color: #fca5a5; border: 1px solid rgba(248,113,113,0.18); }
  </style>
</head>
<body>
  <main class="page">
    <section class="card">
      <h1>Conectar novo projeto</h1>
      <p>Registre um projeto IoT para monitoramento e controle na sua plataforma.</p>

      <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

      <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div class="form-group">
          <label for="name">Nome do projeto</label>
          <input id="name" type="text" name="name" required>
        </div>
        <div class="form-group">
          <label for="manufacturer_code">Código do dispositivo</label>
          <input id="manufacturer_code" type="text" name="manufacturer_code" required>
        </div>
        <div class="form-group">
          <label for="firmware_version">Versão do firmware</label>
          <input id="firmware_version" type="text" name="firmware_version" placeholder="Ex: v1.0.0">
        </div>
        <div class="form-group">
          <label for="wifi_status">Status de rede</label>
          <input id="wifi_status" type="text" name="wifi_status" placeholder="Ex: conectado">
        </div>
        <button type="submit" class="button">Salvar projeto</button>
      </form>

      <div style="margin-top:22px; display:flex; gap:12px; flex-wrap:wrap;">
        <a class="secondary" href="home.php">Voltar ao dashboard</a>
        <a class="secondary" href="logout.php">Sair</a>
      </div>
    </section>
  </main>
</body>
</html>
