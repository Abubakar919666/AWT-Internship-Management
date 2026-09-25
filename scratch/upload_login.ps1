& curl.exe --silent --show-error --connect-timeout 30 -m 60 -u 'iternawt:e^18bD4q3' -T 'login.php' 'ftp://65.21.160.27/login.php'
if ($LASTEXITCODE -eq 0) {
    Write-Host "SUCCESS: login.php uploaded" -ForegroundColor Green
} else {
    Write-Host "Attempt 1 failed. Waiting 20s..." -ForegroundColor Yellow
    Start-Sleep -Seconds 20
    & curl.exe --silent --show-error --connect-timeout 30 -m 60 -u 'iternawt:e^18bD4q3' -T 'login.php' 'ftp://65.21.160.27/login.php'
    if ($LASTEXITCODE -eq 0) {
        Write-Host "SUCCESS: login.php uploaded" -ForegroundColor Green
    } else {
        Write-Host "Attempt 2 failed. Waiting 20s..." -ForegroundColor Yellow
        Start-Sleep -Seconds 20
        & curl.exe --silent --show-error --connect-timeout 30 -m 60 -u 'iternawt:e^18bD4q3' -T 'login.php' 'ftp://65.21.160.27/login.php'
        if ($LASTEXITCODE -eq 0) {
            Write-Host "SUCCESS: login.php uploaded" -ForegroundColor Green
        } else {
            Write-Host "FAILED after 3 attempts" -ForegroundColor Red
        }
    }
}
