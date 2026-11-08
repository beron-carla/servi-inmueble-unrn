@echo off
REM Verificación de entorno de ServiInmueble 2026.
REM Uso: scripts\check_env.bat

C:\xampp\php\php.exe "%~dp0check_env.php"
exit /b %ERRORLEVEL%
