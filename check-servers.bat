@echo off
echo === Checking Laravel Servers ===
echo.
echo Checking if localhost:8000 is responding:
curl -s http://localhost:8000 >nul 2>&1 && echo "Server running on port 8000" || echo "No server on port 8000"
echo.
echo Checking if localhost:8001 is responding:
curl -s http://localhost:8001 >nul 2>&1 && echo "Server running on port 8001" || echo "No server on port 8001"
echo.
echo All PHP processes:
wmic process where "name='php.exe'" get ProcessId,CommandLine 2>nul || echo "No PHP processes found or WMIC not available"
echo.
pause