$base = 'd:\OSPanel\domains\synthesia'
$zipPath = Join-Path $base 'soundnet_storefront.ocmod.zip'

if (Test-Path $zipPath) { Remove-Item -Force $zipPath }

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zipArchive = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)

# 1. Add install.xml at the root
$installXmlPath = Join-Path $base 'install.xml'
if (Test-Path $installXmlPath) {
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipArchive, $installXmlPath, 'install.xml', [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    Write-Output "Added install.xml to root of zip."
}

# 2. Add upload/ folder files with forward slashes
$uploadDir = Join-Path $base 'upload'
if (Test-Path $uploadDir) {
    Get-ChildItem -Path $uploadDir -Recurse -File | ForEach-Object {
        $relPath = "upload/" + $_.FullName.Substring($uploadDir.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipArchive, $_.FullName, $relPath, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
    Write-Output "Added all upload/ files with forward slashes."
}

$zipArchive.Dispose()
Write-Output ("SUCCESS: soundnet_storefront.ocmod.zip created ({0} bytes)" -f (Get-Item $zipPath).Length)
