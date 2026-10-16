@echo off
title FormaFAD LMS - Server di Sviluppo Locale (PHP 8.2 + Laravel 12 + Alpine.js)
echo ============================================================================
echo   Avvio Piattaforma White-Label FormaFAD LMS su http://127.0.0.1:8000
echo ============================================================================
start http://127.0.0.1:8000
"C:\php82\php.exe" artisan serve --host=127.0.0.1 --port=8000
pause
