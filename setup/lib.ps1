#
# setup\lib.ps1
# Funcoes compartilhadas por install.ps1 e verify.ps1.
# Nao instala nem altera nada sozinho — so detecta e informa.
# Compativel com Windows PowerShell 5.1 (o que vem por padrao no Windows 10/11).
#

$script:ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$script:LogDir      = Join-Path $PSScriptRoot 'logs'
$script:BackupDir   = Join-Path $PSScriptRoot 'backup'
$script:LogFile     = Join-Path $script:LogDir ('install_{0}.log' -f (Get-Date -Format 'yyyyMMdd_HHmmss'))

function Initialize-SetupDirs {
    if (-not (Test-Path $script:LogDir))    { New-Item -ItemType Directory -Path $script:LogDir -Force | Out-Null }
    if (-not (Test-Path $script:BackupDir)) { New-Item -ItemType Directory -Path $script:BackupDir -Force | Out-Null }
}

function Write-Log {
    param(
        [Parameter(Mandatory = $true)][string]$Message,
        [ValidateSet('INFO', 'OK', 'PULAR', 'INSTALAR', 'AVISO', 'ERRO')][string]$Level = 'INFO'
    )
    $stamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
    $line  = "[$stamp] [$Level] $Message"
    $color = switch ($Level) {
        'OK'       { 'Green' }
        'PULAR'    { 'DarkGray' }
        'INSTALAR' { 'Cyan' }
        'AVISO'    { 'Yellow' }
        'ERRO'     { 'Red' }
        default    { 'Gray' }
    }
    Write-Host "[$Level] $Message" -ForegroundColor $color
    try { Add-Content -Path $script:LogFile -Value $line -Encoding utf8 } catch {}
}

# Nunca escreve segredos no log/console. Chame isto antes de logar
# qualquer string que possa conter senha/token/chave.
function Protect-Secret {
    param([string]$Text)
    if (-not $Text) { return $Text }
    $patterns = @('senha\s*=\s*\S+', 'password\s*=\s*\S+', 'api_key\s*=\s*\S+', 'token\s*=\s*\S+')
    $out = $Text
    foreach ($p in $patterns) { $out = $out -replace $p, '******' }
    return $out
}

function Test-IsAdmin {
    $id = [Security.Principal.WindowsIdentity]::GetCurrent()
    $pr = New-Object Security.Principal.WindowsPrincipal($id)
    return $pr.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Get-ToolPath {
    param([string]$Name)
    $cmd = Get-Command $Name -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    return $null
}

function Test-TcpPortListening {
    param([int]$Port)
    try {
        $conns = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
        return [bool]$conns
    } catch {
        return $false
    }
}

function Backup-FileOnce {
    # So faz backup se o arquivo existir e ainda nao tiver uma copia
    # salva por esta rotina (nome com timestamp, nunca sobrescreve).
    param([Parameter(Mandatory = $true)][string]$Path)
    if (-not (Test-Path $Path)) { return $null }
    Initialize-SetupDirs
    $name = Split-Path $Path -Leaf
    $stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
    $dest = Join-Path $script:BackupDir "$name.$stamp.bak"
    Copy-Item -Path $Path -Destination $dest -Force
    return $dest
}

# ---------------------------------------------------------------
# Deteccao de ferramentas — usada tanto por install quanto verify
# ---------------------------------------------------------------

function Test-GitEnv {
    $path = Get-ToolPath 'git'
    $result = [ordered]@{ Found = $false; Path = $null; Version = $null; UserName = $null; UserEmail = $null; Remote = $null }
    if (-not $path) { return $result }
    $result.Found = $true
    $result.Path = $path
    $result.Version = (& git --version) -replace 'git version ', ''
    $result.UserName = (& git config --global user.name 2>$null)
    $result.UserEmail = (& git config --global user.email 2>$null)
    Push-Location $script:ProjectRoot
    try {
        $isRepo = (& git rev-parse --is-inside-work-tree 2>$null)
        if ($isRepo -eq 'true') {
            $remoteLine = (& git remote -v 2>$null | Select-Object -First 1)
            if ($remoteLine) { $result.Remote = ($remoteLine -split '\s+')[1] }
        }
    } finally { Pop-Location }
    return $result
}

function Test-GhCli {
    $path = Get-ToolPath 'gh'
    $result = [ordered]@{ Found = $false; Path = $null; Version = $null; AuthOk = $false }
    if (-not $path) { return $result }
    $result.Found = $true
    $result.Path = $path
    $result.Version = ((& gh --version 2>$null) -split "`n")[0]
    $authOut = (& gh auth status 2>&1)
    $result.AuthOk = ($LASTEXITCODE -eq 0)
    return $result
}

function Test-VSCodeEnv {
    $path = Get-ToolPath 'code'
    $result = [ordered]@{ Found = $false; Path = $null; Version = $null; Extensions = @() }
    if (-not $path) { return $result }
    $result.Found = $true
    $result.Path = $path
    $result.Version = ((& code --version 2>$null) -split "`n")[0]
    $result.Extensions = (& code --list-extensions 2>$null)
    return $result
}

# Extensoes que o PROJETO justifica de verdade (PHP em quase todo
# arquivo do backend/frontend). O resto fica como sugestao opcional
# no relatorio, nunca instalado sem necessidade comprovada.
$script:RequiredVsCodeExtensions = @(
    @{ Id = 'bmewburn.vscode-intelephense-client'; Name = 'PHP Intelephense'; Reason = 'Projeto e majoritariamente PHP (Controller/Model/DAO/API/views).' }
)
$script:OptionalVsCodeExtensions = @(
    @{ Id = 'xdebug.php-debug'; Name = 'PHP Debug (Xdebug)'; Reason = 'Util para depurar PHP passo a passo, nao obrigatorio.' },
    @{ Id = 'eamodio.gitlens'; Name = 'GitLens'; Reason = 'Historico/blame do Git, conveniencia.' },
    @{ Id = 'esbenp.prettier-vscode'; Name = 'Prettier'; Reason = 'Formatacao de HTML/CSS/JS, nao ha build step que dependa disso.' }
)

function Test-PhpEnv {
    $path = Get-ToolPath 'php'
    $result = [ordered]@{
        Found = $false; Path = $null; Version = $null; IniPath = $null
        Modules = @(); Missing = @(); OptionalMissing = @()
    }
    if (-not $path) { return $result }
    $result.Found = $true
    $result.Path = $path
    $verLine = (& php --version 2>$null | Select-Object -First 1)
    $result.Version = $verLine
    $result.IniPath = (& php --ini 2>$null | Select-String 'Loaded Configuration File:' | ForEach-Object { ($_ -split ':\s*', 2)[1].Trim() })
    $result.Modules = (& php -m 2>$null)

    # Extensoes REALMENTE usadas no codigo (confirmado por grep no
    # projeto, nao por suposicao): pdo_mysql, curl, openssl, fileinfo.
    # mbstring e usado com fallback (function_exists), entao e opcional.
    $required = @('pdo_mysql', 'curl', 'openssl', 'fileinfo', 'json', 'session')
    foreach ($ext in $required) {
        if ($result.Modules -notcontains $ext) { $result.Missing += $ext }
    }
    $optional = @('mbstring')
    foreach ($ext in $optional) {
        if ($result.Modules -notcontains $ext) { $result.OptionalMissing += $ext }
    }
    return $result
}

function Test-MySqlEnv {
    $result = [ordered]@{
        ServiceName = $null; ServiceRunning = $false; PortListening = $false
        CanConnect = $false; Database = 'aeris'; TablesFound = @(); ConnectError = $null
    }
    foreach ($svcName in @('MySQL80', 'MySQL84', 'MySQL', 'MariaDB')) {
        $svc = Get-Service -Name $svcName -ErrorAction SilentlyContinue
        if ($svc) {
            $result.ServiceName = $svcName
            $result.ServiceRunning = ($svc.Status -eq 'Running')
            break
        }
    }
    $result.PortListening = Test-TcpPortListening -Port 3306

    # Mesmas credenciais que model/Connection.php usa de verdade —
    # sem inventar host/usuario diferente do que o projeto espera.
    $php = Get-ToolPath 'php'
    if ($php -and $result.PortListening) {
        $probe = @'
<?php
try {
    $host = "localhost";
    $user = "root";
    $pass = getenv("AERIS_DB_PASSWORD");
    if ($pass === false) { $pass = "senaisp"; }
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_TIMEOUT => 3]);
    $pdo->exec("USE `aeris`");
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "OK|" . implode(",", $tables);
} catch (Throwable $e) {
    echo "ERR|" . $e->getMessage();
}
'@
        $tmpFile = [System.IO.Path]::GetTempFileName() + '.php'
        Set-Content -Path $tmpFile -Value $probe -Encoding ascii
        try {
            $out = (& $php $tmpFile 2>$null)
            if ($out -like 'OK|*') {
                $result.CanConnect = $true
                $tables = $out.Substring(3)
                if ($tables) { $result.TablesFound = $tables -split ',' }
            } elseif ($out -like 'ERR|*') {
                $result.ConnectError = $out.Substring(4)
            }
        } finally {
            Remove-Item $tmpFile -ErrorAction SilentlyContinue
        }
    }
    return $result
}

function Test-CaddyEnv {
    $localExe = Join-Path $script:ProjectRoot 'tools\caddy.exe'
    $caddyfile = Join-Path $script:ProjectRoot 'tools\Caddyfile'
    $result = [ordered]@{
        LocalExeFound = (Test-Path $localExe); LocalExePath = $localExe
        CaddyfileFound = (Test-Path $caddyfile); Version = $null
        ValidateOk = $false; ValidateOutput = $null; PortListening = $false
    }
    if ($result.LocalExeFound) {
        $result.Version = (& $localExe version 2>$null)
        if ($result.CaddyfileFound) {
            Push-Location (Join-Path $script:ProjectRoot 'tools')
            try {
                $out = (& $localExe validate --config Caddyfile 2>&1)
                $result.ValidateOk = ($LASTEXITCODE -eq 0)
                $result.ValidateOutput = ($out -join ' ')
            } finally { Pop-Location }
        }
    }
    $result.PortListening = Test-TcpPortListening -Port 8000
    return $result
}

function Test-PhpCgiEnv {
    $path = Get-ToolPath 'php-cgi'
    $result = [ordered]@{ Found = ([bool]$path); Path = $path; PortListening = (Test-TcpPortListening -Port 9123) }
    return $result
}

function Test-NodeToolingNeeded {
    $pkg = Join-Path $script:ProjectRoot 'package.json'
    return (Test-Path $pkg)
}

function Test-ComposerToolingNeeded {
    $pkg = Join-Path $script:ProjectRoot 'composer.json'
    return (Test-Path $pkg)
}

function Test-FlutterProjectExists {
    $pubspec = Join-Path $script:ProjectRoot 'pubspec.yaml'
    return (Test-Path $pubspec)
}

function Get-Esp32Includes {
    $ino = Get-ChildItem -Path (Join-Path $script:ProjectRoot 'esp32-firmware') -Filter '*.ino' -ErrorAction SilentlyContinue | Select-Object -First 1
    if (-not $ino) { return @() }
    $lines = Get-Content $ino.FullName | Select-String '^#include\s+[<"]([^>"]+)[>"]'
    return $lines | ForEach-Object { $_.Matches[0].Groups[1].Value }
}

function Get-Esp32LibraryPlan {
    # So bibliotecas EXTERNAS de verdade — WiFi/HTTPClient/WebServer/
    # ESPmDNS/Wire/soc-*.h ja vem com o pacote de placas ESP32.
    $includes = Get-Esp32Includes
    $plan = @()
    if ($includes -contains 'Adafruit_GFX.h') {
        $plan += @{ Library = 'Adafruit GFX Library'; Reason = 'esp32-firmware.ino inclui Adafruit_GFX.h (display OLED).' }
    }
    if ($includes -contains 'Adafruit_SSD1306.h') {
        $plan += @{ Library = 'Adafruit SSD1306'; Reason = 'esp32-firmware.ino inclui Adafruit_SSD1306.h (driver do OLED).' }
        $plan += @{ Library = 'Adafruit BusIO'; Reason = 'Dependencia da Adafruit SSD1306.' }
    }
    return $plan
}

function Test-ArduinoEnv {
    $cliPath = Get-ToolPath 'arduino-cli'
    $idePaths = @(
        "$env:LOCALAPPDATA\Programs\Arduino IDE\Arduino IDE.exe",
        "${env:ProgramFiles}\Arduino IDE\Arduino IDE.exe",
        "${env:ProgramFiles(x86)}\Arduino\arduino.exe"
    )
    $idePath = $idePaths | Where-Object { Test-Path $_ } | Select-Object -First 1
    return [ordered]@{
        CliFound = ([bool]$cliPath); CliPath = $cliPath
        IdeFound = ([bool]$idePath); IdePath = $idePath
    }
}

function Test-Winget {
    $path = Get-ToolPath 'winget'
    return [ordered]@{ Found = ([bool]$path); Path = $path }
}

function Get-PortsSummary {
    return @(
        [ordered]@{ Servico = 'Caddy (web server)'; Porta = 8000; Endereco = 'http://localhost:8000'; Funcao = 'Entrada HTTP do dashboard/API'; Ativa = (Test-TcpPortListening 8000) }
        [ordered]@{ Servico = 'PHP-CGI (FastCGI)'; Porta = 9123; Endereco = '127.0.0.1:9123'; Funcao = 'Backend PHP atras do Caddy'; Ativa = (Test-TcpPortListening 9123) }
        [ordered]@{ Servico = 'MySQL'; Porta = 3306; Endereco = 'localhost:3306'; Funcao = 'Banco de dados (aeris)'; Ativa = (Test-TcpPortListening 3306) }
    )
}
