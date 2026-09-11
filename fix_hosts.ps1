# Fix script for Open Server hosts and permissions
$ErrorActionPreference = "Continue"

Write-Host "1. Cleaning temporary hosts files..." -ForegroundColor Cyan
if (Test-Path "C:\Windows\System32\drivers\etc\hosts.tmp") {
    Remove-Item "C:\Windows\System32\drivers\etc\hosts.tmp" -Force
    Write-Host "   Deleted hosts.tmp" -ForegroundColor Green
} else {
    Write-Host "   hosts.tmp not found (already clean)" -ForegroundColor Gray
}

Write-Host "2. Resetting attributes on hosts file..." -ForegroundColor Cyan
attrib -r -h -s "C:\Windows\System32\drivers\etc\hosts"

Write-Host "3. Granting modify permissions to Users on hosts..." -ForegroundColor Cyan
& icacls "C:\Windows\System32\drivers\etc\hosts" /grant "Users:(M)" /c | Out-Null

Write-Host "4. Adding Windows Defender exclusions..." -ForegroundColor Cyan
try {
    Add-MpPreference -ExclusionPath "C:\Windows\System32\drivers\etc\hosts", "D:\OSPanel" -ErrorAction SilentlyContinue
    Add-MpPreference -ExclusionProcess "Open Server Panel.exe" -ErrorAction SilentlyContinue
    Write-Host "   Exclusions added to Windows Defender" -ForegroundColor Green
} catch {
    Write-Host "   Could not add Defender exclusions: $_" -ForegroundColor Yellow
}

Write-Host "5. Updating hosts file entries..." -ForegroundColor Cyan
$hostsPath = "C:\Windows\System32\drivers\etc\hosts"
$content = [System.IO.File]::ReadAllText($hostsPath)

$ospBlock = @"
# Start Open Server 43db4a8d240df3094967da14cf2a4b9c_hosts
127.0.0.1 synthesia
127.0.0.1 storage
# End Open Server 43db4a8d240df3094967da14cf2a4b9c_hosts
"@

if ($content -match '(?s)# Start Open Server.*?# End Open Server[^\r\n]*') {
    $newContent = $content -replace '(?s)# Start Open Server.*?# End Open Server[^\r\n]*', $ospBlock
} else {
    $newContent = $ospBlock + "`r`n" + $content
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($hostsPath, $newContent, $utf8NoBom)
Write-Host "   hosts file updated with domain synthesia and storage" -ForegroundColor Green

Write-Host "6. Flushing DNS cache..." -ForegroundColor Cyan
ipconfig /flushdns | Out-Null

Write-Host "`nAll done! You can close this window." -ForegroundColor Green
Start-Sleep -Seconds 3
