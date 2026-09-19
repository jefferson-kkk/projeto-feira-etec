<?php

require_once __DIR__ . '/_auth.php';

require_once __DIR__ . '/../../Controller/DeviceController.php';

$deviceController = new DeviceController();

$message = '';
$error = '';
$apiKeyCriada = null;

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $manufacturerCode = trim($_POST['manufacturer_code'] ?? '');
    $firmwareVersion = trim($_POST['firmware_version'] ?? '');
    $esp32Id = trim($_POST['esp32_id'] ?? '');

    if ($name === '' || $manufacturerCode === '') {
        $error = 'Nome do projeto e código do dispositivo são obrigatórios.';
    } elseif ($esp32Id === '') {
        $error = 'Informe o ID do ESP32 (definido no firmware do dispositivo).';
    } else {
        try {

            $resultado = $deviceController->createDevice(
                $currentUser->getid(),
                $name,
                $manufacturerCode,
                $esp32Id,
                null,
                null,
                $firmwareVersion ?: null,
                'MQ-6'
            );

            $apiKeyCriada = $resultado['api_key'];
            $message = 'Dispositivo cadastrado com sucesso.';

        } catch (Exception $e) {
            $error = 'Não foi possível criar o dispositivo: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aeris Guard — Adicionar dispositivo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg:#07110d;
            --bg2:#091611;
            --panel:#0b1712;
            --panel-soft:#0e1c16;
            --panel-light:#12231b;
            --text:#edf6f0;
            --muted:#8da49a;
            --muted-2:#647b70;
            --muted-3:#496056;
            --line:rgba(225,245,234,.105);
            --line-2:rgba(225,245,234,.16);
            --green:#45e99a;
            --green-2:#1fc77a;
            --green-soft:rgba(69,233,154,.10);
            --display:'Space Grotesk',sans-serif;
            --body:'DM Sans',sans-serif;
            --mono:'JetBrains Mono',monospace;
        }

        *{box-sizing:border-box}

        html,body{margin:0;min-height:100%;background:var(--bg)}

        body{
            color:var(--text);
            font-family:var(--body);
            overflow-x:hidden;
            background:
                radial-gradient(700px 450px at 8% -10%,rgba(69,233,154,.10),transparent 65%),
                radial-gradient(550px 500px at 105% 105%,rgba(69,233,154,.055),transparent 65%),
                linear-gradient(135deg,#06100c 0%,#091510 52%,#06100c 100%);
        }

        body::before{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            opacity:.20;
            background-image:
                linear-gradient(rgba(255,255,255,.026) 1px,transparent 1px),
                linear-gradient(90deg,rgba(255,255,255,.026) 1px,transparent 1px);
            background-size:48px 48px;
            mask-image:linear-gradient(to bottom,black 0%,transparent 90%);
        }

        body::after{
            content:"";
            position:fixed;
            left:-100px;
            bottom:-220px;
            width:460px;
            height:460px;
            border-radius:50%;
            border:1px solid rgba(69,233,154,.035);
            box-shadow:
                0 0 0 46px rgba(69,233,154,.018),
                0 0 0 92px rgba(69,233,154,.012);
            pointer-events:none;
        }

        a{color:inherit}

        .page{
            position:relative;
            z-index:1;
            width:min(1280px,calc(100% - 52px));
            min-height:100vh;
            margin:auto;
            padding:26px 0 30px;
            display:flex;
            flex-direction:column;
        }

        /* HEADER */

        .topbar{
            min-height:52px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            margin-bottom:20px;
        }

        .brand{
            display:flex;
            align-items:center;
            gap:11px;
            text-decoration:none;
        }

        .brand-mark{
            width:38px;
            height:38px;
            display:grid;
            place-items:center;
            border-radius:11px;
            background:linear-gradient(145deg,#66f2aa,#20c879);
            color:#06130d;
            font:700 19px var(--display);
            box-shadow:0 10px 28px rgba(69,233,154,.16);
        }

        .brand-text small{
            display:block;
            margin-bottom:2px;
            color:#5e7569;
            font:600 8px var(--mono);
            letter-spacing:.18em;
            text-transform:uppercase;
        }

        .brand-text strong{
            color:#e5eee9;
            font:600 16px var(--display);
            letter-spacing:-.03em;
        }

        .top-meta{
            display:flex;
            align-items:center;
            gap:14px;
        }

        .meta-code{
            color:#536a5f;
            font:600 8px var(--mono);
            letter-spacing:.13em;
            text-transform:uppercase;
        }

        .session{
            display:flex;
            align-items:center;
            gap:7px;
            padding:8px 11px;
            border:1px solid var(--line);
            border-radius:999px;
            color:#91a79c;
            background:rgba(255,255,255,.018);
            font-size:9px;
            font-weight:600;
        }

        .session i{
            width:6px;
            height:6px;
            border-radius:50%;
            background:var(--green);
            box-shadow:0 0 0 5px rgba(69,233,154,.06),0 0 12px var(--green);
        }

        /* MAIN CARD */

        .workspace{
            flex:1;
            min-height:760px;
            display:grid;
            grid-template-columns:minmax(0,1.12fr) minmax(440px,.88fr);
            border:1px solid var(--line);
            border-radius:24px;
            overflow:hidden;
            background:rgba(7,15,11,.80);
            box-shadow:0 32px 100px rgba(0,0,0,.30);
            backdrop-filter:blur(20px);
        }

        /* LEFT */

        .visual{
            position:relative;
            display:flex;
            flex-direction:column;
            min-width:0;
            padding:50px 54px 42px;
            border-right:1px solid var(--line);
            background:
                radial-gradient(500px 360px at 12% 18%,rgba(69,233,154,.075),transparent 68%),
                linear-gradient(180deg,rgba(255,255,255,.018),transparent 45%);
        }

        .visual::before{
            content:"";
            position:absolute;
            top:0;
            left:0;
            width:210px;
            height:210px;
            border-right:1px solid rgba(69,233,154,.06);
            border-bottom:1px solid rgba(69,233,154,.06);
            border-bottom-right-radius:100%;
            pointer-events:none;
        }

        .visual::after{
            content:"AERIS";
            position:absolute;
            right:22px;
            bottom:-17px;
            color:rgba(149,236,187,.035);
            font:700 128px/.8 var(--display);
            letter-spacing:-.09em;
            pointer-events:none;
        }

        .eyebrow{
            position:relative;
            z-index:2;
            display:flex;
            align-items:center;
            gap:9px;
            color:var(--green);
            font:700 8px var(--mono);
            letter-spacing:.15em;
            text-transform:uppercase;
        }

        .eyebrow::before{
            content:"";
            width:30px;
            height:1px;
            background:var(--green);
            box-shadow:0 0 10px rgba(69,233,154,.4);
        }

        h1{
            position:relative;
            z-index:2;
            max-width:650px;
            margin:19px 0 15px;
            font:600 clamp(44px,4.7vw,70px)/.96 var(--display);
            letter-spacing:-.068em;
        }

        h1 span{
            color:var(--green);
        }

        .lead{
            position:relative;
            z-index:2;
            max-width:600px;
            margin:0;
            color:var(--muted);
            font-size:14px;
            line-height:1.72;
        }

        /* DEVICE VISUAL */

        .device-card{
            position:relative;
            z-index:2;
            min-height:215px;
            margin-top:34px;
            padding:20px 22px;
            border:1px solid rgba(69,233,154,.15);
            border-radius:17px;
            overflow:hidden;
            background:
                radial-gradient(circle at 22% 55%,rgba(69,233,154,.14),transparent 25%),
                linear-gradient(120deg,rgba(69,233,154,.055),rgba(3,11,7,.12));
        }

        .device-card::before{
            content:"";
            position:absolute;
            inset:0;
            background:
                linear-gradient(90deg,transparent 0%,rgba(69,233,154,.055) 50%,transparent 100%);
            transform:translateX(-100%);
            animation:scan 5s ease-in-out infinite;
            pointer-events:none;
        }

        .device-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:15px;
            padding-bottom:13px;
            border-bottom:1px solid rgba(255,255,255,.055);
        }

        .device-head small,
        .device-status{
            font:600 8px var(--mono);
            letter-spacing:.13em;
            text-transform:uppercase;
        }

        .device-head small{color:#688174}

        .device-status{
            display:flex;
            align-items:center;
            gap:7px;
            color:#7f9c8e;
        }

        .device-status i{
            width:6px;
            height:6px;
            border-radius:50%;
            background:var(--green);
            box-shadow:0 0 10px var(--green);
        }

        .device-body{
            position:relative;
            min-height:151px;
            display:grid;
            grid-template-columns:150px 1fr;
            align-items:center;
            gap:10px;
        }

        .device-image{
            width:135px;
            height:135px;
            object-fit:contain;
            filter:
                drop-shadow(0 16px 25px rgba(0,0,0,.42))
                drop-shadow(0 0 20px rgba(69,233,154,.14));
        }

        .signal-line{
            position:absolute;
            left:116px;
            right:50px;
            top:50%;
            height:1px;
            background:linear-gradient(90deg,rgba(69,233,154,.62),rgba(69,233,154,.18),transparent);
        }

        .signal-line::before{
            content:"";
            position:absolute;
            top:-3px;
            left:42%;
            width:7px;
            height:7px;
            border-radius:50%;
            background:var(--green);
            box-shadow:0 0 0 8px rgba(69,233,154,.055),0 0 18px var(--green);
            animation:pulse 2.2s ease-in-out infinite;
        }

        .device-info{
            position:relative;
            z-index:2;
        }

        .device-info .mini{
            color:#668075;
            font:600 8px var(--mono);
            letter-spacing:.12em;
            text-transform:uppercase;
        }

        .device-info h2{
            margin:7px 0 5px;
            color:#e3eee7;
            font:600 22px var(--display);
            letter-spacing:-.045em;
        }

        .device-info p{
            margin:0;
            color:#71887c;
            font-size:11px;
        }

        .chips{
            display:flex;
            gap:7px;
            flex-wrap:wrap;
            margin-top:13px;
        }

        .chips span{
            padding:5px 7px;
            border:1px solid rgba(255,255,255,.07);
            border-radius:7px;
            color:#789187;
            background:rgba(255,255,255,.025);
            font:500 8px var(--mono);
        }

        /* PROCESS */

        .process-title{
            position:relative;
            z-index:2;
            display:flex;
            align-items:center;
            gap:12px;
            margin-top:30px;
            color:#71897d;
            font:600 8px var(--mono);
            letter-spacing:.13em;
            text-transform:uppercase;
        }

        .process-title::after{
            content:"";
            height:1px;
            flex:1;
            background:rgba(255,255,255,.055);
        }

        .steps{
            position:relative;
            z-index:2;
            display:grid;
            grid-template-columns:repeat(3,1fr);
            margin-top:17px;
        }

        .step{
            position:relative;
            min-width:0;
            padding-right:24px;
        }

        .step:not(:last-child)::after{
            content:"";
            position:absolute;
            top:15px;
            left:39px;
            right:18px;
            height:1px;
            background:linear-gradient(90deg,rgba(69,233,154,.32),rgba(69,233,154,.04));
        }

        .step-number{
            position:relative;
            z-index:2;
            width:31px;
            height:31px;
            display:grid;
            place-items:center;
            border:1px solid rgba(69,233,154,.34);
            border-radius:9px;
            background:#0b2117;
            color:var(--green);
            font:600 8px var(--mono);
        }

        .step strong{
            display:block;
            margin-top:10px;
            color:#dce8e1;
            font-size:11px;
        }

        .step p{
            max-width:180px;
            margin:5px 0 0;
            color:#6c8378;
            font-size:10px;
            line-height:1.55;
        }

        .visual-bottom{
            position:relative;
            z-index:2;
            display:flex;
            flex-wrap:wrap;
            gap:18px;
            margin-top:auto;
            padding-top:32px;
            color:#587166;
            font:600 8px var(--mono);
            letter-spacing:.10em;
            text-transform:uppercase;
        }

        .visual-bottom span{
            display:flex;
            align-items:center;
            gap:7px;
        }

        .visual-bottom i{
            width:6px;
            height:6px;
            border-radius:50%;
            background:var(--green);
            box-shadow:0 0 9px var(--green);
        }

        /* RIGHT */

        .form-side{
            position:relative;
            padding:46px 44px 38px;
            background:
                linear-gradient(180deg,rgba(255,255,255,.022),transparent 35%),
                rgba(5,12,9,.58);
        }

        .form-side::before{
            content:"SETUP 01 / DEVICE";
            position:absolute;
            top:27px;
            right:38px;
            color:#4c6258;
            font:600 7px var(--mono);
            letter-spacing:.13em;
        }

        .form-kicker{
            display:flex;
            align-items:center;
            gap:8px;
            margin-bottom:11px;
            color:#71897d;
            font:600 8px var(--mono);
            letter-spacing:.13em;
            text-transform:uppercase;
        }

        .form-kicker::before{
            content:"";
            width:17px;
            height:1px;
            background:#617c6e;
        }

        .form-head h2{
            margin:0;
            font:600 31px/1 var(--display);
            letter-spacing:-.055em;
        }

        .form-head p{
            max-width:410px;
            margin:10px 0 0;
            color:var(--muted);
            font-size:12px;
            line-height:1.65;
        }

        .form-divider{
            height:1px;
            margin:27px 0 25px;
            background:var(--line);
        }

        .message{
            margin-bottom:19px;
            padding:12px 13px;
            border:1px solid rgba(255,95,95,.22);
            border-radius:10px;
            color:#ffc3c3;
            background:rgba(255,80,80,.075);
            font-size:11px;
            line-height:1.5;
        }

        .form-group{
            margin-bottom:19px;
        }

        .form-group label{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            margin-bottom:8px;
            color:#c0d0c7;
            font-size:10px;
            font-weight:700;
        }

        .form-group label span{
            color:#5d7468;
            font-size:8px;
            font-weight:500;
        }

        .input{
            width:100%;
            min-height:49px;
            padding:12px 14px;
            border:1px solid var(--line);
            border-radius:11px;
            outline:none;
            color:var(--text);
            background:#09150f;
            font:500 12px var(--body);
            transition:border-color .2s,background .2s,box-shadow .2s,transform .2s;
        }

        .input:hover{
            border-color:var(--line-2);
        }

        .input:focus{
            border-color:rgba(69,233,154,.56);
            background:#0b1912;
            box-shadow:0 0 0 4px rgba(69,233,154,.065),0 12px 30px rgba(0,0,0,.13);
        }

        .input::placeholder{
            color:#536a5e;
        }

        .field-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:12px;
        }

        .field-note{
            display:flex;
            align-items:center;
            gap:7px;
            margin-top:7px;
            color:#5b7266;
            font:500 8px var(--mono);
        }

        .field-note i{
            width:5px;
            height:5px;
            border-radius:50%;
            background:#587264;
        }

        .submit-button{
            width:100%;
            min-height:51px;
            margin-top:2px;
            border:0;
            border-radius:11px;
            cursor:pointer;
            color:#06150c;
            background:linear-gradient(135deg,#5af0a2,#20c878);
            font:700 11px var(--body);
            box-shadow:0 16px 34px rgba(31,199,122,.14);
            transition:transform .2s,box-shadow .2s,filter .2s;
        }

        .submit-button:hover{
            transform:translateY(-1px);
            filter:saturate(1.05);
            box-shadow:0 19px 42px rgba(31,199,122,.22);
        }

        .submit-button:active{
            transform:translateY(0);
        }

        /* SIDE SUMMARY */

        .summary{
            margin-top:21px;
            padding:16px;
            border:1px solid rgba(255,255,255,.07);
            border-radius:12px;
            background:rgba(255,255,255,.018);
        }

        .summary-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            margin-bottom:13px;
        }

        .summary-head strong{
            color:#a7bbb0;
            font:700 8px var(--mono);
            letter-spacing:.13em;
            text-transform:uppercase;
        }

        .summary-head span{
            display:flex;
            align-items:center;
            gap:6px;
            color:#6f897b;
            font-size:8px;
            font-weight:600;
        }

        .summary-head i{
            width:5px;
            height:5px;
            border-radius:50%;
            background:#728b7e;
        }

        .summary-grid{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:7px;
        }

        .summary-item{
            min-width:0;
            padding:10px;
            border-radius:8px;
            background:rgba(0,0,0,.13);
        }

        .summary-item small{
            display:block;
            margin-bottom:5px;
            color:#52685d;
            font:600 7px var(--mono);
            letter-spacing:.08em;
            text-transform:uppercase;
        }

        .summary-item strong{
            display:block;
            overflow:hidden;
            color:#9aafa3;
            font:500 9px var(--mono);
            text-overflow:ellipsis;
            white-space:nowrap;
        }

        .helper{
            margin:14px 0 0;
            color:#61786c;
            font-size:9px;
            line-height:1.65;
        }

        .helper strong{
            color:#91a99d;
        }

        .actions{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:9px;
            margin-top:23px;
            padding-top:19px;
            border-top:1px solid var(--line);
        }

        .secondary{
            min-height:40px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border:1px solid var(--line);
            border-radius:10px;
            color:#a8b9b0;
            background:rgba(255,255,255,.018);
            text-decoration:none;
            font-size:9px;
            font-weight:700;
            transition:transform .2s,border-color .2s,background .2s,color .2s;
        }

        .secondary:hover{
            transform:translateY(-1px);
            color:#e5efe9;
            border-color:rgba(69,233,154,.28);
            background:rgba(69,233,154,.045);
        }

        :focus-visible{
            outline:2px solid rgba(69,233,154,.58);
            outline-offset:3px;
        }

        /* ANIMATION */

        @keyframes pulse{
            0%,100%{opacity:.55;transform:scale(.8)}
            50%{opacity:1;transform:scale(1.12)}
        }

        @keyframes scan{
            0%{transform:translateX(-100%);opacity:0}
            15%{opacity:1}
            50%,100%{transform:translateX(100%);opacity:0}
        }

        @keyframes reveal{
            from{opacity:0;transform:translateY(9px)}
            to{opacity:1;transform:translateY(0)}
        }

        .visual > *,
        .form-side > *{
            animation:reveal .5s ease both;
        }

        .visual > *:nth-child(2){animation-delay:.04s}
        .visual > *:nth-child(3){animation-delay:.08s}
        .visual > *:nth-child(4){animation-delay:.12s}
        .visual > *:nth-child(5){animation-delay:.16s}
        .form-side > *:nth-child(2){animation-delay:.04s}
        .form-side > *:nth-child(3){animation-delay:.08s}
        .form-side > *:nth-child(4){animation-delay:.12s}

        @media(prefers-reduced-motion:reduce){
            *,*::before,*::after{
                animation-duration:.001ms !important;
                animation-iteration-count:1 !important;
                transition-duration:.001ms !important;
            }
        }

        /* RESPONSIVE */

        @media(max-width:1120px){
            .workspace{
                grid-template-columns:1fr;
                min-height:auto;
            }

            .visual{
                border-right:0;
                border-bottom:1px solid var(--line);
            }

            .visual-bottom{
                margin-top:30px;
            }

            .form-side{
                max-width:none;
            }
        }

        @media(max-width:720px){
            .page{
                width:min(100% - 24px,1280px);
                padding:15px 0 22px;
            }

            .topbar{
                margin-bottom:14px;
            }

            .meta-code{
                display:none;
            }

            .workspace{
                border-radius:19px;
            }

            .visual{
                padding:34px 24px 28px;
            }

            h1{
                font-size:clamp(39px,10.8vw,57px);
            }

            .lead{
                font-size:13px;
            }

            .device-card{
                min-height:auto;
                margin-top:27px;
            }

            .device-body{
                grid-template-columns:105px 1fr;
                min-height:145px;
            }

            .device-image{
                width:95px;
                height:95px;
            }

            .signal-line{
                left:85px;
                right:24px;
            }

            .device-info h2{
                font-size:18px;
            }

            .steps{
                grid-template-columns:1fr;
                gap:18px;
            }

            .step{
                display:grid;
                grid-template-columns:31px 1fr;
                column-gap:12px;
                padding:0;
            }

            .step:not(:last-child)::after{
                top:31px;
                left:15px;
                right:auto;
                width:1px;
                height:27px;
                background:linear-gradient(180deg,rgba(69,233,154,.30),rgba(69,233,154,.04));
            }

            .step strong{
                margin-top:1px;
            }

            .step p{
                max-width:none;
            }

            .form-side{
                padding:34px 24px 27px;
            }

            .form-side::before{
                display:none;
            }
        }

        @media(max-width:480px){
            .brand-text small{
                font-size:7px;
            }

            .brand-text strong{
                font-size:15px;
            }

            .session{
                padding:7px 9px;
                font-size:8px;
            }

            .visual{
                padding:30px 19px 25px;
            }

            .device-body{
                grid-template-columns:1fr;
                gap:7px;
                padding:16px 0;
            }

            .device-image{
                width:78px;
                height:78px;
            }

            .signal-line{
                display:none;
            }

            .device-info h2{
                font-size:17px;
            }

            .field-grid{
                grid-template-columns:1fr;
                gap:0;
            }

            .summary-grid{
                grid-template-columns:1fr;
            }

            .form-side{
                padding:29px 19px 24px;
            }

            .form-head h2{
                font-size:27px;
            }

            .actions{
                grid-template-columns:1fr;
            }

            .visual::after{
                font-size:90px;
            }
        }
    </style>
</head>

<body>

<main class="page">

    <header class="topbar">
        <a class="brand" href="home.php" aria-label="Voltar ao Aeris Guard">
            <span class="brand-mark">A</span>

            <span class="brand-text">
                <small>Enterprise IoT</small>
                <strong>Aeris Guard</strong>
            </span>
        </a>

        <div class="top-meta">
            <span class="meta-code">Device setup / secure connection</span>

            <span class="session">
                <i></i>
                Sessão ativa
            </span>
        </div>
    </header>

    <section class="workspace">

        <div class="visual">

            <div class="eyebrow">Aeris Signal / Instalação</div>

            <h1>
                Dê uma identidade ao seu
                <span>dispositivo.</span>
            </h1>

            <p class="lead">
                Vincule o hardware à sua conta e deixe o Aeris Guard cuidar
                da organização das informações. O cadastro é o ponto de
                entrada entre o dispositivo físico e o painel de monitoramento.
            </p>

            <div class="device-card">

                <div class="device-head">
                    <small>Dispositivo / nó de monitoramento</small>

                    <span class="device-status">
                        <i></i>
                        pronto para configurar
                    </span>
                </div>

                <div class="device-body">

                    <img
                        class="device-image"
                        src="../../img/esp32.svg"
                        alt="Placa ESP32 do projeto"
                    >

                    <div class="signal-line"></div>

                    <div class="device-info">

                        <div class="mini">Hardware / identidade</div>

                        <h2>ESP32 · MQ135</h2>

                        <p>Unidade responsável pela coleta e comunicação.</p>

                        <div class="chips">
                            <span>ESP32</span>
                            <span>Wi-Fi</span>
                            <span>MQ135</span>
                        </div>

                    </div>

                </div>
            </div>

            <div class="process-title">
                Como funciona
            </div>

            <div class="steps">

                <article class="step">
                    <span class="step-number">01</span>

                    <div>
                        <strong>Identifique</strong>
                        <p>Use o código exclusivo gravado no dispositivo.</p>
                    </div>
                </article>

                <article class="step">
                    <span class="step-number">02</span>

                    <div>
                        <strong>Configure</strong>
                        <p>Defina nome, firmware e estado da conexão.</p>
                    </div>
                </article>

                <article class="step">
                    <span class="step-number">03</span>

                    <div>
                        <strong>Monitore</strong>
                        <p>O projeto passa a fazer parte do seu painel.</p>
                    </div>
                </article>

            </div>

            <div class="visual-bottom">
                <span><i></i> IoT conectado</span>
                <span><i></i> Identidade única</span>
                <span><i></i> Aeris Signal</span>
            </div>

        </div>

        <div class="form-side">

            <div class="form-head">

                <div class="form-kicker">
                    Configuração
                </div>

                <h2>Novo projeto</h2>

                <p>
                    Preencha os dados do dispositivo que será associado
                    ao seu usuário.
                </p>

            </div>

            <div class="form-divider"></div>

            <?php if ($error): ?>
                <div class="message">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($apiKeyCriada): ?>
                <div class="message" style="border-color:rgba(69,233,154,.4);color:#a8f2c8;background:rgba(69,233,154,.08)">
                    <strong><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p style="margin:10px 0 4px">API Key do ESP32 (mostrada apenas uma vez, copie agora):</p>
                    <code style="display:block;padding:10px 12px;border-radius:8px;background:rgba(0,0,0,.35);word-break:break-all;font-family:var(--mono);font-size:12px;color:#fff"><?php echo htmlspecialchars($apiKeyCriada, ENT_QUOTES, 'UTF-8'); ?></code>
                    <p style="margin:10px 0 0">Use esse valor e o ID do ESP32 informado no firmware para configurar o envio de leituras.</p>
                </div>
            <?php endif; ?>

            <form
                method="post"
                action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? 'project-connect.php', ENT_QUOTES, 'UTF-8'); ?>"
            >

                <div class="form-group">

                    <label for="name">
                        Nome do projeto
                        <span>Obrigatório</span>
                    </label>

                    <input
                        class="input"
                        id="name"
                        type="text"
                        name="name"
                        placeholder="Ex.: Monitoramento da cozinha"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="manufacturer_code">
                        Código do dispositivo
                        <span>Obrigatório</span>
                    </label>

                    <input
                        class="input"
                        id="manufacturer_code"
                        type="text"
                        name="manufacturer_code"
                        placeholder="AERIS-MQ6-001"
                        required
                    >

                    <div class="field-note">
                        <i></i>
                        Código usado para identificar o hardware.
                    </div>

                </div>

                <div class="field-grid">

                    <div class="form-group">

                        <label for="firmware_version">
                            Firmware
                            <span>Opcional</span>
                        </label>

                        <input
                            class="input"
                            id="firmware_version"
                            type="text"
                            name="firmware_version"
                            placeholder="Ex.: 1.0.0"
                        >

                    </div>

                    <div class="form-group">

                        <label for="esp32_id">
                            ID do ESP32
                            <span>Obrigatório</span>
                        </label>

                        <input
                            class="input"
                            id="esp32_id"
                            type="text"
                            name="esp32_id"
                            placeholder="ESP32-MQ6-001"
                            required
                        >

                    </div>

                </div>

                <button type="submit" class="submit-button">
                    Adicionar ao monitoramento
                </button>

            </form>

            <div class="summary">

                <div class="summary-head">

                    <strong>Resumo da configuração</strong>

                    <span>
                        <i></i>
                        aguardando envio
                    </span>

                </div>

                <div class="summary-grid">

                    <div class="summary-item">
                        <small>Hardware</small>
                        <strong>ESP32</strong>
                    </div>

                    <div class="summary-item">
                        <small>Sensor</small>
                        <strong>MQ-6</strong>
                    </div>

                    <div class="summary-item">
                        <small>Destino</small>
                        <strong>Dashboard</strong>
                    </div>

                </div>

            </div>

            <p class="helper">
                <strong>Depois do cadastro:</strong>
                o projeto será associado à sua conta e poderá aparecer
                no dashboard para acompanhamento das leituras.
            </p>

            <div class="actions">
                <a class="secondary" href="home.php">Voltar ao dashboard</a>
                <a class="secondary" href="logout.php">Sair</a>
            </div>

        </div>

    </section>

</main>

</body>
</html>
