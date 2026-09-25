$files = @(
    'config/helpers.php',
    'includes/header.php',
    'supervisor/index.php',
    'supervisor/interns.php',
    'supervisor/attendance.php',
    'supervisor/tasks.php',
    'supervisor/appraisal.php',
    'login.php',
    'DEPLOYMENT_GUIDE.md',
    'database/activate_all_supervisors.sql'
)

Write-Host "=== Starting AWT-IMS Supervisor System Deployment ==="
Write-Host "Target: ftp://65.21.160.27/"
Write-Host "Total files to deploy: $($files.Count)"
Write-Host ""

$successCount = 0
$failCount = 0

foreach ($f in $files) {
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Uploading: $f ..."
    
    # Retry up to 2 times if rate limited
    $retries = 0
    $uploaded = $false
    while ($retries -lt 2 -and -not $uploaded) {
        & curl.exe --silent --show-error --connect-timeout 30 -m 60 -u 'iternawt:e^18bD4q3' -T "$f" "ftp://65.21.160.27/$f"
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  -> SUCCESS: $f uploaded successfully" -ForegroundColor Green
            $uploaded = $true
            $successCount++
        } else {
            $retries++
            Write-Host "  -> Attempt $retries failed (Exit code: $LASTEXITCODE). Waiting 35s for rate limit reset..." -ForegroundColor Yellow
            Start-Sleep -Seconds 35
        }
    }

    if (-not $uploaded) {
        Write-Host "  -> FAILED: $f could not be uploaded" -ForegroundColor Red
        $failCount++
    }

    Write-Host "  -> Waiting 25s cooldown before next file..."
    Start-Sleep -Seconds 25
}

Write-Host ""
Write-Host "=== Deployment Summary ==="
Write-Host "Success: $successCount / $($files.Count)"
Write-Host "Failed:  $failCount"
