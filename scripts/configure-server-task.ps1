#Requires -RunAsAdministrator

$ErrorActionPreference = 'Stop'

$taskName = 'NDMU-RMAS Laravel Server'
$serverScript = Join-Path $PSScriptRoot 'serve-production.ps1'

$action = New-ScheduledTaskAction `
    -Execute 'powershell.exe' `
    -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$serverScript`""
$trigger = New-ScheduledTaskTrigger -AtStartup
$principal = New-ScheduledTaskPrincipal `
    -UserId 'SYSTEM' `
    -LogonType ServiceAccount `
    -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit ([TimeSpan]::Zero) `
    -MultipleInstances IgnoreNew

Register-ScheduledTask `
    -TaskName $taskName `
    -Action $action `
    -Trigger $trigger `
    -Principal $principal `
    -Settings $settings `
    -Description 'Runs the NDMU-RMAS Laravel origin for Cloudflare Tunnel.' `
    -Force | Out-Null

Start-ScheduledTask -TaskName $taskName

Write-Output "Registered and started scheduled task: $taskName"
