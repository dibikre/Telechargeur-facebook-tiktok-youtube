@echo off
echo ===================================================
echo   MicMediaFetch - Installation et Demarrage du Projet
echo ===================================================

echo.
echo [1/2] Installation des dependances...
call npm install

echo.
echo [2/2] Lancement de l'application Angular sur le port 5399...
call npx ng serve --port 5399 --host 0.0.0.0

pause
