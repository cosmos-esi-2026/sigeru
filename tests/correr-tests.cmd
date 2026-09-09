@echo off
REM ============================================================================
REM  SiGeRU 2026 - Corre los tests de la API
REM
REM  Calcula su propia ruta con %~dp0, asi que funciona monte donde monte el
REM  pendrive (D:, E:, F:). No hay ninguna letra de unidad escrita a mano.
REM
REM  Requiere Apache y MySQL corriendo: herramientas\xampp\iniciar-xampp.cmd
REM ============================================================================
setlocal
set "BASE=%~dp0"
set "PROYECTO=%BASE%..\.."
for %%I in ("%PROYECTO%") do set "PROYECTO=%%~fI"

"%PROYECTO%\herramientas\php\php.exe" "%BASE%correr-tests.php"

echo.
pause
