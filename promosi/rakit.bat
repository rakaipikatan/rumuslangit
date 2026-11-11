@echo off
chcp 65001 >nul
cd /d "%~dp0"
rem Lokasi Python: bila ada berkas jalur_python.txt (satu baris, tanpa tanda kutip) dipakai itu; bila tidak, "python" dari PATH.
set "PY=python"
if exist "jalur_python.txt" set /p PY=<jalur_python.txt
"%PY%" --version >nul 2>nul
if errorlevel 1 (
  echo Python tidak ditemukan: %PY%
  echo Isi berkas jalur_python.txt dengan lokasi python.exe Anda, atau pasang Python dan centang "Add python.exe to PATH".
  pause
  exit /b 1
)
"%PY%" -m pip install -q -r requirements.txt
if errorlevel 1 (
  echo.
  echo Pemasangan komponen gagal. Salin pesan di atas ke chat untuk dibantu.
  pause
  exit /b 1
)
"%PY%" rakit.py %*
echo.
pause
