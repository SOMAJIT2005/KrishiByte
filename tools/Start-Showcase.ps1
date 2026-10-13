param([switch]$NoBrowser, [ValidateRange(1024,65535)][int]$Port = 8000)
$ErrorActionPreference = 'Stop'
$projectDirectory = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
try {
    $phpBinary = (Get-Command php -ErrorAction SilentlyContinue).Source
    if (!$phpBinary) {
        $phpBinary = Get-ChildItem 'D:\wamp64\bin\php\php*\php.exe' -ErrorAction SilentlyContinue | Sort-Object FullName -Descending | Select-Object -First 1 -ExpandProperty FullName
    }
    if (!$phpBinary) { throw 'PHP was not found. Start Wampserver or add PHP to PATH.' }
    $mysqlProbe = New-Object Net.Sockets.TcpClient
    try { $mysqlProbe.Connect('127.0.0.1',3306) } catch { throw 'MySQL is unavailable. Start Wampserver, then run this file again.' } finally { $mysqlProbe.Dispose() }
    $url = "http://127.0.0.1:$Port/demo-showcase"
    $listener = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
    if (!$listener) {
        $logsDirectory = Join-Path $projectDirectory 'storage\logs'
        $phpProcess = Start-Process -FilePath $phpBinary -ArgumentList @('-S',"127.0.0.1:$Port",'-t','public') -WorkingDirectory $projectDirectory -WindowStyle Hidden -RedirectStandardOutput (Join-Path $logsDirectory "showcase-server-$Port.log") -RedirectStandardError (Join-Path $logsDirectory "showcase-server-errors-$Port.log") -PassThru
        Set-Content -LiteralPath (Join-Path $logsDirectory "showcase-server-$Port.pid") -Value $phpProcess.Id
    }
    $ready = $false
    for ($attempt=0; $attempt -lt 20; $attempt++) {
        try {
            $page = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 3
            if ($page.Content -notmatch 'KrishiByte showcase') { throw "Port $Port is serving another application." }
            $ready = $true; break
        } catch { if ($listener) { throw }; Start-Sleep -Milliseconds 300 }
    }
    if (!$ready) { throw "The showcase did not start. Check storage\logs\showcase-server-errors-$Port.log and your local .env database settings." }
    Write-Host "KrishiByte showcase is ready: $url" -ForegroundColor Green
    if (!$NoBrowser) { Start-Process $url }
} catch {
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
