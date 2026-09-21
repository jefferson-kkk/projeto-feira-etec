@echo off
echo Iniciando Sadag (Caddy + PHP FastCGI)...

taskkill /F /IM caddy.exe >nul 2>&1
taskkill /F /IM php-cgi.exe >nul 2>&1
timeout /t 1 >nul

set PHP_FCGI_CHILDREN=8
set PHP_FCGI_MAX_REQUESTS=0

rem O setup instala o PHP em C:\php, mas se alguem ja tinha o PHP em
rem outro lugar (e no PATH), usamos o que existir em vez de falhar.
set "PHP_CGI=C:\php\php-cgi.exe"
if not exist "%PHP_CGI%" (
    for %%I in (php-cgi.exe) do set "PHP_CGI=%%~$PATH:I"
)
if not exist "%PHP_CGI%" (
    echo ERRO: php-cgi.exe nao encontrado nem em C:\php nem no PATH.
    echo Rode setup\install.bat para instalar o PHP.
    pause
    exit /b 1
)
start "PHP FastCGI" /MIN "%PHP_CGI%" -b 127.0.0.1:9123

timeout /t 1 >nul

cd /d "%~dp0"
start "Caddy" /MIN caddy.exe run --config Caddyfile

echo.
echo Pronto! Acesse http://localhost:8000/view/html/acesso.php
echo (Essa janela pode ser fechada; os processos continuam rodando em segundo plano.)
echo Para PARAR tudo: feche pelo Gerenciador de Tarefas os processos "caddy.exe" e "php-cgi.exe".
pause
