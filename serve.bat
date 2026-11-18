@echo off
title PosMyAdmin - PostgreSQL Web Management
echo ========================================================
echo        PosMyAdmin - PostgreSQL Web Dashboard
echo ========================================================
echo.
echo Starting local web server on http://127.0.0.1:8080 ...
echo Press Ctrl+C to stop the server.
echo.
php -S 127.0.0.1:8080 -t public
pause
