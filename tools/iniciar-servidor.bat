@echo off
echo Iniciando Sadag (Caddy + PHP FastCGI)...

taskkill /F /IM caddy.exe >nul 2>&1
taskkill /F /IM php-cgi.exe >nul 2>&1
timeout /t 1 >nul

set PHP_FCGI_CHILDREN=8
set PHP_FCGI_MAX_REQUESTS=0
start "PHP FastCGI" /MIN C:\php\php-cgi.exe -b 127.0.0.1:9123

timeout /t 1 >nul

cd /d "%~dp0"
start "Caddy" /MIN caddy.exe run --config Caddyfile

echo.
echo Pronto! Acesse http://localhost:8000/view/html/acesso.php
echo (Essa janela pode ser fechada; os processos continuam rodando em segundo plano.)
echo Para PARAR tudo: feche pelo Gerenciador de Tarefas os processos "caddy.exe" e "php-cgi.exe".
pause
