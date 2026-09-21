@echo off
setlocal EnableDelayedExpansion
title Sadag - Setup do ambiente

rem Nunca depende de caminho pessoal fixo: a raiz do projeto e
rem sempre a pasta onde este .bat esta, um nivel acima de setup\.
set "SETUP_DIR=%~dp0"
set "PROJECT_DIR=%SETUP_DIR%.."

rem --- Verifica se ja esta rodando como administrador ---
net session >nul 2>&1
if %errorLevel% NEQ 0 (
    echo Este instalador precisa de privilegios de administrador
    echo ^(para servico do MySQL, php.ini e regra de firewall^).
    echo Solicitando elevacao...
    rem O parametro -ArgumentList do Start-Process NAO aceita string vazia:
    rem passar -ArgumentList '' (caso normal, sem argumentos) derruba o
    rem PowerShell com "o argumento e nulo ou vazio" e a elevacao nunca
    rem acontece. Por isso so passamos -ArgumentList quando ha argumentos.
    if "%~1"=="" (
        powershell -NoProfile -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    ) else (
        powershell -NoProfile -Command "Start-Process -FilePath '%~f0' -ArgumentList '%*' -Verb RunAs"
    )
    if errorlevel 1 (
        echo.
        echo Nao foi possivel abrir o instalador como administrador
        echo ^(UAC cancelado ou bloqueado^).
        echo Clique com o botao direito em install.bat e escolha
        echo "Executar como administrador".
        echo.
        pause
    )
    exit /b
)

echo ========================================
echo  SADAG - Setup do ambiente de desenvolvimento
echo ========================================
echo Rodando como administrador. Pasta do projeto: %PROJECT_DIR%
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%SETUP_DIR%install.ps1" %*
set "EXITCODE=%ERRORLEVEL%"

echo.
if %EXITCODE% EQU 0 (
    echo Setup finalizado. Veja o resumo acima.
) else (
    echo Setup finalizado com pendencias. Veja o resumo acima.
)
echo Log completo em: %SETUP_DIR%logs\
echo.
pause
exit /b %EXITCODE%
