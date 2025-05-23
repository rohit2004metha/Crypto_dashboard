@echo off
set "SCRIPT_PATH=%~dp0cron.php"
set "PHP_PATH=C:\xampp\php\php.exe"
set "LOG_PATH=%~dp0cron.log"

:: Create the scheduled task
schtasks /create /tn "XKCD_Comic_Update" /tr "\"%PHP_PATH%\" \"%SCRIPT_PATH%\" >> \"%LOG_PATH%\" 2>&1" /sc daily /st 19:00 /f

echo Windows Task created successfully!
echo Task will run daily at 7:00 PM
echo Log file will be created at: %LOG_PATH%