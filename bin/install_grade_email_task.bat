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
set "SCRIPT=%~dp0send_grade_alerts.php"
set "TASK_NAME=Qarshi Stock Aging Grade Alerts"
set "TASK_COMMAND=\"%PHP_EXE%\" \"%SCRIPT%\""
schtasks /Create /TN "%TASK_NAME%" /TR "%TASK_COMMAND%" /SC DAILY /ST 08:00 /F
if errorlevel 1 (
    echo.
    echo Task creation failed. Open Command Prompt as Administrator and run this file again.
) else (
    echo.
    echo Daily Grade Alert task created for 08:00 AM.
)
pause
