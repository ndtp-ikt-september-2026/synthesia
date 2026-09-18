import subprocess
import os

ps_code = """
$word = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $files = Get-ChildItem "d:\\OSPanel\\domains\\synthesia" -Filter "*.docx"
    foreach ($f in $files) {
        $doc = $word.Documents.Open($f.FullName)
        $pages = $doc.ComputeStatistics(2)
        $words = $doc.ComputeStatistics(0)
        $chars = $doc.ComputeStatistics(3)
        $paras = $doc.ComputeStatistics(4)
        Write-Host "$($f.Name) | Pages: $pages | Words: $words | Chars: $chars | Paras: $paras"
        $doc.Close([ref]$false)
    }
} catch {
    Write-Host "Error: $($_.Exception.Message)"
} finally {
    if ($word -ne $null) {
        $word.Quit([ref]$false)
    }
}
"""

with open("scratch/check_pages_ascii.ps1", "w", encoding="utf-8") as f:
    f.write(ps_code)

res = subprocess.run(["powershell", "-ExecutionPolicy", "Bypass", "-File", "scratch/check_pages_ascii.ps1"], capture_output=True, text=True)
print("STDOUT:\n", res.stdout)
print("STDERR:\n", res.stderr)
