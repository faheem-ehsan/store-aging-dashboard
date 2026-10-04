@echo off
setlocal
set "PHP_EXE=C:\xampp\php\php.exe"
if not exist "%PHP_EXE%" (
    for /f "delims=" %%I in ('where php 2^>nul') do set "PHP_EXE=%%I"
)
if not exist "%PHP_EXE%" (
    echo PHP executable was not found. Edit this file and set PHP_EXE.
    pause
    exit /b 1
)
"%PHP_EXE%" "%~dp0send_grade_alerts.php"
pause
