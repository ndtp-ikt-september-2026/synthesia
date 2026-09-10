$base = 'd:\OSPanel\domains\synthesia'
$dist = Join-Path $base 'dist\soundnet_tracklist'
$zipPath = Join-Path $base 'soundnet_tracklist.ocmod.zip'

if (Test-Path $zipPath) { Remove-Item -Force $zipPath }

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zipArchive = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)

# Recursively add all files from $dist with forward slash relative paths
Get-ChildItem -Path $dist -Recurse -File | ForEach-Object {
    $relPath = $_.FullName.Substring($dist.Length + 1).Replace('\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipArchive, $_.FullName, $relPath, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
}

$zipArchive.Dispose()

Write-Output ("ZIP CREATED WITH FORWARD SLASHES: {0} ({1} bytes)" -f $zipPath, (Get-Item $zipPath).Length)
