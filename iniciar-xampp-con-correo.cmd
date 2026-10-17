@echo off
REM Ejecuta el lanzador de correo sin cambiar la politica global de PowerShell.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0iniciar-xampp-con-correo.ps1"
exit /b %ERRORLEVEL%
