<?php

session_start();



if (!isset($_SERVER) || !is_array($_SERVER)) {
    $_SERVER = [];
}

session_start();

if (
    empty($_SESSION['user_id']) &&
    empty($_SESSION['id']) &&
    empty($_SESSION['login_id'])
) {
    header('Location: acesso.php');
    exit;
}

$userId =
    $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? $_SESSION['login_id'];

require_once __DIR__ . '/../../Controller/DeviceController.php';
require_once __DIR__ . '/../../Controller/LocationController.php';

$deviceController = new DeviceController();
$locationController = new LocationController();

$mensagem = null;
$tipoMensagem = 'success';
$apiKeyCriada = null;

try {
    $dispositivos =
        $deviceController->getAllByUser(
            (int)$userId
        );

    $locais =
        $locationController->getByUser(
            (int)$userId
        );
} catch (Throwable $e) {
    $dispositivos = [];
    $locais = [];

    $mensagem =
        'Erro ao carregar os dispositivos: ' .
        $e->getMessage();

    $tipoMensagem = 'error';
}


/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    try {

        /*
        |--------------------------------------------------------------------------
        | CRIAR DISPOSITIVO
        |--------------------------------------------------------------------------
        */

        if ($acao === 'criar') {

            $name = trim(
                $_POST['name'] ?? ''
            );

            $manufacturerCode = trim(
                $_POST['manufacturer_code'] ?? ''
            );

            $esp32Id = trim(
                $_POST['esp32_id'] ?? ''
            );

            $firmwareVersion = trim(
                $_POST['firmware_version'] ?? ''
            );

            $description = trim(
                $_POST['description'] ?? ''
            );

            $locationId =
                !empty($_POST['location_id'])
                    ? (int)$_POST['location_id']
                    : null;


            if ($name === '') {
                throw new Exception(
                    'Informe o nome do dispositivo.'
                );
            }

            if ($manufacturerCode === '') {
                throw new Exception(
                    'Informe o código do dispositivo.'
                );
            }

            if ($esp32Id === '') {
                throw new Exception(
                    'Informe o ID do ESP32.'
                );
            }


            $resultado =
                $deviceController->createDevice(
                    (int)$userId,
                    $name,
                    $manufacturerCode,
                    $esp32Id,
                    $locationId,
                    $description,
                    $firmwareVersion,
                    'MQ135'
                );


            $apiKeyCriada =
                $resultado['api_key'];

            $mensagem =
                'Dispositivo cadastrado com sucesso.';

            $tipoMensagem = 'success';
        }


        /*
        |--------------------------------------------------------------------------
        | EDITAR DISPOSITIVO
        |--------------------------------------------------------------------------
        */

        elseif ($acao === 'editar') {

            $id = (int)(
                $_POST['id'] ?? 0
            );

            if ($id <= 0) {
                throw new Exception(
                    'Dispositivo inválido.'
                );
            }

            $name = trim(
                $_POST['name'] ?? ''
            );

            $manufacturerCode = trim(
                $_POST['manufacturer_code'] ?? ''
            );

            $esp32Id = trim(
                $_POST['esp32_id'] ?? ''
            );

            $locationId =
                !empty($_POST['location_id'])
                    ? (int)$_POST['location_id']
                    : null;

            $description = trim(
                $_POST['description'] ?? ''
            );

            $firmwareVersion = trim(
                $_POST['firmware_version'] ?? ''
            );

            $status = trim(
                $_POST['status'] ?? 'offline'
            );


            $deviceController->updateDevice(
                (int)$userId,
                $id,
                $name,
                $manufacturerCode,
                $esp32Id,
                $locationId,
                $description,
                $status,
                $firmwareVersion,
                'MQ135'
            );


            $mensagem =
                'Dispositivo atualizado com sucesso.';

            $tipoMensagem = 'success';
        }


        /*
        |--------------------------------------------------------------------------
        | EXCLUIR DISPOSITIVO
        |--------------------------------------------------------------------------
        */

        elseif ($acao === 'excluir') {

            $id = (int)(
                $_POST['id'] ?? 0
            );

            if ($id <= 0) {
                throw new Exception(
                    'Dispositivo inválido.'
                );
            }

            $deviceController->deleteDevice(
                $id,
                (int)$userId
            );

            $mensagem =
                'Dispositivo excluído com sucesso.';

            $tipoMensagem = 'success';
        }


        else {

            throw new Exception(
                'Ação não reconhecida.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Recarregar dados
        |--------------------------------------------------------------------------
        */

        $dispositivos =
            $deviceController->getAllByUser(
                (int)$userId
            );

        $locais =
            $locationController->getByUser(
                (int)$userId
            );

    } catch (Throwable $e) {

        $mensagem =
            $e->getMessage();

        $tipoMensagem = 'error';
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Aeris Guard — Dispositivos</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #070b13;
            color: #f4f7fb;
            font-family: Arial, sans-serif;
        }

        .container {
            width: min(1100px, 100%);
            margin: auto;
        }

        h1 {
            margin-top: 0;
        }

        .subtitle {
            color: #96a5bb;
            margin-bottom: 25px;
        }

        .card {
            background: #111827;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 20px;
        }

        .message {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .message.success {
            background: rgba(54,214,122,.10);
            border: 1px solid rgba(54,214,122,.25);
            color: #7ff0a8;
        }

        .message.error {
            background: rgba(255,87,87,.10);
            border: 1px solid rgba(255,87,87,.25);
            color: #ff9c9c;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .field {
            display: grid;
            gap: 6px;
            margin-bottom: 14px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            color: #96a5bb;
            font-size: 12px;
            font-weight: bold;
        }

        input,
        textarea,
        select {
            width: 100%;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 9px;
            padding: 11px 12px;
            background: #0b1321;
            color: #f4f7fb;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        button {
            border: 0;
            border-radius: 9px;
            padding: 11px 16px;
            cursor: pointer;
            font-weight: bold;
        }

        .primary {
            background: #20d7b2;
            color: #03150f;
        }

        .danger {
            background: rgba(255,87,87,.12);
            color: #ff9b9b;
            border: 1px solid rgba(255,87,87,.25);
        }

        .device {
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.02);
            border-radius: 12px;
            padding: 18px;
            margin-top: 14px;
        }

        .device-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .device h3 {
            margin: 0;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 15px 0;
        }

        .meta-box {
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 8px;
            padding: 10px;
        }

        .meta-box span {
            display: block;
            color: #96a5bb;
            font-size: 10px;
            margin-bottom: 5px;
        }

        .online {
            color: #36d67a;
        }

        .offline {
            color: #ff5757;
        }

        .api-box {
            margin-top: 20px;
            padding: 16px;
            background: rgba(32,215,178,.07);
            border: 1px solid rgba(32,215,178,.25);
            border-radius: 10px;
        }

        .api-key {
            margin-top: 10px;
            padding: 12px;
            background: #070b13;
            border-radius: 8px;
            font-family: monospace;
            word-break: break-all;
            color: #20d7b2;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        hr {
            border: 0;
            border-top: 1px solid rgba(255,255,255,.08);
            margin: 20px 0;
        }

        @media (max-width: 700px) {

            body {
                padding: 15px;
            }

            .grid,
            .meta {
                grid-template-columns: 1fr;
            }

            .device-header {
                flex-direction: column;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Dispositivos</h1>

    <p class="subtitle">
        Gerencie os ESP32 conectados ao Aeris Guard.
    </p>


    <?php if ($mensagem): ?>

        <div class="message <?= $tipoMensagem ?>">
            <?= htmlspecialchars(
                $mensagem,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

    <?php endif; ?>


    <?php if ($apiKeyCriada): ?>

        <div class="api-box">

            <strong>
                API Key gerada
            </strong>

            <p>
                Essa chave será utilizada pelo ESP32
                para enviar as leituras para a API.
                Guarde-a, pois o sistema armazena
                apenas o hash dela.
            </p>

            <div class="api-key">
                <?= htmlspecialchars(
                    $apiKeyCriada,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        </div>

    <?php endif; ?>


    <!-- CADASTRO -->

    <div class="card">

        <h2>Cadastrar dispositivo</h2>

        <form method="POST">

            <input
                type="hidden"
                name="acao"
                value="criar"
            >

            <div class="grid">

                <div class="field">

                    <label>
                        Nome do dispositivo
                    </label>

                    <input
                        type="text"
                        name="name"
                        required
                        placeholder="Detector Laboratório"
                    >

                </div>


                <div class="field">

                    <label>
                        Código do dispositivo
                    </label>

                    <input
                        type="text"
                        name="manufacturer_code"
                        required
                        placeholder="AERIS-MQ135-001"
                    >

                </div>


                <div class="field">

                    <label>
                        ID do ESP32
                    </label>

                    <input
                        type="text"
                        name="esp32_id"
                        required
                        placeholder="ESP32-MQ135-001"
                    >

                </div>


                <div class="field">

                    <label>
                        Firmware
                    </label>

                    <input
                        type="text"
                        name="firmware_version"
                        placeholder="1.0.0"
                    >

                </div>


                <div class="field">

                    <label>
                        Ambiente
                    </label>

                    <select
                        name="location_id"
                    >

                        <option value="">
                            Sem ambiente
                        </option>

                        <?php foreach ($locais as $local): ?>

                            <option
                                value="<?= (int)$local->getId() ?>"
                            >
                                <?= htmlspecialchars(
                                    $local->getName(),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="field">

                    <label>
                        Sensor
                    </label>

                    <input
                        type="text"
                        value="MQ135"
                        disabled
                    >

                </div>


                <div class="field full">

                    <label>
                        Descrição
                    </label>

                    <textarea
                        name="description"
                        placeholder="Descrição do dispositivo..."
                    ></textarea>

                </div>

            </div>

            <button
                type="submit"
                class="primary"
            >
                Cadastrar dispositivo
            </button>

        </form>

    </div>


    <!-- DISPOSITIVOS -->

    <div class="card">

        <h2>
            Dispositivos cadastrados
        </h2>

        <?php if (empty($dispositivos)): ?>

            <p class="subtitle">
                Nenhum dispositivo cadastrado.
            </p>

        <?php else: ?>

            <?php foreach ($dispositivos as $device): ?>

                <div class="device">

                    <div class="device-header">

                        <div>

                            <h3>
                                <?= htmlspecialchars(
                                    $device['name'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h3>

                            <p class="subtitle">
                                MQ135
                            </p>

                        </div>

                        <strong
                            class="<?= ($device['status'] ?? 'offline') === 'online'
                                ? 'online'
                                : 'offline' ?>"
                        >
                            <?= htmlspecialchars(
                                strtoupper(
                                    $device['status'] ?? 'offline'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                    </div>


                    <div class="meta">

                        <div class="meta-box">

                            <span>
                                ESP32
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $device['esp32_id'] ?? '--',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                        </div>


                        <div class="meta-box">

                            <span>
                                Código
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $device['manufacturer_code'] ?? '--',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                        </div>


                        <div class="meta-box">

                            <span>
                                Ambiente
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $device['location_name']
                                        ?? 'Sem ambiente',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                        </div>


                        <div class="meta-box">

                            <span>
                                IP
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $device['last_seen_ip']
                                        ?? '--',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                        </div>


                        <div class="meta-box">

                            <span>
                                Wi-Fi
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $device['wifi_status']
                                        ?? '--',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                        </div>


                        <div class="meta-box">

                            <span>
                                Última comunicação
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $device['last_seen_at']
                                        ?? '--',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                        </div>

                    </div>


                    <hr>


                    <!-- EDIÇÃO -->

                    <h4>
                        Editar informações
                    </h4>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="acao"
                            value="editar"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int)$device['id'] ?>"
                        >

                        <div class="grid">

                            <div class="field">

                                <label>
                                    Nome
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    required
                                    value="<?= htmlspecialchars(
                                        $device['name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>


                            <div class="field">

                                <label>
                                    Código
                                </label>

                                <input
                                    type="text"
                                    name="manufacturer_code"
                                    required
                                    value="<?= htmlspecialchars(
                                        $device['manufacturer_code'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>


                            <div class="field">

                                <label>
                                    ESP32 ID
                                </label>

                                <input
                                    type="text"
                                    name="esp32_id"
                                    required
                                    value="<?= htmlspecialchars(
                                        $device['esp32_id'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>


                            <div class="field">

                                <label>
                                    Firmware
                                </label>

                                <input
                                    type="text"
                                    name="firmware_version"
                                    value="<?= htmlspecialchars(
                                        $device['firmware_version'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>


                            <div class="field">

                                <label>
                                    Ambiente
                                </label>

                                <select
                                    name="location_id"
                                >

                                    <option value="">
                                        Sem ambiente
                                    </option>

                                    <?php foreach ($locais as $local): ?>

                                        <option
                                            value="<?= (int)$local->getId() ?>"
                                            <?= (
                                                (int)($device['location_id'] ?? 0)
                                                ===
                                                (int)$local->getId()
                                            )
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= htmlspecialchars(
                                                $local->getName(),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <div class="field">

                                <label>
                                    Status
                                </label>

                                <select
                                    name="status"
                                >

                                    <option
                                        value="offline"
                                        <?= ($device['status'] ?? '') === 'offline'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Offline
                                    </option>

                                    <option
                                        value="online"
                                        <?= ($device['status'] ?? '') === 'online'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Online
                                    </option>

                                </select>

                            </div>


                            <div class="field full">

                                <label>
                                    Descrição
                                </label>

                                <textarea
                                    name="description"
                                ><?= htmlspecialchars(
                                    $device['description'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?></textarea>

                            </div>

                        </div>


                        <div class="actions">

                            <button
                                type="submit"
                                class="primary"
                            >
                                Salvar alterações
                            </button>

                        </div>

                    </form>


                    <hr>


                    <!-- EXCLUIR -->

                    <form method="POST">

                        <input
                            type="hidden"
                            name="acao"
                            value="excluir"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int)$device['id'] ?>"
                        >

                        <button
                            type="submit"
                            class="danger"
                            onclick="return confirm('Deseja realmente excluir este dispositivo?')"
                        >
                            Excluir dispositivo
                        </button>

                    </form>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

</body>

</html>