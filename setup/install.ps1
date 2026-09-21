#
# setup\install.ps1
# Orquestrador principal do setup do Sadag (ex-Aeris Guard).
# Chamado por setup\install.bat, ja em modo administrador.
#
# Filosofia (ver AUDITORIA.md): analisar -> detectar -> comparar ->
# instalar somente o necessario -> configurar -> validar -> relatorio.
# Nada e reinstalado ou sobrescrito se ja estiver correto.
#

param(
    [switch]$PrepararFlutter,
    [switch]$PrepararArduino
)

$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'lib.ps1')
Initialize-SetupDirs

$script:CriticalFailures = @()
$script:Warnings = @()

function Step-Header {
    param([int]$N, [int]$Total, [string]$Title)
    Write-Host ''
    Write-Host "[$N/$Total] $Title" -ForegroundColor White -BackgroundColor DarkBlue
    Write-Log "== Etapa $N/$Total : $Title ==" 'INFO'
}

Write-Host ''
Write-Host '========================================' -ForegroundColor Green
Write-Host ' SADAG - SETUP DO AMBIENTE DE DESENVOLVIMENTO' -ForegroundColor Green
Write-Host '========================================' -ForegroundColor Green
Write-Log "Projeto: $script:ProjectRoot" 'INFO'
Write-Log "Log: $script:LogFile" 'INFO'

if (-not (Test-IsAdmin)) {
    Write-Log 'Este script nao esta rodando como administrador. Algumas etapas (php.ini, servico MySQL, firewall) podem falhar.' 'AVISO'
    $script:Warnings += 'Sem privilegios de administrador durante a execucao do install.ps1.'
}

$TotalSteps = 9

# -----------------------------------------------------------------
Step-Header 1 $TotalSteps 'Windows e privilegios'
# -----------------------------------------------------------------
$os = Get-CimInstance Win32_OperatingSystem
Write-Log "Sistema: $($os.Caption) ($($os.OSArchitecture))" 'INFO'
if ($os.Caption -notmatch 'Windows 10|Windows 11') {
    Write-Log 'Este instalador foi desenhado para Windows 10/11. Continuando mesmo assim.' 'AVISO'
    $script:Warnings += 'Versao de Windows nao testada.'
} else {
    Write-Log 'Windows 10/11 confirmado.' 'OK'
}
Write-Log "Administrador: $(Test-IsAdmin)" 'INFO'
$winget = Test-Winget
if ($winget.Found) { Write-Log "winget disponivel em $($winget.Path)." 'OK' }
else { Write-Log 'winget nao encontrado. Etapas que dependem dele vao pedir instalacao manual pela fonte oficial.' 'AVISO' }

# -----------------------------------------------------------------
Step-Header 2 $TotalSteps 'Git'
# -----------------------------------------------------------------
$git = Test-GitEnv
if (-not $git.Found) {
    Write-Log 'Git nao encontrado no PATH.' 'INSTALAR'
    if ($winget.Found) {
        Write-Log 'Instalando Git via winget (Git.Git)...' 'INSTALAR'
        try {
            & winget install --id Git.Git -e --source winget --accept-package-agreements --accept-source-agreements | Out-Null
            $env:Path = [System.Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path', 'User')
            $git = Test-GitEnv
            if ($git.Found) { Write-Log "Git instalado: $($git.Version)" 'OK' }
            else { $script:CriticalFailures += 'Git nao ficou disponivel no PATH apos a instalacao. Reabra o terminal e rode novamente.' }
        } catch {
            $script:CriticalFailures += "Falha ao instalar Git via winget: $($_.Exception.Message)"
        }
    } else {
        $script:CriticalFailures += 'Git ausente e winget indisponivel. Instale manualmente em https://git-scm.com/download/win e rode o instalador de novo.'
    }
} else {
    Write-Log "Git ja instalado: $($git.Version) ($($git.Path))." 'OK'
    Write-Log 'PULANDO instalacao do Git.' 'PULAR'
}
if ($git.Found) {
    if ($git.UserName -and $git.UserEmail) {
        Write-Log "git config global ja definido: $($git.UserName) <$($git.UserEmail)>." 'OK'
    } else {
        Write-Log 'git config --global user.name/user.email nao estao definidos.' 'AVISO'
        Write-Host 'Digite seu nome para o git config --global user.name (ou ENTER para pular):' -ForegroundColor Yellow
        $n = Read-Host '  Nome'
        if ($n) { & git config --global user.name $n; Write-Log "user.name definido para '$n'." 'OK' }
        Write-Host 'Digite seu email para o git config --global user.email (ou ENTER para pular):' -ForegroundColor Yellow
        $e = Read-Host '  Email'
        if ($e) { & git config --global user.email $e; Write-Log "user.email definido para '$e'." 'OK' }
    }
    if ($git.Remote) { Write-Log "Remote do projeto: $($git.Remote) (nao alterado)." 'OK' }
}
$gh = Test-GhCli
if ($gh.Found) {
    Write-Log "GitHub CLI encontrado: $($gh.Version)." 'OK'
    if ($gh.AuthOk) { Write-Log 'gh auth status: autenticado.' 'OK' }
    else {
        Write-Log 'gh encontrado mas nao autenticado. Rode manualmente: gh auth login' 'AVISO'
        $script:Warnings += 'GitHub CLI instalado porem sem login (gh auth login pendente).'
    }
} else {
    Write-Log 'GitHub CLI (gh) nao encontrado. O projeto usa remote do GitHub via HTTPS pelo proprio Git, entao gh e opcional (usado so para PRs/issues pelo terminal).' 'INFO'
}

# -----------------------------------------------------------------
Step-Header 3 $TotalSteps 'Visual Studio Code + extensoes'
# -----------------------------------------------------------------
$vscode = Test-VSCodeEnv
if (-not $vscode.Found) {
    Write-Log 'VS Code nao encontrado no PATH.' 'INSTALAR'
    if ($winget.Found) {
        try {
            & winget install --id Microsoft.VisualStudioCode -e --source winget --accept-package-agreements --accept-source-agreements | Out-Null
            $env:Path = [System.Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path', 'User')
            $vscode = Test-VSCodeEnv
            if ($vscode.Found) { Write-Log "VS Code instalado: $($vscode.Version)" 'OK' }
            else { $script:Warnings += "VS Code instalado mas comando 'code' nao apareceu no PATH desta sessao. Abra um terminal novo." }
        } catch {
            $script:CriticalFailures += "Falha ao instalar VS Code via winget: $($_.Exception.Message)"
        }
    } else {
        $script:CriticalFailures += 'VS Code ausente e winget indisponivel. Instale manualmente em https://code.visualstudio.com/'
    }
} else {
    Write-Log "VS Code ja instalado: $($vscode.Version) ($($vscode.Path))." 'OK'
    Write-Log 'PULANDO instalacao do VS Code.' 'PULAR'
}
if ($vscode.Found) {
    foreach ($ext in $script:RequiredVsCodeExtensions) {
        if ($vscode.Extensions -contains $ext.Id) {
            Write-Log "Extensao '$($ext.Name)' ja instalada. $($ext.Reason)" 'OK'
        } else {
            Write-Log "Instalando extensao necessaria '$($ext.Name)'. Motivo: $($ext.Reason)" 'INSTALAR'
            try {
                & code --install-extension $ext.Id --force | Out-Null
                Write-Log "Extensao '$($ext.Name)' instalada." 'OK'
            } catch {
                $script:Warnings += "Nao foi possivel instalar a extensao $($ext.Name) automaticamente."
            }
        }
    }
    Write-Log 'Extensoes OPCIONAIS (nao instaladas automaticamente, so sugestao):' 'INFO'
    foreach ($ext in $script:OptionalVsCodeExtensions) {
        Write-Log "  - $($ext.Name) [$($ext.Id)] -- $($ext.Reason)" 'INFO'
    }
}

# -----------------------------------------------------------------
Step-Header 4 $TotalSteps 'PHP e extensoes'
# -----------------------------------------------------------------
$php = Test-PhpEnv
if (-not $php.Found) {
    $script:CriticalFailures += 'PHP nao encontrado no PATH. Instale PHP 8.x (thread-safe ou nao, x64) e adicione ao PATH manualmente -- este projeto ja espera C:\php\php.exe. Fonte oficial: https://windows.php.net/download/'
} else {
    Write-Log "PHP encontrado: $($php.Version -join ' ') em $($php.Path)." 'OK'
    Write-Log "php.ini: $($php.IniPath)" 'INFO'
    Write-Log 'PULANDO instalacao do PHP (ja presente).' 'PULAR'

    if ($php.Missing.Count -eq 0) {
        Write-Log 'Todas as extensoes PHP obrigatorias (pdo_mysql, curl, openssl, fileinfo, json, session) ja estao ativas.' 'OK'
    } else {
        foreach ($ext in $php.Missing) {
            Write-Log "Extensao PHP obrigatoria ausente: $ext" 'INSTALAR'
            if ($ext -eq 'fileinfo' -and $php.IniPath -and (Test-Path $php.IniPath)) {
                $iniContent = Get-Content $php.IniPath -Raw
                if ($iniContent -match ';\s*extension\s*=\s*fileinfo') {
                    if (-not (Test-IsAdmin)) {
                        $script:CriticalFailures += "Extensao fileinfo esta desativada em $($php.IniPath) e e usada pelo upload de avatar (view/html/profile.php). Precisa rodar como administrador para habilitar."
                        continue
                    }
                    $backup = Backup-FileOnce -Path $php.IniPath
                    Write-Log "Backup do php.ini salvo em: $backup" 'INFO'
                    $newContent = $iniContent -replace ';\s*extension\s*=\s*fileinfo', 'extension=fileinfo'
                    Set-Content -Path $php.IniPath -Value $newContent -Encoding ascii
                    Write-Log 'extension=fileinfo habilitada em php.ini (view/html/profile.php usa new finfo() no upload de avatar).' 'OK'
                    Write-Log 'Reinicie o php-cgi (pare e rode setup\..\tools\iniciar-servidor.bat de novo) para a mudanca valer.' 'AVISO'
                    $script:Warnings += 'php-cgi precisa ser reiniciado para a extensao fileinfo recem-habilitada valer.'
                } else {
                    $script:Warnings += "Extensao fileinfo nao encontrada em $($php.IniPath) (nem comentada). Adicione manualmente 'extension=fileinfo'."
                }
            } else {
                $script:Warnings += "Extensao PHP '$ext' ausente e precisa ser habilitada manualmente em $($php.IniPath)."
            }
        }
    }
    if ($php.OptionalMissing.Count -gt 0) {
        Write-Log "Extensoes opcionais ausentes (o codigo ja tem fallback, nao e obrigatorio): $($php.OptionalMissing -join ', ')" 'INFO'
    }

    $phpCgi = Test-PhpCgiEnv
    if ($phpCgi.Found) { Write-Log "php-cgi.exe encontrado em $($phpCgi.Path)." 'OK' }
    else { $script:Warnings += 'php-cgi.exe nao encontrado no PATH (esperado ao lado do php.exe).' }
}

# -----------------------------------------------------------------
Step-Header 5 $TotalSteps 'Banco de dados (MySQL)'
# -----------------------------------------------------------------
$mysql = Test-MySqlEnv
if ($mysql.ServiceName) {
    Write-Log "Servico do MySQL encontrado: $($mysql.ServiceName)." 'OK'
    if ($mysql.ServiceRunning) {
        Write-Log 'Servico ja esta em execucao.' 'OK'
        Write-Log 'PULANDO start do servico.' 'PULAR'
    } else {
        Write-Log 'Servico existe mas esta parado. Tentando iniciar...' 'INSTALAR'
        if (Test-IsAdmin) {
            try {
                Start-Service -Name $mysql.ServiceName
                Start-Sleep -Seconds 2
                $mysql = Test-MySqlEnv
                if ($mysql.ServiceRunning) { Write-Log 'Servico do MySQL iniciado.' 'OK' }
                else { $script:CriticalFailures += "Servico $($mysql.ServiceName) nao iniciou. Veja o Visualizador de Eventos do Windows." }
            } catch {
                $script:CriticalFailures += "Falha ao iniciar o servico $($mysql.ServiceName): $($_.Exception.Message)"
            }
        } else {
            $script:CriticalFailures += "Servico $($mysql.ServiceName) parado e o script nao esta como administrador para inicia-lo."
        }
    }
} else {
    Write-Log 'Nenhum servico Windows de MySQL/MariaDB (MySQL80/MySQL84/MySQL/MariaDB) foi encontrado.' 'AVISO'
    if ($mysql.PortListening) {
        Write-Log 'Porem algo ja esta escutando na porta 3306 -- pode ser um MySQL rodando fora de servico Windows (ex.: XAMPP). Nao mexendo nisso.' 'INFO'
    } else {
        $script:CriticalFailures += 'MySQL/MariaDB nao parece estar instalado nem rodando (sem servico, sem porta 3306 ativa). Instale o MySQL Server 8.x pela fonte oficial (https://dev.mysql.com/downloads/mysql/) e rode este instalador de novo. O instalador NAO baixa o MySQL sozinho para evitar reconfigurar um servidor ja existente por engano.'
    }
}

if ($mysql.PortListening) {
    Write-Log 'Testando conexao real com o banco (mesmas credenciais de model/Connection.php: usuario root, banco aeris)...' 'INFO'
    $mysql = Test-MySqlEnv
    if ($mysql.CanConnect) {
        Write-Log "Conexao OK. Banco 'aeris' acessivel." 'OK'
        $expectedTables = @('login', 'devices', 'locations', 'sensor_readings', 'support_tickets', 'support_messages', 'notifications')
        $missingTables = $expectedTables | Where-Object { $mysql.TablesFound -notcontains $_ }
        if ($missingTables.Count -eq 0) {
            Write-Log "Tabelas principais ja existem ($($mysql.TablesFound.Count) tabelas encontradas)." 'OK'
            Write-Log 'PULANDO importacao de schema (banco ja configurado). Nada foi apagado nem sobrescrito.' 'PULAR'
        } else {
            Write-Log "Tabelas ausentes: $($missingTables -join ', ')." 'AVISO'
            $schemaFile = Join-Path $script:ProjectRoot 'api\iot.schema.sql'
            if (Test-Path $schemaFile) {
                Write-Host "Deseja importar api\iot.schema.sql agora? Ele so usa CREATE TABLE IF NOT EXISTS, entao NAO apaga nada que ja existe. (S/N)" -ForegroundColor Yellow
                $resp = Read-Host '  Resposta'
                if ($resp -match '^[sS]') {
                    try {
                        $pass = $env:AERIS_DB_PASSWORD
                        if (-not $pass) { $pass = 'senaisp' }
                        $sql = Get-Content $schemaFile -Raw
                        $probe = @"
<?php
`$pdo = new PDO('mysql:host=localhost;dbname=aeris;charset=utf8mb4', 'root', '$pass');
`$pdo->exec(file_get_contents('$($schemaFile -replace '\\','\\\\')'));
echo 'OK';
"@
                        $tmp = [System.IO.Path]::GetTempFileName() + '.php'
                        Set-Content -Path $tmp -Value $probe -Encoding ascii
                        $out = (& (Get-ToolPath 'php') $tmp 2>&1)
                        Remove-Item $tmp -ErrorAction SilentlyContinue
                        if ($out -match 'OK') { Write-Log 'Schema importado (somente tabelas que faltavam).' 'OK' }
                        else { $script:Warnings += "Falha ao importar schema: $out" }
                    } catch {
                        $script:Warnings += "Falha ao importar schema: $($_.Exception.Message)"
                    }
                } else {
                    Write-Log 'Importacao de schema pulada a pedido do usuario.' 'INFO'
                }
            } else {
                $script:Warnings += 'api/iot.schema.sql nao encontrado; tabelas ausentes precisam ser criadas manualmente ou pela propria aplicacao (varias DAOs criam tabela com CREATE TABLE IF NOT EXISTS na primeira execucao).'
            }
        }
    } else {
        $script:CriticalFailures += "Nao foi possivel conectar no MySQL com usuario root (senha via variavel de ambiente AERIS_DB_PASSWORD, senao 'senaisp' como no Connection.php). Erro: $(Protect-Secret $mysql.ConnectError)"
    }
}

# -----------------------------------------------------------------
Step-Header 6 $TotalSteps 'Caddy + PHP FastCGI (servidor local do projeto)'
# -----------------------------------------------------------------
$caddy = Test-CaddyEnv
if ($caddy.LocalExeFound) {
    Write-Log "Caddy do projeto encontrado: $($caddy.LocalExePath) ($($caddy.Version))." 'OK'
    Write-Log 'PULANDO download do Caddy -- o projeto ja usa um binario proprio em tools\caddy.exe (nao e um Caddy instalado globalmente).' 'PULAR'
    if ($caddy.CaddyfileFound) {
        if ($caddy.ValidateOk) { Write-Log 'tools\Caddyfile validado com sucesso (caddy validate).' 'OK' }
        else { $script:Warnings += "caddy validate reportou problema: $($caddy.ValidateOutput)" }
    } else {
        $script:CriticalFailures += 'tools\Caddyfile nao encontrado. Sem ele o Caddy nao sabe como servir o projeto.'
    }
} else {
    Write-Log 'tools\caddy.exe nao encontrado (ele fica fora do Git, veja .gitignore).' 'INSTALAR'
    Write-Host 'Baixe o Caddy oficial (https://caddyserver.com/download) e salve o executavel como tools\caddy.exe. Deseja que eu tente abrir a pagina de download agora? (S/N)' -ForegroundColor Yellow
    $resp = Read-Host '  Resposta'
    if ($resp -match '^[sS]') { Start-Process 'https://caddyserver.com/download' }
    $script:CriticalFailures += 'tools\caddy.exe ausente. Baixe manualmente da fonte oficial e coloque em tools\caddy.exe, depois rode o instalador de novo.'
}

if ($caddy.LocalExeFound -and $caddy.CaddyfileFound -and $caddy.ValidateOk) {
    if ($caddy.PortListening) {
        Write-Log 'Algo ja esta escutando na porta 8000 -- assumindo que o Caddy do projeto ja esta rodando.' 'OK'
        Write-Log 'PULANDO start do servidor.' 'PULAR'
    } else {
        Write-Log 'Porta 8000 livre. Iniciando Caddy + php-cgi (tools\iniciar-servidor.bat)...' 'INSTALAR'
        $startScript = Join-Path $script:ProjectRoot 'tools\iniciar-servidor.bat'
        if (Test-Path $startScript) {
            Start-Process -FilePath $startScript -WorkingDirectory (Join-Path $script:ProjectRoot 'tools')
            Start-Sleep -Seconds 3
            $caddy = Test-CaddyEnv
            if ($caddy.PortListening) { Write-Log 'Servidor local no ar (http://localhost:8000).' 'OK' }
            else { $script:Warnings += 'tools\iniciar-servidor.bat foi executado mas a porta 8000 ainda nao respondeu. Confira manualmente.' }
        } else {
            $script:Warnings += 'tools\iniciar-servidor.bat nao encontrado; inicie o servidor manualmente.'
        }
    }
}

# -----------------------------------------------------------------
Step-Header 7 $TotalSteps 'Node.js / Composer (confirmando que NAO sao necessarios)'
# -----------------------------------------------------------------
if (Test-NodeToolingNeeded) {
    $script:Warnings += 'Foi encontrado package.json no projeto, mas este instalador nao tem uma rotina para Node configurada -- avise para essa etapa ser adicionada.'
} else {
    Write-Log 'Nenhum package.json/package-lock.json/yarn.lock/pnpm-lock.yaml encontrado no projeto.' 'OK'
    Write-Log 'PULANDO instalacao de Node.js/npm -- o frontend e HTML/CSS/JS puro, sem build step.' 'PULAR'
}
if (Test-ComposerToolingNeeded) {
    $script:Warnings += 'Foi encontrado composer.json no projeto, mas este instalador nao tem uma rotina para Composer configurada -- avise para essa etapa ser adicionada.'
} else {
    Write-Log 'Nenhum composer.json encontrado no projeto.' 'OK'
    Write-Log 'PULANDO instalacao do Composer -- o projeto usa um cliente SMTP proprio (model/Mailer.php) e clientes HTTP via cURL nativo, sem nenhuma dependencia via Composer.' 'PULAR'
}

# -----------------------------------------------------------------
Step-Header 8 $TotalSteps 'Flutter (ambiente futuro, opcional) e Arduino/ESP32'
# -----------------------------------------------------------------
if (Test-FlutterProjectExists) {
    $script:Warnings += 'Foi encontrado pubspec.yaml no projeto, mas este instalador nao tem uma rotina completa de Flutter configurada -- avise para essa etapa ser expandida.'
} else {
    Write-Log 'Nenhum pubspec.yaml encontrado -- ainda nao existe um app Flutter neste projeto.' 'INFO'
    if ($PrepararFlutter) {
        Write-Log 'Flag -PrepararFlutter usada: preparando terreno para o app futuro.' 'INSTALAR'
        Write-Log 'Flutter/Dart, Android SDK e Android Studio sao downloads grandes (varios GB) -- este instalador NAO baixa isso sozinho.' 'AVISO'
        Write-Log 'Passo a passo oficial:' 'INFO'
        Write-Log '  1) https://docs.flutter.dev/get-started/install/windows -- baixar e extrair o Flutter SDK.' 'INFO'
        Write-Log '  2) Adicionar <pasta-do-flutter>\bin ao PATH do usuario.' 'INFO'
        Write-Log '  3) Instalar Android Studio: https://developer.android.com/studio' 'INFO'
        Write-Log '  4) Rodar: flutter doctor -v   e resolver o que ele apontar.' 'INFO'
        Write-Log '  5) Instalar a extensao Flutter no VS Code (Dart-Code.flutter) quando o app comecar a existir.' 'INFO'
        $script:Warnings += 'Ambiente Flutter ainda depende de instalacao manual guiada (Flutter SDK + Android Studio). Nada foi instalado automaticamente.'
    } else {
        Write-Log 'PULANDO preparo do ambiente Flutter (nenhum app Flutter existe ainda e a flag -PrepararFlutter nao foi usada).' 'PULAR'
        Write-Log 'Para preparar o terreno mesmo sem o app existir ainda, rode: install.bat -PrepararFlutter' 'INFO'
    }
}

$arduino = Test-ArduinoEnv
$esp32Libs = Get-Esp32LibraryPlan
if ($arduino.IdeFound -or $arduino.CliFound) {
    if ($arduino.IdeFound) { Write-Log "Arduino IDE encontrada em $($arduino.IdePath)." 'OK' }
    if ($arduino.CliFound) { Write-Log "arduino-cli encontrado em $($arduino.CliPath)." 'OK' }
    Write-Log 'PULANDO instalacao do Arduino -- ja presente.' 'PULAR'
} else {
    Write-Log 'Arduino IDE / arduino-cli nao encontrados.' 'INFO'
    if ($PrepararArduino) {
        Write-Log 'Flag -PrepararArduino usada.' 'INSTALAR'
        if ($winget.Found) {
            try {
                & winget install --id ArduinoSA.IDE.stable -e --source winget --accept-package-agreements --accept-source-agreements | Out-Null
                Write-Log 'Arduino IDE instalada via winget.' 'OK'
            } catch {
                $script:Warnings += "Falha ao instalar Arduino IDE via winget: $($_.Exception.Message). Baixe manualmente em https://www.arduino.cc/en/software"
            }
        } else {
            $script:Warnings += 'winget indisponivel; baixe a Arduino IDE manualmente em https://www.arduino.cc/en/software'
        }
        Write-Log 'Depois de instalar, adicione o indice de placas ESP32 em Preferencias -> URLs adicionais:' 'INFO'
        Write-Log '  https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json' 'INFO'
        Write-Log 'E instale a placa "esp32 by Espressif Systems" pelo Gerenciador de Placas.' 'INFO'
    } else {
        Write-Log 'PULANDO instalacao do Arduino IDE (nao e necessaria para desenvolver o dashboard/API -- so para gravar firmware no ESP32). Use install.bat -PrepararArduino se quiser instalar agora.' 'PULAR'
    }
}
if ($esp32Libs.Count -gt 0) {
    Write-Log 'Bibliotecas Arduino que o firmware realmente usa (instalar pelo Gerenciador de Bibliotecas quando for compilar o .ino):' 'INFO'
    foreach ($lib in $esp32Libs) { Write-Log "  - $($lib.Library) -- $($lib.Reason)" 'INFO' }
}
Write-Log 'Nenhuma senha de Wi-Fi ou API key real foi lida nem gravada por este instalador -- essas credenciais ficam no proprio firmware/config, configuradas manualmente por voce.' 'INFO'

# -----------------------------------------------------------------
Step-Header 9 $TotalSteps 'Validacao final'
# -----------------------------------------------------------------
$backendOk = $false
try {
    $resp = Invoke-WebRequest -Uri 'http://localhost:8000/view/html/acesso.php' -UseBasicParsing -TimeoutSec 5
    $backendOk = ($resp.StatusCode -eq 200)
} catch { $backendOk = $false }
if ($backendOk) { Write-Log 'Dashboard respondeu em http://localhost:8000/view/html/acesso.php (HTTP 200).' 'OK' }
else { $script:Warnings += 'Nao foi possivel confirmar que http://localhost:8000 esta respondendo agora.' }

Write-Host ''
Write-Host '========================================' -ForegroundColor Green
Write-Host ' SADAG - RELATORIO FINAL' -ForegroundColor Green
Write-Host '========================================' -ForegroundColor Green

$rows = @(
    @{ Nome = 'Git'; Ok = $git.Found }
    @{ Nome = 'GitHub CLI'; Ok = $gh.Found }
    @{ Nome = 'VS Code'; Ok = $vscode.Found }
    @{ Nome = 'PHP'; Ok = ($php.Found -and $php.Missing.Count -eq 0) }
    @{ Nome = 'MySQL (servico/porta)'; Ok = $mysql.PortListening }
    @{ Nome = 'MySQL (conexao real)'; Ok = $mysql.CanConnect }
    @{ Nome = 'Caddy'; Ok = ($caddy.LocalExeFound -and $caddy.ValidateOk) }
    @{ Nome = 'Backend (HTTP 200)'; Ok = $backendOk }
)
foreach ($r in $rows) {
    $tag = if ($r.Ok) { '[OK]' } else { '[FALHA]' }
    $color = if ($r.Ok) { 'Green' } else { 'Red' }
    Write-Host ("  {0,-26} {1}" -f $r.Nome, $tag) -ForegroundColor $color
}

Write-Host ''
Write-Host "Projeto:  $script:ProjectRoot"
Write-Host "Backend:  http://localhost:8000/view/html/acesso.php"
Write-Host "Banco:    localhost:3306 / banco 'aeris'"
if ($php.Found) { Write-Host "PHP:      $($php.Version -join ' ')" }
if ($git.Found) { Write-Host "Git:      $($git.Version)" }
if ($caddy.LocalExeFound) { Write-Host "Caddy:    $($caddy.Version)" }

if ($script:Warnings.Count -gt 0) {
    Write-Host ''
    Write-Host '[ATENCAO] Itens que precisam de acao manual:' -ForegroundColor Yellow
    foreach ($w in $script:Warnings) { Write-Host "  - $w" -ForegroundColor Yellow }
}

if ($script:CriticalFailures.Count -gt 0) {
    Write-Host ''
    Write-Host '[ERRO] Itens criticos que impedem considerar o setup concluido:' -ForegroundColor Red
    foreach ($f in $script:CriticalFailures) { Write-Host "  - $f" -ForegroundColor Red }
    Write-Host ''
    Write-Host 'SETUP INCOMPLETO. Resolva os itens acima e rode install.bat novamente (ele pula tudo que ja estiver certo).' -ForegroundColor Red
    Write-Log 'Setup finalizado com falhas criticas.' 'ERRO'
    exit 1
} else {
    Write-Host ''
    Write-Host 'SETUP CONCLUIDO. Ambiente pronto para desenvolver o Sadag.' -ForegroundColor Green
    Write-Log 'Setup finalizado com sucesso.' 'OK'
    exit 0
}
