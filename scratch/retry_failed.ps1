$failedFiles = @(
    'supervisor/interns.php',
    'login.php',
    'DEPLOYMENT_GUIDE.md',
    'database/activate_all_supervisors.sql'
)

foreach ($f in $failedFiles) {
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Retrying upload: $f ..."
    $retries = 0
    $uploaded = $false
    while ($retries -lt 3 -and -not $uploaded) {
        & curl.exe --silent --show-error --connect-timeout 30 -m 60 -u 'iternawt:e^18bD4q3' -T $f "ftp://65.21.160.27/$f"
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  -> SUCCESS: $f uploaded successfully" -ForegroundColor Green
            $uploaded = $true
        } else {
            $retries++
            Write-Host "  -> Attempt $retries failed (Exit code: $LASTEXITCODE). Waiting 20s before retry..." -ForegroundColor Yellow
            Start-Sleep -Seconds 20
        }
    }
    if (-not $uploaded) {
        Write-Host "  -> FAILED: $f could not be uploaded after retries" -ForegroundColor Red
    }
    Start-Sleep -Seconds 10
}
