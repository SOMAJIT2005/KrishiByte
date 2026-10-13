param([switch]$NoBrowser)
$ErrorActionPreference = 'Stop'
$projectDirectory = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
try {
    $phpBinary = (Get-Command php -ErrorAction SilentlyContinue).Source
    if (!$phpBinary) { throw 'PHP was not found. Start Wampserver or add PHP to PATH.' }
    Push-Location $projectDirectory
    try {
        & $phpBinary artisan manual:populate
        if ($LASTEXITCODE -ne 0) { throw 'Demo population failed. Check local database settings and migrations.' }
        & $phpBinary tools/create-demo-agreement.php
        if ($LASTEXITCODE -ne 0) { throw 'Demo upload fixture generation failed.' }
    } finally { Pop-Location }
    & (Join-Path $PSScriptRoot 'Start-Showcase.ps1') -NoBrowser:$NoBrowser
} catch { Write-Host $_.Exception.Message -ForegroundColor Red; exit 1 }
