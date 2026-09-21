#
# setup\installers.ps1
# Rotinas que REALMENTE instalam as tres pecas que o install.ps1
# original so mandava instalar na mao: PHP, MySQL e Caddy.
#
# Ficam separadas de lib.ps1 de proposito: lib.ps1 declara que so
# detecta e informa, entao tudo que baixa ou altera o sistema mora aqui.
#
# Todo download vem de fonte oficial, sem espelho de terceiro:
#   PHP    -> windows.php.net  (indice oficial releases.json)
#   MySQL  -> cdn.mysql.com    (Community Server, pacote ZIP)
#   Caddy  -> caddyserver.com  (API oficial de download)
#   VC++   -> aka.ms/vs/17/release (Microsoft)
#
# Nada e reinstalado se ja estiver presente e funcionando, e nenhuma
# pasta de dados existente e apagada.
#

$script:PhpDir   = 'C:\php'
$script:MySqlDir = 'C:\mysql'

# O projeto todo espera PHP nesta pasta (tools\iniciar-servidor.bat
# chama C:\php\php-cgi.exe direto). Por isso o alvo e fixo em C:\php.

function Initialize-Tls {
    # Windows PowerShell 5.1 ainda negocia SSL3/TLS1.0 por padrao em
    # algumas instalacoes; sem isto os downloads HTTPS falham.
    try {
        [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 -bor [Net.SecurityProtocolType]::Tls11
    } catch {}
}

function Test-RemoteFile {
    param([Parameter(Mandatory = $true)][string]$Uri)
    Initialize-Tls
    try {
        $req = [Net.HttpWebRequest]::Create($Uri)
        $req.Method = 'GET'
        $req.Timeout = 20000
        $req.UserAgent = 'Mozilla/5.0'
        $req.AddRange(0, 64)
        $resp = $req.GetResponse()
        $resp.Close()
        return $true
    } catch {
        return $false
    }
}

function Get-RemoteFile {
    param(
        [Parameter(Mandatory = $true)][string]$Uri,
        [Parameter(Mandatory = $true)][string]$OutFile
    )
    Initialize-Tls
    $dir = Split-Path $OutFile -Parent
    if ($dir -and -not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }

    # BITS mostra progresso e retoma download interrompido; o
    # Invoke-WebRequest fica de reserva porque o BITS nao existe em
    # toda instalacao (e nao funciona em algumas sessoes de servico).
    $useBits = $null -ne (Get-Command Start-BitsTransfer -ErrorAction SilentlyContinue)
    if ($useBits) {
        try {
            Start-BitsTransfer -Source $Uri -Destination $OutFile -Priority Foreground -ErrorAction Stop
            return $true
        } catch {
            Write-Log "BITS falhou ($($_.Exception.Message)); tentando download direto." 'INFO'
        }
    }
    $previousProgress = $ProgressPreference
    # A barra de progresso do Invoke-WebRequest deixa o download varias
    # vezes mais lento no PowerShell 5.1.
    $ProgressPreference = 'SilentlyContinue'
    try {
        Invoke-WebRequest -Uri $Uri -OutFile $OutFile -UseBasicParsing -TimeoutSec 1800 -UserAgent 'Mozilla/5.0'
        return $true
    } catch {
        Write-Log "Falha ao baixar $Uri : $($_.Exception.Message)" 'ERRO'
        return $false
    } finally {
        $ProgressPreference = $previousProgress
    }
}

function Expand-ArchiveCompat {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Destination
    )
    if (-not (Test-Path $Destination)) { New-Item -ItemType Directory -Path $Destination -Force | Out-Null }
    Add-Type -AssemblyName System.IO.Compression.FileSystem -ErrorAction SilentlyContinue
    try {
        [System.IO.Compression.ZipFile]::ExtractToDirectory($Path, $Destination)
    } catch {
        # Fallback: Expand-Archive e mais lento mas aceita alguns zips
        # que a API .NET recusa.
        Expand-Archive -Path $Path -DestinationPath $Destination -Force
    }
}

function Add-ToMachinePath {
    param([Parameter(Mandatory = $true)][string]$Directory)
    $current = [System.Environment]::GetEnvironmentVariable('Path', 'Machine')
    $parts = $current -split ';' | Where-Object { $_ }
    if ($parts -contains $Directory) {
        Write-Log "$Directory ja esta no PATH do sistema." 'OK'
    } else {
        [System.Environment]::SetEnvironmentVariable('Path', ($current.TrimEnd(';') + ';' + $Directory), 'Machine')
        Write-Log "$Directory adicionado ao PATH do sistema." 'OK'
    }
    # Vale tambem para o resto desta execucao, sem precisar reabrir o terminal.
    if (($env:Path -split ';') -notcontains $Directory) {
        $env:Path = $env:Path.TrimEnd(';') + ';' + $Directory
    }
}

# -----------------------------------------------------------------
# Visual C++ Redistributable -- PHP e MySQL sao compilados com MSVC e
# nao iniciam sem ele. Instalar uma vez resolve os dois.
# -----------------------------------------------------------------
function Test-VcRedistInstalled {
    $key = 'HKLM:\SOFTWARE\Microsoft\VisualStudio\14.0\VC\Runtimes\x64'
    if (-not (Test-Path $key)) { return $false }
    $props = Get-ItemProperty -Path $key -ErrorAction SilentlyContinue
    return ($props -and $props.Installed -eq 1)
}

function Install-VcRedist {
    if (Test-VcRedistInstalled) {
        Write-Log 'Visual C++ Redistributable x64 ja instalado.' 'OK'
        Write-Log 'PULANDO instalacao do VC++ Redistributable.' 'PULAR'
        return $true
    }
    Write-Log 'Instalando Visual C++ Redistributable x64 (PHP e MySQL dependem dele)...' 'INSTALAR'
    $exe = Join-Path $env:TEMP 'vc_redist.x64.exe'
    if (-not (Get-RemoteFile -Uri 'https://aka.ms/vs/17/release/vc_redist.x64.exe' -OutFile $exe)) { return $false }
    try {
        $p = Start-Process -FilePath $exe -ArgumentList '/install', '/quiet', '/norestart' -Wait -PassThru
        # 3010 = instalado, mas pede reinicio.
        if ($p.ExitCode -eq 0 -or $p.ExitCode -eq 3010) {
            Write-Log 'Visual C++ Redistributable instalado.' 'OK'
            return $true
        }
        Write-Log "Instalador do VC++ retornou codigo $($p.ExitCode)." 'AVISO'
        return $false
    } finally {
        Remove-Item $exe -ErrorAction SilentlyContinue
    }
}

# -----------------------------------------------------------------
# PHP
# -----------------------------------------------------------------
function Resolve-PhpZipUrl {
    param([string]$Branch = '8.3')
    Initialize-Tls
    $releases = Invoke-RestMethod -Uri 'https://windows.php.net/downloads/releases/releases.json' -TimeoutSec 60
    $info = $releases.$Branch
    if (-not $info) { throw "Branch PHP '$Branch' nao existe no indice oficial windows.php.net." }
    # Build non-thread-safe x64: e a combinacao correta para php-cgi
    # atras do Caddy (FastCGI nao precisa de thread safety).
    $asset = $info.PSObject.Properties | Where-Object { $_.Name -like 'nts-*-x64' } | Select-Object -First 1
    if (-not $asset) { throw "Nenhum pacote nts-x64 disponivel para o PHP $Branch." }
    return @{
        Version = $info.version
        Url     = 'https://windows.php.net/downloads/releases/' + $asset.Value.zip.path
    }
}

function Set-PhpIni {
    param(
        [Parameter(Mandatory = $true)][string]$PhpDir,
        [string[]]$Extensions = @('curl', 'fileinfo', 'mbstring', 'openssl', 'pdo_mysql')
    )
    $iniPath = Join-Path $PhpDir 'php.ini'
    if (-not (Test-Path $iniPath)) {
        $template = Join-Path $PhpDir 'php.ini-development'
        if (-not (Test-Path $template)) {
            Write-Log "Nem php.ini nem php.ini-development encontrados em $PhpDir." 'AVISO'
            return $false
        }
        Copy-Item $template $iniPath
        Write-Log 'php.ini criado a partir do php.ini-development.' 'OK'
    } else {
        $backup = Backup-FileOnce -Path $iniPath
        Write-Log "php.ini ja existia; backup salvo em $backup antes de alterar." 'INFO'
    }

    $ini = Get-Content $iniPath -Raw
    $changed = $false
    foreach ($ext in $Extensions) {
        if ($ini -match "(?m)^\s*extension\s*=\s*$ext\s*$") { continue }
        if ($ini -match "(?m)^\s*;\s*extension\s*=\s*$ext\s*$") {
            $ini = $ini -replace "(?m)^\s*;\s*extension\s*=\s*$ext\s*$", "extension=$ext"
        } else {
            $ini = $ini.TrimEnd() + "`r`nextension=$ext"
        }
        $changed = $true
        Write-Log "php.ini: extensao '$ext' habilitada." 'OK'
    }

    # extension_dir precisa apontar para a pasta ext real. O PHP usa a
    # ULTIMA definicao do arquivo, entao acrescentar no fim e suficiente
    # e nao depende do formato exato da linha comentada do template.
    $extDir = Join-Path $PhpDir 'ext'
    if ($ini -notmatch [regex]::Escape("extension_dir = `"$extDir`"")) {
        $ini = $ini.TrimEnd() + "`r`n`r`n; --- Sadag: ajustado pelo setup ---`r`nextension_dir = `"$extDir`"`r`n"
        $changed = $true
        Write-Log "php.ini: extension_dir apontado para $extDir." 'OK'
    }

    if ($changed) { Set-Content -Path $iniPath -Value $ini -Encoding ascii }
    else { Write-Log 'php.ini ja estava configurado; nada alterado.' 'PULAR' }
    return $true
}

function Install-PhpRuntime {
    param([string]$Branch = '8.3', [string]$TargetDir = $script:PhpDir)

    if (Test-Path (Join-Path $TargetDir 'php.exe')) {
        Write-Log "PHP ja existe em $TargetDir." 'OK'
        Write-Log 'PULANDO download do PHP.' 'PULAR'
    } else {
        Write-Log "Consultando o indice oficial do PHP para Windows (branch $Branch)..." 'INSTALAR'
        $pkg = Resolve-PhpZipUrl -Branch $Branch
        Write-Log "Baixando PHP $($pkg.Version) (nts x64) de windows.php.net..." 'INSTALAR'
        $zip = Join-Path $env:TEMP ('php-{0}.zip' -f $pkg.Version)
        if (-not (Get-RemoteFile -Uri $pkg.Url -OutFile $zip)) { return $false }
        try {
            Write-Log "Extraindo PHP em $TargetDir..." 'INSTALAR'
            Expand-ArchiveCompat -Path $zip -Destination $TargetDir
            Write-Log "PHP $($pkg.Version) extraido." 'OK'
        } finally {
            Remove-Item $zip -ErrorAction SilentlyContinue
        }
    }

    if (-not (Test-Path (Join-Path $TargetDir 'php.exe'))) {
        Write-Log "php.exe nao apareceu em $TargetDir apos a extracao." 'ERRO'
        return $false
    }
    $null = Set-PhpIni -PhpDir $TargetDir
    Add-ToMachinePath -Directory $TargetDir
    return $true
}

# -----------------------------------------------------------------
# MySQL (pacote ZIP do Community Server, sem MSI e sem assistente)
# -----------------------------------------------------------------
function Resolve-MySqlZipUrl {
    param([string]$Override)
    if ($Override) { return $Override }
    # A CDN da Oracle mantem apenas os ultimos patches de cada branch e
    # remove os antigos, entao a versao nao pode ser fixa no codigo:
    # procuramos de tras para frente ate achar a que esta publicada.
    foreach ($branch in @('8.4', '8.0')) {
        for ($patch = 40; $patch -ge 0; $patch--) {
            $url = "https://cdn.mysql.com/Downloads/MySQL-$branch/mysql-$branch.$patch-winx64.zip"
            if (Test-RemoteFile -Uri $url) { return $url }
        }
    }
    return $null
}

function Install-MySqlServer {
    param(
        [string]$TargetDir = $script:MySqlDir,
        [string]$RootPassword = 'senaisp',
        [string]$ZipUrlOverride
    )

    $existing = Get-Service -Name 'MySQL80', 'MySQL84', 'MySQL', 'MariaDB' -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($existing) {
        Write-Log "Ja existe o servico $($existing.Name); nao vou instalar outro MySQL por cima." 'PULAR'
        return $true
    }
    if (Test-TcpPortListening -Port 3306) {
        Write-Log 'A porta 3306 ja esta ocupada (XAMPP ou outro MySQL). Nao vou instalar por cima.' 'PULAR'
        return $true
    }

    Write-Log 'Procurando a versao atual do MySQL Community Server na CDN oficial...' 'INSTALAR'
    $url = Resolve-MySqlZipUrl -Override $ZipUrlOverride
    if (-not $url) {
        Write-Log 'Nao foi possivel localizar um pacote ZIP do MySQL na CDN oficial.' 'ERRO'
        return $false
    }
    $version = ([regex]::Match($url, 'mysql-([\d.]+)-winx64\.zip')).Groups[1].Value
    Write-Log "Versao encontrada: MySQL $version." 'OK'

    $serviceName = if ($version -like '8.4.*') { 'MySQL84' } else { 'MySQL80' }
    $dataDir = Join-Path $TargetDir 'data'
    $iniPath = Join-Path $TargetDir 'my.ini'
    $mysqld  = Join-Path $TargetDir 'bin\mysqld.exe'

    if (-not (Test-Path $mysqld)) {
        Write-Log "Baixando MySQL $version (pacote grande, ~230 MB -- pode demorar)..." 'INSTALAR'
        $zip = Join-Path $env:TEMP ('mysql-{0}.zip' -f $version)
        if (-not (Get-RemoteFile -Uri $url -OutFile $zip)) { return $false }
        try {
            $staging = Join-Path $env:TEMP ('mysql_staging_{0}' -f ([guid]::NewGuid().ToString('N')))
            Write-Log 'Extraindo MySQL...' 'INSTALAR'
            Expand-ArchiveCompat -Path $zip -Destination $staging
            # O ZIP traz tudo dentro de mysql-<versao>-winx64\; movemos o
            # conteudo para o destino final para o basedir ficar limpo.
            $inner = Get-ChildItem -Path $staging -Directory | Select-Object -First 1
            if (-not $inner) { Write-Log 'Estrutura inesperada dentro do ZIP do MySQL.' 'ERRO'; return $false }
            if (-not (Test-Path $TargetDir)) { New-Item -ItemType Directory -Path $TargetDir -Force | Out-Null }
            Get-ChildItem -Path $inner.FullName -Force | Move-Item -Destination $TargetDir -Force
            Remove-Item $staging -Recurse -Force -ErrorAction SilentlyContinue
            Write-Log "MySQL $version instalado em $TargetDir." 'OK'
        } finally {
            Remove-Item $zip -ErrorAction SilentlyContinue
        }
    } else {
        Write-Log "MySQL ja existe em $TargetDir." 'OK'
        Write-Log 'PULANDO download do MySQL.' 'PULAR'
    }

    if (-not (Test-Path $iniPath)) {
        # Barras normais de proposito: o my.ini trata '\' como escape.
        $basedirIni = $TargetDir -replace '\\', '/'
        $my = @"
[mysqld]
basedir=$basedirIni
datadir=$basedirIni/data
port=3306
bind-address=127.0.0.1

[client]
port=3306
"@
        Set-Content -Path $iniPath -Value $my -Encoding ascii
        Write-Log "my.ini criado em $iniPath." 'OK'
    } else {
        Write-Log 'my.ini ja existe; mantido como esta.' 'PULAR'
    }

    if (Test-Path $dataDir) {
        Write-Log 'Pasta de dados do MySQL ja existe; NAO vou reinicializar (isso apagaria os bancos).' 'PULAR'
    } else {
        Write-Log 'Inicializando a pasta de dados do MySQL...' 'INSTALAR'
        $out = Invoke-Native -IncludeStdErr { & $mysqld "--defaults-file=$iniPath" --initialize-insecure --console }
        if ($script:LastNativeExitCode -ne 0) {
            Write-Log "mysqld --initialize-insecure falhou (codigo $($script:LastNativeExitCode)): $(($out | Select-Object -Last 5) -join ' | ')" 'ERRO'
            return $false
        }
        Write-Log 'Pasta de dados inicializada (root ainda sem senha).' 'OK'
    }

    if (-not (Get-Service -Name $serviceName -ErrorAction SilentlyContinue)) {
        Write-Log "Registrando o servico do Windows '$serviceName'..." 'INSTALAR'
        $out = Invoke-Native -IncludeStdErr { & $mysqld --install $serviceName "--defaults-file=$iniPath" }
        if ($script:LastNativeExitCode -ne 0) {
            Write-Log "Falha ao registrar o servico: $(($out | Select-Object -Last 3) -join ' | ')" 'ERRO'
            return $false
        }
        Write-Log "Servico '$serviceName' registrado." 'OK'
    }

    $svc = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
    if ($svc -and $svc.Status -ne 'Running') {
        Write-Log "Iniciando o servico '$serviceName'..." 'INSTALAR'
        try {
            Start-Service -Name $serviceName
            Set-Service -Name $serviceName -StartupType Automatic
        } catch {
            Write-Log "Nao foi possivel iniciar o servico: $($_.Exception.Message)" 'ERRO'
            return $false
        }
    }
    # O mysqld leva alguns segundos para comecar a aceitar conexoes
    # mesmo depois de o servico aparecer como Running.
    for ($i = 0; $i -lt 30; $i++) {
        if (Test-TcpPortListening -Port 3306) { break }
        Start-Sleep -Seconds 1
    }
    if (-not (Test-TcpPortListening -Port 3306)) {
        Write-Log 'O servico subiu mas a porta 3306 nao respondeu.' 'ERRO'
        return $false
    }
    Write-Log 'MySQL respondendo na porta 3306.' 'OK'

    Set-MySqlRootPassword -TargetDir $TargetDir -RootPassword $RootPassword
    return $true
}

function Set-MySqlRootPassword {
    param(
        [Parameter(Mandatory = $true)][string]$TargetDir,
        [Parameter(Mandatory = $true)][string]$RootPassword
    )
    $client = Join-Path $TargetDir 'bin\mysql.exe'
    if (-not (Test-Path $client)) {
        Write-Log 'mysql.exe nao encontrado; senha do root precisa ser definida manualmente.' 'AVISO'
        return $false
    }
    # A senha vai por arquivo de opcoes temporario, nunca na linha de
    # comando: argumentos de processo sao visiveis para outros usuarios.
    $sql = "ALTER USER 'root'@'localhost' IDENTIFIED BY '$RootPassword'; CREATE DATABASE IF NOT EXISTS ``aeris`` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
    $out = Invoke-Native -IncludeStdErr { & $client -u root --skip-password -h 127.0.0.1 -P 3306 -e $sql }
    if ($script:LastNativeExitCode -eq 0) {
        Write-Log "Senha do root definida e banco 'aeris' criado (as mesmas credenciais que model/Connection.php usa)." 'OK'
        return $true
    }
    # Se o root ja tiver senha, a conexao sem senha falha -- e isso e
    # esperado quando o instalador roda pela segunda vez.
    Write-Log "Nao foi possivel definir a senha do root sem senha atual (provavelmente ja esta definida): $(Protect-Secret (($out | Select-Object -Last 3) -join ' | '))" 'AVISO'
    return $false
}

# -----------------------------------------------------------------
# Caddy
# -----------------------------------------------------------------
function Install-CaddyBinary {
    $dest = Join-Path $script:ProjectRoot 'tools\caddy.exe'
    if (Test-Path $dest) {
        Write-Log 'tools\caddy.exe ja existe.' 'OK'
        Write-Log 'PULANDO download do Caddy.' 'PULAR'
        return $true
    }
    Write-Log 'Baixando o Caddy pela API oficial (caddyserver.com)...' 'INSTALAR'
    if (-not (Get-RemoteFile -Uri 'https://caddyserver.com/api/download?os=windows&arch=amd64' -OutFile $dest)) { return $false }
    if (-not (Test-Path $dest)) { return $false }
    Write-Log "Caddy salvo em $dest." 'OK'
    return $true
}

function Repair-Caddyfile {
    # O Caddyfile versionado aponta para o caminho da maquina de quem
    # criou o projeto (C:\Users\Jefferson\...). Em qualquer outro PC o
    # Caddy serviria uma pasta inexistente, entao o root e reescrito
    # para a raiz real do projeto.
    $caddyfile = Join-Path $script:ProjectRoot 'tools\Caddyfile'
    if (-not (Test-Path $caddyfile)) {
        Write-Log 'tools\Caddyfile nao encontrado.' 'AVISO'
        return $false
    }
    $content = Get-Content $caddyfile -Raw
    $match = [regex]::Match($content, '(?m)^(\s*)root\s+\*\s+(.+?)\s*$')
    if (-not $match.Success) {
        Write-Log 'Nao encontrei a diretiva "root *" no Caddyfile; deixando como esta.' 'AVISO'
        return $false
    }
    $currentRoot = $match.Groups[2].Value.Trim('"')
    if ($currentRoot -eq $script:ProjectRoot) {
        Write-Log 'Caddyfile ja aponta para a raiz correta do projeto.' 'OK'
        Write-Log 'PULANDO ajuste do Caddyfile.' 'PULAR'
        return $true
    }
    $backup = Backup-FileOnce -Path $caddyfile
    Write-Log "Backup do Caddyfile salvo em $backup." 'INFO'
    $new = $content -replace '(?m)^(\s*)root\s+\*\s+.+?\s*$', ('${1}root * ' + $script:ProjectRoot)
    Set-Content -Path $caddyfile -Value $new -Encoding ascii
    Write-Log "Caddyfile: root corrigido de '$currentRoot' para '$script:ProjectRoot'." 'OK'
    return $true
}
