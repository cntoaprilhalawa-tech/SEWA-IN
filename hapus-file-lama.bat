@echo off
REM Jalankan file ini dari DALAM folder proyek sewa-in (yang berisi artisan).
if not exist artisan (
  echo Salah folder. Letakkan dan jalankan file ini di folder proyek yang berisi file "artisan".
  pause
  exit /b 1
)
del /q "app\Http\Controllers\Auth\VerificationController.php" 2>nul
del /q "resources\views\auth\verify-email.blade.php" 2>nul
del /q "resources\views\dashboard.blade.php" 2>nul
echo File lama sudah dihapus.
pause
