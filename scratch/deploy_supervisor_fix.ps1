$files = @(
    'login.php',
    'config/helpers.php'
)

Write-Host "=== Deploying Supervisor Fix ===" -ForegroundColor Cyan
Write-Host "Files to upload: $($files.Count)"
Write-Host ""

$successCount = 0
$failCount = 0

foreach ($f in $files) {
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Uploading: $f ..."
    $retries = 0
    $uploaded = $false
    while ($retries -lt 3 -and -not $uploaded) {
        & curl.exe --silent --show-error --connect-timeout 30 -m 60 -u 'iternawt:e^18bD4q3' -T $f "ftp://65.21.160.27/$f"
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  -> SUCCESS: $f uploaded" -ForegroundColor Green
            $uploaded = $true
            $successCount++
        } else {
            $retries++
            Write-Host "  -> Attempt $retries failed (Exit: $LASTEXITCODE). Waiting 20s..." -ForegroundColor Yellow
            Start-Sleep -Seconds 20
        }
    }
    if (-not $uploaded) {
        Write-Host "  -> FAILED: $f" -ForegroundColor Red
        $failCount++
    }
    if ($files.IndexOf($f) -lt $files.Count - 1) {
        Write-Host "  -> Waiting 15s cooldown..."
        Start-Sleep -Seconds 15
    }
}

Write-Host ""
Write-Host "=== Summary ===" -ForegroundColor Cyan
Write-Host "Success: $successCount / $($files.Count)" -ForegroundColor Green
Write-Host "Failed:  $failCount" -ForegroundColor Red
