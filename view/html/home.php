<?php
require_once __DIR__ . '/_auth.php';

/*
 * dashboard.php é o painel real, conectado ao banco (MySQL) e à API
 * (api/app.php, api/devices.php, api/data.php, api/manage.php,
 * api/locations.php). Precisa ser incluído (não lido como arquivo
 * estático) para que o PHP dele seja executado.
 */
require __DIR__ . '/dashboard.php';

