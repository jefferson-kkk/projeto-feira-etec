#
# setup\verify.ps1
# Somente VERIFICA o ambiente -- nao instala, nao altera, nao inicia
# nada. Serve para checar, depois do setup, se a maquina continua
# corretamente configurada (por exemplo depois de meses, ou numa
# maquina que outra pessoa preparou).
#

$ErrorActionPreference = 'Continue'
. (Join-Path $PSScriptRoot 'lib.ps1')

Write-Host ''
Write-Host '========================================' -ForegroundColor Cyan
Write-Host ' SADAG - VERIFICACAO DO AMBIENTE (somente leitura)' -ForegroundColor Cyan
Write-Host '========================================' -ForegroundColor Cyan
Write-Host "Projeto: $script:ProjectRoot"
Write-Host ''

$fail = 0
function Report {
    param([string]$Label, [bool]$Ok, [string]$Detail = '')
    $tag = if ($Ok) { '[OK]' } else { '[FALHA]' }
    $color = if ($Ok) { 'Green' } else { 'Red' }
    Write-Host ("{0,-34} {1}  {2}" -f $Label, $tag, $Detail) -ForegroundColor $color
    if (-not $Ok) { $script:fail++ }
}
function ReportInfo {
    param([string]$Label, [string]$Detail)
    Write-Host ("{0,-34} {1}" -f $Label, $Detail) -ForegroundColor DarkGray
}

Write-Host '--- Ferramentas ---'
$git = Test-GitEnv
Report 'Git' $git.Found $git.Version
if ($git.Found) {
    Report '  git config user.name/email' ([bool]($git.UserName -and $git.UserEmail)) "$($git.UserName) <$($git.UserEmail)>"
    ReportInfo '  remote' $git.Remote
}
$gh = Test-GhCli
ReportInfo 'GitHub CLI' $(if ($gh.Found) { "$($gh.Version) -- autenticado: $($gh.AuthOk)" } else { 'nao instalado (opcional)' })

$vscode = Test-VSCodeEnv
Report 'VS Code' $vscode.Found $vscode.Version
if ($vscode.Found) {
    foreach ($ext in $script:RequiredVsCodeExtensions) {
        Report "  extensao: $($ext.Name)" ($vscode.Extensions -contains $ext.Id)
    }
}

Write-Host ''
Write-Host '--- PHP ---'
$php = Test-PhpEnv
Report 'PHP instalado' $php.Found ($php.Version -join ' ')
if ($php.Found) {
    Report '  Extensoes obrigatorias (pdo_mysql, curl, openssl, fileinfo, json, session)' ($php.Missing.Count -eq 0) $(if ($php.Missing.Count -gt 0) { "faltando: $($php.Missing -join ', ')" } else { 'todas presentes' })
    if ($php.OptionalMissing.Count -gt 0) { ReportInfo '  Extensoes opcionais ausentes' ($php.OptionalMissing -join ', ') }
    $phpCgi = Test-PhpCgiEnv
    Report '  php-cgi.exe no PATH' $phpCgi.Found
}

Write-Host ''
Write-Host '--- Banco de dados ---'
$mysql = Test-MySqlEnv
Report 'Servico MySQL/MariaDB encontrado' ([bool]$mysql.ServiceName) $mysql.ServiceName
if ($mysql.ServiceName) { Report '  Servico em execucao' $mysql.ServiceRunning }
Report 'Porta 3306 ativa' $mysql.PortListening
Report 'Conexao PDO (root / banco aeris)' $mysql.CanConnect
if ($mysql.CanConnect) {
    $expectedTables = @('login', 'devices', 'locations', 'sensor_readings', 'support_tickets', 'support_messages', 'notifications')
    $missingTables = $expectedTables | Where-Object { $mysql.TablesFound -notcontains $_ }
    Report '  Tabelas principais presentes' ($missingTables.Count -eq 0) $(if ($missingTables.Count -gt 0) { "faltando: $($missingTables -join ', ')" } else { "$($mysql.TablesFound.Count) tabelas" })
} elseif ($mysql.ConnectError) {
    ReportInfo '  Erro de conexao' (Protect-Secret $mysql.ConnectError)
}

Write-Host ''
Write-Host '--- Caddy / servidor local ---'
$caddy = Test-CaddyEnv
Report 'tools\caddy.exe presente' $caddy.LocalExeFound
Report 'tools\Caddyfile presente' $caddy.CaddyfileFound
if ($caddy.LocalExeFound -and $caddy.CaddyfileFound) { Report '  caddy validate' $caddy.ValidateOk $caddy.ValidateOutput }
Report 'Porta 8000 respondendo' $caddy.PortListening
Report 'Porta 9123 (php-cgi) respondendo' (Test-TcpPortListening 9123)

$backendOk = $false
$backendDetail = ''
try {
    $resp = Invoke-WebRequest -Uri 'http://localhost:8000/view/html/acesso.php' -UseBasicParsing -TimeoutSec 5
    $backendOk = ($resp.StatusCode -eq 200)
    $backendDetail = "HTTP $($resp.StatusCode)"
} catch {
    $backendDetail = $_.Exception.Message
}
Report 'Backend responde (acesso.php)' $backendOk $backendDetail

Write-Host ''
Write-Host '--- Node / Composer / Flutter (deve estar ausente por design) ---'
ReportInfo 'package.json no projeto' $(if (Test-NodeToolingNeeded) { 'ENCONTRADO -- ver auditoria' } else { 'nao existe (esperado)' })
ReportInfo 'composer.json no projeto' $(if (Test-ComposerToolingNeeded) { 'ENCONTRADO -- ver auditoria' } else { 'nao existe (esperado)' })
ReportInfo 'pubspec.yaml no projeto' $(if (Test-FlutterProjectExists) { 'ENCONTRADO -- ver auditoria' } else { 'nao existe ainda (esperado)' })

Write-Host ''
Write-Host '--- Arduino / ESP32 (opcional) ---'
$arduino = Test-ArduinoEnv
ReportInfo 'Arduino IDE' $(if ($arduino.IdeFound) { $arduino.IdePath } else { 'nao instalada (opcional para dev do dashboard)' })
ReportInfo 'arduino-cli' $(if ($arduino.CliFound) { $arduino.CliPath } else { 'nao instalado' })
$esp32Libs = Get-Esp32LibraryPlan
foreach ($lib in $esp32Libs) { ReportInfo "  biblioteca necessaria" $lib.Library }

Write-Host ''
Write-Host '--- Portas ---'
foreach ($p in (Get-PortsSummary)) {
    Report ("Porta {0} ({1})" -f $p.Porta, $p.Servico) $p.Ativa $p.Funcao
}

Write-Host ''
if ($fail -eq 0) {
    Write-Host 'Ambiente verificado: tudo que e critico esta OK.' -ForegroundColor Green
    exit 0
} else {
    Write-Host "Ambiente verificado: $fail item(ns) com [FALHA]. Rode setup\install.bat para corrigir o que for possivel automaticamente." -ForegroundColor Red
    exit 1
}
