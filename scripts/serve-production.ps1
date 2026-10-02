$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$phpExecutable = 'C:\php\php.exe'

Set-Location -LiteralPath $projectRoot

$logDirectory = Join-Path $projectRoot 'storage\logs'
$serverOut = Join-Path $logDirectory 'production-server.out.log'
$serverError = Join-Path $logDirectory 'production-server.error.log'
$schedulerOut = Join-Path $logDirectory 'production-scheduler.out.log'
$schedulerError = Join-Path $logDirectory 'production-scheduler.error.log'

function Start-NdmuProcess {
    param(
        [Parameter(Mandatory = $true)][string[]] $Arguments,
        [Parameter(Mandatory = $true)][string] $StandardOutput,
        [Parameter(Mandatory = $true)][string] $StandardError
    )

    Start-Process `
        -FilePath $phpExecutable `
        -ArgumentList $Arguments `
        -WorkingDirectory $projectRoot `
        -WindowStyle Hidden `
        -RedirectStandardOutput $StandardOutput `
        -RedirectStandardError $StandardError `
        -PassThru
}

$server = Start-NdmuProcess -Arguments @('artisan', 'serve', '--host=127.0.0.1', '--port=8000') -StandardOutput $serverOut -StandardError $serverError
$scheduler = Start-NdmuProcess -Arguments @('artisan', 'schedule:work') -StandardOutput $schedulerOut -StandardError $schedulerError

while ($true) {
    if ($server.HasExited) {
        $server = Start-NdmuProcess -Arguments @('artisan', 'serve', '--host=127.0.0.1', '--port=8000') -StandardOutput $serverOut -StandardError $serverError
    }
    if ($scheduler.HasExited) {
        $scheduler = Start-NdmuProcess -Arguments @('artisan', 'schedule:work') -StandardOutput $schedulerOut -StandardError $schedulerError
    }
    Start-Sleep -Seconds 5
}
