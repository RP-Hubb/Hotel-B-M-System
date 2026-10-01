@echo off
title Adishiv Hotel System - Local Server
echo ===================================================================
echo   Adishiv Luxury Hotel - Booking & Management System
echo ===================================================================

:: Check if MySQL is already running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL daemon is active.
) else (
    echo [*] Starting MySQL server...
    if exist "C:\xampp\mysql\bin\mysqld.exe" (
        start "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
        timeout /t 3 /nobreak >nul
    ) else (
        echo [!] C:\xampp\mysql\bin\mysqld.exe not found. Please start MySQL from XAMPP Control Panel.
    )
)

echo.
echo [OK] Opening local development server...
echo -------------------------------------------------------------------
echo  * Public Website : http://localhost:8000
echo  * Admin Portal   : http://localhost:8000/admin/login.php
echo  * Admin Login    : admin@adishivhotel.com  /  Admin@Adishiv2026
echo -------------------------------------------------------------------
echo Press Ctrl + C to stop the server at any time.
echo.

if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S localhost:8000
) else (
    php -S localhost:8000
)
