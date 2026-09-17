<?php
require_once __DIR__ . '/../../Controller/Controller.php';

session_start();

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$currentPage = $_SERVER['PHP_SELF'] ?? 'acesso.php';

if (!empty($_SESSION['user_id'])) {
  header('Location: home.php');
    exit;
}

$controller = new LoginController();
$erro = '';

if ($requestMethod === 'POST') {
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
            header('Location: home.php');
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
            header('Location: home.php');
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
<meta name="theme-color" content="#07100c">
<title>Aeris Guard — Acesso</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  color-scheme:dark;
  --bg:#050806; --paper:#eef2ed; --paper2:#f7f8f5;
  --ink:#07100c; --ink2:#102019; --text:#f1f6f2; --soft:#b5c2bb; --muted:#74847c;
  --line:rgba(233,244,237,.12); --line-dark:rgba(7,16,12,.12);
  --green:#42e38c; --green2:#17b96e; --green3:#a8f2c8;
  --ease:cubic-bezier(.2,.8,.2,1);
}
*{box-sizing:border-box}
html,body{margin:0;min-height:100%;background:var(--bg)}
body{min-height:100vh;overflow-x:hidden;color:var(--text);font-family:"DM Sans",sans-serif;background:
  radial-gradient(900px 700px at 0% 0%,rgba(49,226,137,.09),transparent 62%),
  radial-gradient(800px 700px at 100% 100%,rgba(49,226,137,.075),transparent 64%),
  linear-gradient(135deg,#030504 0%,#07100c 45%,#050806 100%)}
button,input{font:inherit}button{color:inherit}a{color:inherit}
::selection{background:rgba(66,227,140,.25);color:#fff}
#background-canvas{position:fixed;inset:0;width:100%;height:100%;z-index:0;pointer-events:none;opacity:.46}
.noise{position:fixed;inset:0;z-index:1;pointer-events:none;opacity:.035;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 160 160' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.95' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.65'/%3E%3C/svg%3E")}
.edge-glow{position:fixed;z-index:0;pointer-events:none;border-radius:50%}
.edge-glow.one{width:620px;height:620px;left:-430px;top:-430px;border:1px solid rgba(66,227,140,.20);box-shadow:0 0 0 38px rgba(66,227,140,.025),0 0 0 92px rgba(66,227,140,.013),0 0 130px rgba(66,227,140,.08)}
.edge-glow.two{width:660px;height:660px;right:-455px;bottom:-455px;border:1px solid rgba(66,227,140,.18);box-shadow:0 0 0 42px rgba(66,227,140,.023),0 0 0 102px rgba(66,227,140,.012),0 0 140px rgba(66,227,140,.07)}
.grid{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.15;background:linear-gradient(rgba(255,255,255,.022) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.022) 1px,transparent 1px);background-size:72px 72px;mask-image:radial-gradient(ellipse at center,black 10%,rgba(0,0,0,.6) 67%,transparent 100%)}
.page{position:relative;z-index:2;min-height:100vh;padding:clamp(14px,2.5vw,36px);display:flex;align-items:center;justify-content:center}
.shell{position:relative;width:min(1540px,100%);min-height:min(900px,calc(100vh - 28px));display:grid;grid-template-columns:minmax(0,1.16fr) minmax(440px,.84fr);overflow:hidden;border:1px solid rgba(235,246,239,.14);border-radius:30px;background:rgba(8,14,11,.80);box-shadow:0 40px 120px rgba(0,0,0,.52),0 0 0 1px rgba(255,255,255,.02) inset;backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px)}
.shell:before{content:"";position:absolute;inset:0;pointer-events:none;background:linear-gradient(125deg,rgba(255,255,255,.045),transparent 30%,transparent 68%,rgba(255,255,255,.018))}
/* LEFT — visual identity */
.visual{position:relative;min-width:0;overflow:hidden;padding:clamp(28px,4vw,62px);display:flex;flex-direction:column;justify-content:space-between;border-right:1px solid var(--line)}
.visual:before{content:"";position:absolute;inset:0;pointer-events:none;background:
  radial-gradient(580px 500px at 8% 2%,rgba(66,227,140,.075),transparent 68%),
  radial-gradient(560px 500px at 88% 96%,rgba(66,227,140,.045),transparent 70%)}
.brand{position:relative;z-index:5;display:flex;align-items:center;gap:12px}
.brand-mark{width:48px;height:48px;border-radius:15px;display:grid;place-items:center;background:linear-gradient(145deg,#53e998,#19bc70);color:#06100b;font:700 22px "Space Grotesk",sans-serif;box-shadow:0 14px 40px rgba(35,205,119,.16)}
.brand-copy{display:grid;gap:2px}.brand-copy small{font:700 8px "Space Grotesk",sans-serif;letter-spacing:.25em;text-transform:uppercase;color:#7f9188}.brand-copy strong{font:600 18px "Space Grotesk",sans-serif;letter-spacing:-.045em}
.top-status{position:absolute;right:clamp(28px,4vw,62px);top:clamp(28px,4vw,62px);display:flex;align-items:center;gap:9px;color:#84958d;font-size:9px;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
.top-status i{width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 0 5px rgba(66,227,140,.055),0 0 18px rgba(66,227,140,.45);animation:pulse 2.5s ease-in-out infinite}@keyframes pulse{50%{opacity:.55;transform:scale(.75)}}
.visual-content{position:relative;z-index:3;padding-top:clamp(50px,7vh,92px)}
.eyebrow{display:flex;align-items:center;gap:11px;color:#819189;font:700 9px "Space Grotesk",sans-serif;letter-spacing:.2em;text-transform:uppercase}.eyebrow:before{content:"";width:34px;height:1px;background:rgba(66,227,140,.82)}
.visual h1{margin:20px 0 0;max-width:860px;font:600 clamp(52px,6.2vw,101px)/.88 "Space Grotesk",sans-serif;letter-spacing:-.09em}
.visual h1 .light{color:#f0f6f2}.visual h1 .green{background:linear-gradient(105deg,#39dc86,#a8f2c8);-webkit-background-clip:text;background-clip:text;color:transparent}
.visual-copy{max-width:670px;margin:26px 0 0;color:#96a69e;font-size:15px;line-height:1.72}
/* layered editorial composition — several anchors, no single hero */
.composition{position:relative;margin-top:clamp(38px,6vh,70px);height:clamp(250px,31vh,340px);border-top:1px solid var(--line);border-bottom:1px solid var(--line);overflow:hidden}
.composition:before{content:"AERIS";position:absolute;left:-6px;top:10px;font:700 clamp(90px,13vw,210px)/.78 "Space Grotesk",sans-serif;letter-spacing:-.12em;color:rgba(240,248,243,.027);white-space:nowrap}
.composition:after{content:"ENVIRONMENTAL SAFETY / CONNECTED MONITORING / 2026";position:absolute;right:0;bottom:13px;color:#52645b;font:700 7px "Space Grotesk",sans-serif;letter-spacing:.2em}
.contours{position:absolute;inset:0;overflow:hidden;opacity:.72}
.contours span{position:absolute;border:1px solid rgba(66,227,140,.17);border-radius:50%;transform:rotate(-18deg);box-shadow:0 0 30px rgba(66,227,140,.02)}
.contours span:nth-child(1){width:390px;height:180px;left:2%;top:18%;}.contours span:nth-child(2){width:500px;height:235px;left:-1%;top:10%;}.contours span:nth-child(3){width:610px;height:295px;left:-5%;top:0%;}.contours span:nth-child(4){width:740px;height:355px;left:-10%;top:-9%;}
.route{position:absolute;right:7%;top:17%;width:42%;height:58%;border-left:1px solid rgba(66,227,140,.13);border-bottom:1px solid rgba(66,227,140,.13);transform:skewY(-8deg)}
.route:before,.route:after{content:"";position:absolute;border:1px solid rgba(66,227,140,.18);border-radius:50%}.route:before{width:130px;height:130px;right:12%;top:2%;box-shadow:0 0 0 22px rgba(66,227,140,.015),0 0 0 44px rgba(66,227,140,.01)}.route:after{width:8px;height:8px;right:calc(12% + 61px);top:64px;background:var(--green);border:0;box-shadow:0 0 0 8px rgba(66,227,140,.055),0 0 24px rgba(66,227,140,.6)}
.route-line{position:absolute;width:76%;height:1px;right:8%;top:48%;background:linear-gradient(90deg,transparent,rgba(66,227,140,.42),transparent);transform:rotate(-12deg)}
.info-chip{position:absolute;z-index:4;padding:10px 12px;border:1px solid rgba(235,246,239,.10);background:rgba(9,18,14,.72);box-shadow:0 14px 35px rgba(0,0,0,.2);backdrop-filter:blur(12px);border-radius:11px;color:#9baca4;font:700 8px "Space Grotesk",sans-serif;letter-spacing:.08em;text-transform:uppercase}.info-chip b{display:block;color:#e3eee8;font-size:11px;letter-spacing:-.02em;text-transform:none;margin-top:4px}.chip-a{left:4%;bottom:18%}.chip-b{right:5%;bottom:16%}.chip-c{left:32%;top:16%}
.editorial-grid{position:absolute;right:5%;top:10%;width:28%;height:26%;display:grid;grid-template-columns:repeat(8,1fr);gap:5px;opacity:.55}.editorial-grid span{border:1px solid rgba(66,227,140,.12)}
.visual-bottom{position:relative;z-index:4;display:flex;align-items:flex-end;justify-content:space-between;gap:24px;padding-top:28px}.bottom-list{display:flex;flex-wrap:wrap;gap:18px;color:#63746c;font-size:8px;text-transform:uppercase;letter-spacing:.13em;font-weight:700}.bottom-list span{display:flex;align-items:center;gap:7px}.bottom-list i{width:5px;height:5px;border-radius:50%;background:#56675f}.bottom-list .active{color:#a9b9b1}.bottom-list .active i{background:var(--green);box-shadow:0 0 10px rgba(66,227,140,.5)}
.signature{text-align:right;color:#53645c;font:700 8px/1.5 "Space Grotesk",sans-serif;letter-spacing:.16em;text-transform:uppercase}.signature strong{display:block;color:#8fa098;font-size:10px}
/* RIGHT — light product-like authentication */
.auth{position:relative;min-width:0;background:linear-gradient(145deg,rgba(246,248,244,.98),rgba(232,237,231,.98));color:var(--ink);padding:clamp(38px,5vw,76px);display:flex;align-items:center}
.auth:before{content:"";position:absolute;width:380px;height:380px;right:-250px;top:-240px;border-radius:50%;border:1px solid rgba(31,181,105,.24);box-shadow:0 0 0 34px rgba(31,181,105,.035),0 0 0 78px rgba(31,181,105,.018);pointer-events:none}
.auth:after{content:"";position:absolute;width:300px;height:300px;left:-230px;bottom:-220px;border-radius:50%;border:1px solid rgba(31,181,105,.15);box-shadow:0 0 0 30px rgba(31,181,105,.025),0 0 80px rgba(31,181,105,.06);pointer-events:none}
.auth-box{position:relative;z-index:3;width:min(500px,100%);margin:0 auto}
.auth-top{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:26px}.auth-kicker{display:flex;align-items:center;gap:10px;color:#65746c;font:700 8px "Space Grotesk",sans-serif;letter-spacing:.2em;text-transform:uppercase}.auth-kicker:before{content:"";width:28px;height:1px;background:#1bb86d}.protected{display:flex;align-items:center;gap:7px;color:#69776f;font-size:8px;font-weight:700}.protected i{width:5px;height:5px;border-radius:50%;background:#20bd72}
.auth h2{margin:0;font:600 clamp(48px,5vw,70px)/.88 "Space Grotesk",sans-serif;letter-spacing:-.075em;color:#07100c}.auth-intro{max-width:430px;margin:20px 0 0;color:#66756d;font-size:13px;line-height:1.7}.erro{padding:11px 13px;margin:18px 0 0;border:1px solid rgba(180,50,50,.2);background:rgba(180,50,50,.06);border-radius:11px;color:#9b3535;font-size:11px}
.tabs{position:relative;display:grid;grid-template-columns:1fr 1fr;margin-top:31px;padding:4px;border:1px solid rgba(7,16,12,.12);border-radius:13px;background:rgba(255,255,255,.48);height:52px}.tab-indicator{position:absolute;left:4px;top:4px;height:42px;width:calc(50% - 4px);border-radius:9px;background:#0d2118;box-shadow:0 5px 14px rgba(7,16,12,.12);transition:transform .35s var(--ease)}.tab{position:relative;z-index:2;border:0;background:transparent;color:#738078;font-size:11px;font-weight:700;cursor:pointer;transition:color .25s}.tab.active{color:#f4faf6}.tab:focus-visible{outline:2px solid #22b971;outline-offset:2px;border-radius:8px}
.form-shell{position:relative;margin-top:27px;min-height:292px}.form-panel{transition:opacity .28s var(--ease),transform .35s var(--ease)}.form-panel.hidden{display:none}.form-panel.outgoing{opacity:0;transform:translateY(8px)}
.input-group{margin-bottom:17px}.input-group label{display:block;margin:0 0 7px;color:#25342d;font-size:10px;font-weight:800}.field{position:relative}.input-control{width:100%;height:55px;border:1px solid rgba(7,16,12,.13);border-radius:12px;background:rgba(255,255,255,.60);color:#0a1711;outline:0;padding:0 45px 0 16px;font-size:12px;box-shadow:0 1px 0 rgba(255,255,255,.7) inset;transition:border-color .25s,background .25s,box-shadow .25s,transform .25s}.input-control::placeholder{color:#9aa59f}.input-control:hover{background:rgba(255,255,255,.78);border-color:rgba(7,16,12,.2)}.input-control:focus{border-color:rgba(24,184,109,.62);background:#fff;box-shadow:0 0 0 4px rgba(24,184,109,.09)}
.field-icon{position:absolute;left:15px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:#829088;pointer-events:none;display:none}.password-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);width:31px;height:31px;border:0;border-radius:8px;background:transparent;color:#718078;display:grid;place-items:center;cursor:pointer}.password-toggle:hover{background:rgba(7,16,12,.055);color:#26352e}
.form-meta{display:flex;align-items:center;justify-content:space-between;gap:12px;color:#7b8881;font-size:9px;margin:2px 0 19px}.form-meta a{color:#159d5e;text-decoration:none;font-weight:800}.form-meta a:hover{text-decoration:underline}
.submit-button{position:relative;overflow:hidden;width:100%;height:56px;border:0;border-radius:12px;background:#0b2117;color:#f2faf5;font-size:12px;font-weight:800;cursor:pointer;box-shadow:0 12px 28px rgba(7,16,12,.14);transition:transform .25s var(--ease),box-shadow .25s var(--ease),background .25s}.submit-button:before{content:"";position:absolute;top:-50%;bottom:-50%;left:-35%;width:18%;transform:skewX(-20deg);background:rgba(255,255,255,.18);filter:blur(5px);transition:left .65s var(--ease)}.submit-button:hover{transform:translateY(-2px);background:#123b29;box-shadow:0 16px 32px rgba(7,16,12,.18)}.submit-button:hover:before{left:125%}.submit-button:active{transform:translateY(0)}
.auth-note{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:22px}.note{padding-top:14px;border-top:1px solid rgba(7,16,12,.10)}.note span{display:block;color:#7c8982;font:700 7px "Space Grotesk",sans-serif;letter-spacing:.17em;text-transform:uppercase}.note strong{display:block;margin-top:5px;color:#25352d;font:600 10px "Space Grotesk",sans-serif}.auth-footer{display:flex;justify-content:space-between;gap:20px;margin-top:34px;padding-top:17px;border-top:1px solid rgba(7,16,12,.11);color:#829088;font-size:8px}.auth-footer strong{color:#4f5f57}.legal{margin-top:9px;color:#9aa49e;font-size:8px;line-height:1.5}
/* Decorative vertical typography */
.vertical{position:absolute;z-index:6;color:#52645b;font:700 7px "Space Grotesk",sans-serif;letter-spacing:.2em;text-transform:uppercase;writing-mode:vertical-rl}.vertical.left{left:9px;top:34%;transform:rotate(180deg)}.vertical.right{right:9px;bottom:30%}
@media(max-width:1180px){.shell{grid-template-columns:minmax(0,1.05fr) minmax(400px,.95fr)}.visual h1{font-size:clamp(50px,6.3vw,82px)}.composition{height:285px}.auth{padding:45px}.auth h2{font-size:58px}}
@media(max-width:900px){.page{padding:12px}.shell{grid-template-columns:1fr;min-height:auto}.visual{border-right:0;border-bottom:1px solid var(--line);min-height:650px}.auth{min-height:650px}.visual h1{font-size:clamp(52px,9vw,82px)}.composition{height:270px}.vertical{display:none}.auth-box{max-width:560px}}
@media(max-width:600px){body{background:#050806}.page{padding:0}.shell{width:100%;min-height:100vh;border:0;border-radius:0}.visual{padding:21px 19px 25px;min-height:auto}.brand-mark{width:42px;height:42px;border-radius:13px;font-size:19px}.brand-copy strong{font-size:16px}.brand-copy small{font-size:7px}.top-status{top:24px;right:19px;font-size:7px}.visual-content{padding-top:56px}.eyebrow{font-size:7px}.eyebrow:before{width:25px}.visual h1{font-size:clamp(43px,12.5vw,60px);line-height:.91}.visual-copy{font-size:12px;line-height:1.65;margin-top:18px}.composition{height:235px;margin-top:30px}.composition:after{font-size:5px}.chip-c{left:35%;top:13%}.chip-a{left:3%;bottom:14%}.chip-b{right:3%;bottom:14%}.info-chip{padding:8px 9px;font-size:6px}.info-chip b{font-size:9px}.editorial-grid{width:31%;height:23%}.visual-bottom{padding-top:19px;align-items:flex-start}.bottom-list{gap:9px;font-size:6px}.signature{font-size:6px}.signature strong{font-size:8px}.auth{padding:38px 19px 29px;min-height:auto}.auth-top{margin-bottom:24px}.protected{font-size:7px}.auth h2{font-size:45px}.auth-intro{font-size:11px;margin-top:16px}.tabs{margin-top:24px}.form-shell{margin-top:23px}.input-control{height:53px}.auth-note{grid-template-columns:1fr 1fr;margin-top:19px}.auth-footer{margin-top:27px}.edge-glow.one{width:400px;height:400px;left:-300px;top:-300px}.edge-glow.two{width:430px;height:430px;right:-320px;bottom:-320px}}
@media(prefers-reduced-motion:reduce){*,*:before,*:after{animation:none!important;transition:none!important}.submit-button:before{display:none}}
</style>
</head>
<body>
<canvas id="background-canvas" aria-hidden="true"></canvas>
<div class="noise" aria-hidden="true"></div><div class="grid" aria-hidden="true"></div><div class="edge-glow one" aria-hidden="true"></div><div class="edge-glow two" aria-hidden="true"></div>
<main class="page">
  <span class="vertical left">AERIS GUARD / SYSTEM ACCESS</span>
  <span class="vertical right">ENVIRONMENTAL SAFETY / 2026</span>
  <div class="shell">
    <section class="visual" aria-label="Identidade Aeris Guard">
      <div>
        <div class="brand"><div class="brand-mark">A</div><div class="brand-copy"><small>Intelligent Safety</small><strong>Aeris Guard</strong></div></div>
        <div class="top-status"><i></i> sistema conectado</div>
      </div>

      <div class="visual-content">
        <div class="eyebrow">Aeris Guard / acesso ao ecossistema</div>
        <h1><span class="light">Segurança que</span><br><span class="light">começa <span class="green">antes</span></span><br><span class="light">do alerta.</span></h1>
        <p class="visual-copy">Uma plataforma criada para transformar leitura ambiental em informação clara, acompanhamento contínuo e respostas mais rápidas.</p>

        <div class="composition" aria-hidden="true">
          <div class="contours"><span></span><span></span><span></span><span></span></div>
          <div class="route"><div class="route-line"></div></div>
          <div class="editorial-grid"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
          <div class="info-chip chip-a">Sensor<b>Leitura ambiental</b></div>
          <div class="info-chip chip-c">Aeris Signal<b>Fluxo conectado</b></div>
          <div class="info-chip chip-b">Painel<b>Informação em tempo real</b></div>
        </div>
      </div>

      <div class="visual-bottom">
        <div class="bottom-list"><span class="active"><i></i> Plataforma Aeris</span><span><i></i> Monitoramento</span><span><i></i> Interface responsiva</span></div>
        <div class="signature"><strong>AERIS / 01</strong>secure access</div>
      </div>
    </section>

    <section class="auth" role="main" aria-labelledby="auth-title">
      <div class="auth-box">
        <div class="auth-top"><div class="auth-kicker">Acesso ao sistema</div><div class="protected"><i></i> Canal protegido</div></div>
        <h2 id="auth-title">Bem-vindo<br>de volta.</h2>
        <p class="auth-intro">Acesse sua conta para continuar acompanhando seu ambiente pelo painel Aeris Guard.</p>
        <?php if (!empty($erro)) echo '<p class="erro">' . htmlspecialchars($erro) . '</p>'; ?>

        <div class="tabs" role="tablist" aria-label="Modo de autenticação">
          <span class="tab-indicator"></span>
          <button class="tab active" data-panel="login" type="button" role="tab" aria-selected="true">Entrar</button>
          <button class="tab" data-panel="create" type="button" role="tab" aria-selected="false">Criar conta</button>
        </div>

        <div class="form-shell">
          <div id="login" class="form-panel visible" aria-hidden="false">
            <form method="post" action="<?php echo htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="tipo" value="login">
              <div class="input-group"><label for="email-login">E-mail</label><div class="field"><input id="email-login" type="email" name="email" class="input-control" placeholder="seu@email.com" autocomplete="email" required></div></div>
              <div class="input-group"><label for="senha-login">Senha</label><div class="field"><input id="senha-login" type="password" name="senha" class="input-control" placeholder="Digite sua senha" autocomplete="current-password" required><button class="password-toggle" type="button" data-password="senha-login" aria-label="Mostrar senha"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><path d="M3.5 12s3-5 8.5-5 8.5 5 8.5 5-3 5-8.5 5-8.5-5-8.5-5Z" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="12" r="2.2" stroke="currentColor" stroke-width="1.5"/></svg></button></div></div>
              <div class="form-meta"><span>Use seus dados cadastrados.</span><a href="#">Esqueceu a senha?</a></div>
              <button type="submit" class="submit-button">Entrar na plataforma</button>
            </form>
          </div>

          <div id="create" class="form-panel hidden" aria-hidden="true">
            <form method="post" action="<?php echo htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="tipo" value="cadastro">
              <div class="input-group"><label for="nome-create">Nome completo</label><div class="field"><input id="nome-create" type="text" name="nome" class="input-control" placeholder="Como podemos chamar você?" autocomplete="name" required></div></div>
              <div class="input-group"><label for="email-create">E-mail</label><div class="field"><input id="email-create" type="email" name="email" class="input-control" placeholder="seu@email.com" autocomplete="email" required></div></div>
              <div class="input-group"><label for="senha-create">Senha</label><div class="field"><input id="senha-create" type="password" name="senha" class="input-control" placeholder="Mínimo de 6 caracteres" autocomplete="new-password" required><button class="password-toggle" type="button" data-password="senha-create" aria-label="Mostrar senha"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><path d="M3.5 12s3-5 8.5-5 8.5 5 8.5 5-3 5-8.5 5-8.5-5-8.5-5Z" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="12" r="2.2" stroke="currentColor" stroke-width="1.5"/></svg></button></div></div>
              <div class="form-meta"><span>Crie seu acesso ao painel Aeris.</span></div>
              <button type="submit" class="submit-button">Criar minha conta</button>
            </form>
          </div>
        </div>

        <div class="auth-note"><div class="note"><span>Experiência</span><strong>Acesso simples e direto</strong></div><div class="note"><span>Ecossistema</span><strong>Monitoramento conectado</strong></div></div>
        <div class="auth-footer"><span><strong>Aeris Guard</strong> · Ambiente conectado</span><span>v1.0 · Secure Access</span></div>
        <div class="legal">Seu acesso leva você diretamente ao painel de monitoramento.</div>
      </div>
    </section>
  </div>
</main>
<script>
const tabs=document.querySelectorAll('.tab'),panels=document.querySelectorAll('.form-panel'),indicator=document.querySelector('.tab-indicator');
function updateIndicator(activeTab){if(!activeTab||!indicator)return;const rect=activeTab.getBoundingClientRect(),parentRect=activeTab.parentElement.getBoundingClientRect();indicator.style.transform=`translateX(${rect.left-parentRect.left-4}px)`;indicator.style.width=`${rect.width}px`}
function activateTab(tab){tabs.forEach(button=>{const panel=document.getElementById(button.dataset.panel),active=button===tab;button.classList.toggle('active',active);button.setAttribute('aria-selected',active);if(active){panel.classList.remove('hidden','outgoing');panel.classList.add('visible');setTimeout(()=>{const input=panel.querySelector('input:not([type="hidden"])');if(input)input.focus()},300)}else if(!panel.classList.contains('hidden')){panel.classList.add('outgoing');panel.classList.remove('visible');setTimeout(()=>panel.classList.add('hidden'),330)}});updateIndicator(tab)}
tabs.forEach(tab=>tab.addEventListener('click',()=>activateTab(tab)));window.addEventListener('load',()=>updateIndicator(document.querySelector('.tab.active')));window.addEventListener('resize',()=>updateIndicator(document.querySelector('.tab.active')));
document.querySelectorAll('.password-toggle').forEach(btn=>btn.addEventListener('click',()=>{const input=document.getElementById(btn.dataset.password);if(!input)return;const visible=input.type==='text';input.type=visible?'password':'text';btn.setAttribute('aria-label',visible?'Mostrar senha':'Ocultar senha')}));
const canvas=document.getElementById('background-canvas'),ctx=canvas.getContext('2d');let w=innerWidth,h=innerHeight,dpr=1,particles=[],running=true;const pointer={x:-9999,y:-9999};
function count(){return innerWidth<600?34:innerWidth<900?54:innerWidth<1200?76:98}
function makeParticle(){const edge=Math.random();let x=Math.random()*w,y=Math.random()*h;if(edge<.32){x=Math.random()*w*.36;y=Math.random()*h*.36}else if(edge>.68){x=w-Math.random()*w*.36;y=h-Math.random()*h*.36}return{x,y,vx:(Math.random()-.5)*.14,vy:(Math.random()-.5)*.14,r:Math.random()*1.15+.3,a:Math.random()*.16+.035,p:Math.random()*6.28}}
function resize(){w=innerWidth;h=innerHeight;dpr=Math.min(devicePixelRatio||1,2);canvas.width=w*dpr;canvas.height=h*dpr;canvas.style.width=w+'px';canvas.style.height=h+'px';ctx.setTransform(dpr,0,0,dpr,0,0);const n=count();while(particles.length<n)particles.push(makeParticle());while(particles.length>n)particles.pop()}
function render(){if(!running)return;ctx.clearRect(0,0,w,h);for(const p of particles){p.p+=.008;p.x+=p.vx;p.y+=p.vy;if(p.x<-8)p.x=w+8;if(p.x>w+8)p.x=-8;if(p.y<-8)p.y=h+8;if(p.y>h+8)p.y=-8;const dx=pointer.x-p.x,dy=pointer.y-p.y,d=Math.hypot(dx,dy);if(d<140){p.x-=dx*.00035;p.y-=dy*.00035}}for(let i=0;i<particles.length;i++){const a=particles[i];ctx.beginPath();ctx.fillStyle=`rgba(66,227,140,${a.a+.02*Math.sin(a.p)})`;ctx.arc(a.x,a.y,a.r,0,Math.PI*2);ctx.fill();for(let j=i+1;j<particles.length;j++){const b=particles[j],dx=a.x-b.x,dy=a.y-b.y,d=Math.hypot(dx,dy);if(d<112){ctx.beginPath();ctx.strokeStyle=`rgba(66,227,140,${(1-d/112)*.045})`;ctx.lineWidth=.6;ctx.moveTo(a.x,a.y);ctx.lineTo(b.x,b.y);ctx.stroke()}}}requestAnimationFrame(render)}
addEventListener('resize',resize);addEventListener('pointermove',e=>{pointer.x=e.clientX;pointer.y=e.clientY});addEventListener('pointerleave',()=>{pointer.x=-9999;pointer.y=-9999});resize();render();document.addEventListener('visibilitychange',()=>{running=document.visibilityState==='visible';if(running)requestAnimationFrame(render)});
</script>
</body>
</html>
