@echo off
title Sadag - Verificacao do ambiente

rem So verifica, nunca instala nada -- por isso nao precisa de
rem privilegios de administrador.
set "SETUP_DIR=%~dp0"

powershell -NoProfile -ExecutionPolicy Bypass -File "%SETUP_DIR%verify.ps1"
set "EXITCODE=%ERRORLEVEL%"

echo.
pause
exit /b %EXITCODE%
