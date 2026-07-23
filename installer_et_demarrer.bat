@echo off
echo ===================================================
echo   MicMediaFetch - Installation et Demarrage du Projet
echo ===================================================

echo.
echo [1/2] Installation des dependances avec pnpm...
call pnpm install

echo.
echo [2/2] Lancement de l'application Angular sur le port 3000...
call pnpm exec ng serve --port 3000 --host 0.0.0.0 --allowed-hosts=all --allowed-hosts=localhost --allowed-hosts=127.0.0.1

pause
