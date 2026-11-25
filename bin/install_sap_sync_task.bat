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

set "SCRIPT=%~dp0sync_sap_stock.php"
set "TASK_NAME=Qarshi SAP S4HANA Stock Sync"
set "TASK_COMMAND=\"%PHP_EXE%\" \"%SCRIPT%\""

REM Initial recommended schedule: daily at 01:15 AM.
REM Change /ST below after the SAP team approves the production synchronization window.
schtasks /Create /TN "%TASK_NAME%" /TR "%TASK_COMMAND%" /SC DAILY /ST 01:15 /F

if errorlevel 1 (
    echo.
    echo Task creation failed. Open Command Prompt as Administrator and run this file again.
) else (
    echo.
    echo SAP S/4HANA stock synchronization task created for 01:15 AM daily.
)
pause
