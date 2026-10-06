# NDMU-RMAS Emergency Local / LAN Server for Defense Day
# Runs Laravel bound to 0.0.0.0:8000 so devices on the same Wi-Fi / Hotspot can connect.

$ErrorActionPreference = 'Continue'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot

Write-Host "==============================================================" -ForegroundColor DarkGreen
Write-Host "       NDMU-RMAS DEFENSE CONTINGENCY / LAN SERVER" -ForegroundColor Green
Write-Host "==============================================================" -ForegroundColor DarkGreen

# 1. Determine Local IPv4 Address
$localIp = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object { 
    $_.InterfaceAlias -notlike "*Loopback*" -and 
    $_.InterfaceAlias -notlike "*vEthernet*" -and 
    $_.IPAddress -notlike "169.254*" 
} | Select-Object -ExpandProperty IPAddress -First 1)

if (-not $localIp) {
    $localIp = "127.0.0.1"
}

$port = 8000

Write-Host ""
Write-Host "[+] Local Machine URL : " -NoNewline; Write-Host "http://localhost:${port}" -ForegroundColor Cyan
Write-Host "[+] Panelists / LAN URL: " -NoNewline; Write-Host "http://${localIp}:${port}" -ForegroundColor Yellow
Write-Host ""

# 2. Check .env for SESSION_SECURE_COOKIE
$envFile = Join-Path $projectRoot '.env'
if (Test-Path $envFile) {
    $envContent = Get-Content $envFile -Raw
    if ($envContent -match 'SESSION_SECURE_COOKIE\s*=\s*true') {
        Write-Host "[!] WARNING: SESSION_SECURE_COOKIE=true in .env" -ForegroundColor Yellow
        Write-Host "    Browsers will block session cookies on plain HTTP (http://${localIp}:${port})." -ForegroundColor Yellow
        Write-Host "    For local LAN fallback, set SESSION_SECURE_COOKIE=false in .env" -ForegroundColor White
        Write-Host ""
    }
}

# 3. Check / Add Windows Firewall Rule for Port 8000
$ruleName = "NDMU-RMAS Defense Port $port"
$existingRule = Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue
if (-not $existingRule) {
    Write-Host "[*] Adding Windows Firewall rule for inbound TCP port $port..." -ForegroundColor Gray
    try {
        New-NetFirewallRule -DisplayName $ruleName -Direction Inbound -LocalPort $port -Protocol TCP -Action Allow -ErrorAction Stop | Out-Null
        Write-Host "[v] Firewall rule added successfully." -ForegroundColor Green
    } catch {
        Write-Host "[i] Note: Run PowerShell as Administrator if external devices cannot reach port $port." -ForegroundColor DarkYellow
    }
} else {
    Write-Host "[v] Windows Firewall rule for port $port is already active." -ForegroundColor Green
}

# 4. Check PostgreSQL Docker Container
$pgContainer = "ndmu_rmas_postgres"
$dockerCheck = docker ps --filter "name=$pgContainer" --format "{{.Status}}" 2>$null
if ($dockerCheck -like "*Up*") {
    Write-Host "[v] PostgreSQL container ($pgContainer) is running." -ForegroundColor Green
} else {
    Write-Host "[!] PostgreSQL container ($pgContainer) is not running!" -ForegroundColor Red
    Write-Host "    Attempting to start container..." -ForegroundColor Yellow
    docker start $pgContainer 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "[v] Started $pgContainer successfully." -ForegroundColor Green
    } else {
        Write-Host "[!] Could not start $pgContainer. Ensure Docker Desktop is running." -ForegroundColor Red
    }
}

# 5. Set PHP Server Workers and Launch Server
$env:PHP_CLI_SERVER_WORKERS = '8'

Write-Host ""
Write-Host "--------------------------------------------------------------" -ForegroundColor DarkGray
Write-Host "Starting server on 0.0.0.0:$port (Workers: 8)..." -ForegroundColor Green
Write-Host "Share this link with your Panelists & Facilitators:" -ForegroundColor White
Write-Host ">>> http://${localIp}:${port} <<<" -ForegroundColor Yellow -BackgroundColor DarkGreen
Write-Host "Press Ctrl+C at any time to stop the server." -ForegroundColor Gray
Write-Host "--------------------------------------------------------------" -ForegroundColor DarkGray
Write-Host ""

php artisan serve --host=0.0.0.0 --port=$port
