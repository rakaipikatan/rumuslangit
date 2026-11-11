@echo off
chcp 65001 >nul
cd /d "%~dp0"
where python >nul 2>nul
if errorlevel 1 (
  echo Python belum terpasang. Pasang dulu dari https://www.python.org/downloads/
  echo Saat memasang, CENTANG kotak "Add python.exe to PATH".
  pause
  exit /b 1
)
python -m pip install -q -r requirements.txt
python rakit.py %*
echo.
pause
