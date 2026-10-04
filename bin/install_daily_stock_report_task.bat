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

set "SCRIPT=%~dp0generate_daily_stock_reports.php"
set "TASK_NAME=Qarshi Daily Stock Closing Snapshot"
set "TASK_COMMAND=\"%PHP_EXE%\" \"%SCRIPT%\""

schtasks /Create /TN "%TASK_NAME%" /TR "%TASK_COMMAND%" /SC DAILY /ST 23:59 /F

if errorlevel 1 (
    echo.
    echo Task creation failed. Open Command Prompt as Administrator and run this file again.
) else (
    echo.
    echo Daily Stock Closing Snapshot task created for 11:59 PM.
)
pause
