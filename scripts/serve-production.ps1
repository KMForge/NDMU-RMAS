$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$phpExecutable = 'C:\php\php.exe'

Set-Location -LiteralPath $projectRoot

while ($true) {
    & $phpExecutable artisan serve --host=127.0.0.1 --port=8000
    Start-Sleep -Seconds 5
}
