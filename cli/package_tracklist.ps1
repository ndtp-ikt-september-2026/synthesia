$base = 'd:\OSPanel\domains\synthesia'
$dist = Join-Path $base 'dist\soundnet_tracklist'
if (Test-Path $dist) { Remove-Item -Recurse -Force $dist }

$paths = @(
    'upload\admin\controller\extension\module',
    'upload\admin\language\en-gb\extension\module',
    'upload\admin\language\ru-ru\extension\module',
    'upload\admin\model\extension\module',
    'upload\admin\view\template\extension\module',
    'upload\catalog\controller\extension\module',
    'upload\catalog\language\en-gb\extension\module',
    'upload\catalog\language\ru-ru\extension\module',
    'upload\catalog\model\extension\module',
    'upload\catalog\view\theme\default\template\extension\module',
    'upload\catalog\view\javascript\soundnet_tracklist'
)

foreach ($p in $paths) {
    New-Item -ItemType Directory -Path (Join-Path $dist $p) -Force | Out-Null
}

Copy-Item (Join-Path $base 'install.xml') (Join-Path $dist 'install.xml') -Force

# Admin files
Copy-Item (Join-Path $base 'admin\controller\extension\module\soundnet_tracklist.php') (Join-Path $dist 'upload\admin\controller\extension\module\soundnet_tracklist.php') -Force
Copy-Item (Join-Path $base 'admin\language\en-gb\extension\module\soundnet_tracklist.php') (Join-Path $dist 'upload\admin\language\en-gb\extension\module\soundnet_tracklist.php') -Force
Copy-Item (Join-Path $base 'admin\language\ru-ru\extension\module\soundnet_tracklist.php') (Join-Path $dist 'upload\admin\language\ru-ru\extension\module\soundnet_tracklist.php') -Force
Copy-Item (Join-Path $base 'admin\model\extension\module\soundnet_tracklist.php') (Join-Path $dist 'upload\admin\model\extension\module\soundnet_tracklist.php') -Force
Copy-Item (Join-Path $base 'admin\view\template\extension\module\soundnet_tracklist.twig') (Join-Path $dist 'upload\admin\view\template\extension\module\soundnet_tracklist.twig') -Force

# Catalog files
Copy-Item (Join-Path $base 'catalog\controller\extension\module\soundnet_tracklist.php') (Join-Path $dist 'upload\catalog\controller\extension\module\soundnet_tracklist.php') -Force
Copy-Item (Join-Path $base 'catalog\language\en-gb\extension\module\soundnet_tracklist.php') (Join-Path $dist 'upload\catalog\language\en-gb\extension\module\soundnet_tracklist.php') -Force
Copy-Item (Join-Path $base 'catalog\language\ru-ru\extension\module\soundnet_tracklist.php') (Join-Path $dist 'upload\catalog\language\ru-ru\extension\module\soundnet_tracklist.php') -Force
Copy-Item (Join-Path $base 'catalog\model\extension\module\soundnet_tracklist.php') (Join-Path $dist 'upload\catalog\model\extension\module\soundnet_tracklist.php') -Force
Copy-Item (Join-Path $base 'catalog\view\theme\default\template\extension\module\soundnet_tracklist.twig') (Join-Path $dist 'upload\catalog\view\theme\default\template\extension\module\soundnet_tracklist.twig') -Force
Copy-Item (Join-Path $base 'catalog\view\javascript\soundnet_tracklist\tracklist.js') (Join-Path $dist 'upload\catalog\view\javascript\soundnet_tracklist\tracklist.js') -Force
Copy-Item (Join-Path $base 'catalog\view\javascript\soundnet_tracklist\tracklist.css') (Join-Path $dist 'upload\catalog\view\javascript\soundnet_tracklist\tracklist.css') -Force

# Create ZIP archive
$zipPath = Join-Path $base 'soundnet_tracklist.ocmod.zip'
if (Test-Path $zipPath) { Remove-Item -Force $zipPath }
Compress-Archive -Path (Join-Path $dist '*') -DestinationPath $zipPath -Force

Write-Output ("PACKAGING COMPLETE! Archive: {0} ({1} bytes)" -f $zipPath, (Get-Item $zipPath).Length)
