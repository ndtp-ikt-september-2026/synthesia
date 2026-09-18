import subprocess
import sys

sys.stdout.reconfigure(encoding='utf-8')

ps_code = """
$word = New-Object -ComObject Word.Application
$word.Visible = $false
try {
    $doc = $word.Documents.Open("d:\\OSPanel\\domains\\synthesia\\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx")
    $total_pages = $doc.ComputeStatistics(2)
    Write-Host "TOTAL_PAGES: $total_pages"
    $doc.Close([ref]$false)
} catch {
    Write-Host "COM_ERROR: $($_.Exception.Message)"
} finally {
    $word.Quit([ref]$false)
}
"""

with open("scratch/get_total_pages.ps1", "w", encoding="utf-8-sig") as f:
    f.write(ps_code)

res = subprocess.run(["powershell", "-ExecutionPolicy", "Bypass", "-File", "scratch/get_total_pages.ps1"], capture_output=True, text=True, encoding="utf-8", errors="replace")
print("RESULT:\n", res.stdout)
