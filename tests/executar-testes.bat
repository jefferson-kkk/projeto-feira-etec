@echo off
setlocal
title Sadag - Executar testes automatizados

set "TESTS_DIR=%~dp0"
set "PROJECT_DIR=%TESTS_DIR%.."

echo ========================================
echo  SADAG - Testes automatizados
echo ========================================
echo.

if not exist "C:\php\php.exe" (
    echo ERRO: nao encontrei C:\php\php.exe
    echo Rode setup\install.bat primeiro para instalar o PHP.
    pause
    exit /b 1
)

echo Para tambem testar login e o chat com IA, digite a senha da conta
echo de teste (jecorrea.2008@gmail.com) e aperte ENTER.
echo Para pular esses testes (so os que nao exigem login), aperte ENTER vazio.
echo.
set /p SADAG_TEST_PASSWORD="Senha (ou ENTER para pular): "

echo.
"C:\php\php.exe" "%TESTS_DIR%run_all.php"
set "EXITCODE=%ERRORLEVEL%"

echo.
pause
exit /b %EXITCODE%
