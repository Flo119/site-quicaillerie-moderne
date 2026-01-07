@echo off
title Lancement Quincaillerie Moderne...
echo Demarrage du serveur...

:: Check for PHP in common locations
if exist "C:\xampp\php\php.exe" (
    set PHP_CMD="C:\xampp\php\php.exe"
) else (
    set PHP_CMD=php
)

:: Start Server in background
start /B "" %PHP_CMD% -S localhost:8000

:: Wait a moment for server to spin up
timeout /t 2 /nobreak >nul

:: Open Browser
start http://localhost:8000

echo.
echo ========================================================
echo   LE SITE EST EN LIGNE !
echo   Ne fermez pas cette fenetre noire tant que vous 
echo   utilisez le site.
echo ========================================================
echo.
pause
