@echo off
rem Borra todos los usuarios y deja los de prueba (clave 123). Necesita XAMPP andando (iniciar-xampp.cmd).
rem Ver usuarios-de-prueba.sql antes de correrlo.
"%~dp0..\..\..\herramientas\xampp\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 -t sigeru < "%~dp0usuarios-de-prueba.sql"
if errorlevel 1 (echo.
  echo FALLO: revisa que MySQL este andando y que la base se llame sigeru.) else (echo.
  echo Listo. Entra con admin@sigeru.uy, vecino@sigeru.uy, operario@sigeru.uy o cuadrillaa@sigeru.uy ... cuadrillag@sigeru.uy. Clave 123.)
pause
