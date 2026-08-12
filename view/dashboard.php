<?php
session_start();
if (empty($_SESSION['usuario'])) {
    header('Location: acesso.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Painel</title>
  <style>
    body { font-family: Arial, sans-serif; background: #0A0E14; color: #EDF1F7; margin: 0; padding: 40px; }
    .card { max-width: 600px; margin: 0 auto; background: #121826; padding: 24px; border-radius: 12px; }
  </style>
</head>
<body>
  <div class="card">
    <h2>Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario']); ?>!</h2>
    <p>Login realizado com sucesso.</p>
    <a href="acesso.php" style="color: #17C9A8;">Sair</a>
  </div>
</body>
</html>
