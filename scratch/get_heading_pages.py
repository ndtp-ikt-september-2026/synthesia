import subprocess
import sys

sys.stdout.reconfigure(encoding="utf-8")

ps_code = """
$word = New-Object -ComObject Word.Application
$word.Visible = $false
try {
    $files = Get-ChildItem "d:\\OSPanel\\domains\\synthesia" -Filter "*.docx"
    foreach ($f in $files) {
        Write-Host "=== FILE: $($f.Name) ==="
        $doc = $word.Documents.Open($f.FullName)
        for ($i = 1; $i -le $doc.Paragraphs.Count; $i++) {
            $p = $doc.Paragraphs.Item($i)
            $txt = $p.Range.Text.Trim()
            # check if paragraph starts with heading or is centered uppercase
            if ($p.Format.Alignment -eq 1 -and $txt.Length -gt 2 -and $txt.Length -lt 100) {
                $pageNum = $p.Range.Information(3)
                Write-Host "Page $pageNum : $txt"
            }
        }
        $doc.Close([ref]$false)
    }
} catch {
    Write-Host "Error: $($_.Exception.Message)"
} finally {
    $word.Quit([ref]$false)
}
"""

with open("scratch/check_pages_nobom.ps1", "w", encoding="utf-8-sig") as f:
    f.write(ps_code)

res = subprocess.run(
    ["powershell", "-ExecutionPolicy", "Bypass", "-File", "scratch/check_pages_nobom.ps1"],
    capture_output=True,
    text=True,
    encoding="utf-8",
    errors="replace"
)
print("STDOUT:\n", res.stdout)
print("STDERR:\n", res.stderr)
