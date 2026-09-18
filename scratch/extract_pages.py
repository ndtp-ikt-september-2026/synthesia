import subprocess
import sys
import re

sys.stdout.reconfigure(encoding='utf-8')

ps_code = """
$word = New-Object -ComObject Word.Application
$word.Visible = $false
try {
    $doc = $word.Documents.Open("d:\\OSPanel\\domains\\synthesia\\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx")
    for ($i = 1; $i -le $doc.Paragraphs.Count; $i++) {
        $p = $doc.Paragraphs.Item($i)
        # Skip if inside a table
        if ($p.Range.Tables.Count -gt 0) {
            continue
        }
        $txt = $p.Range.Text.Trim()
        # Skip lines with tabs (TOC items)
        if ($txt.Contains("`t")) {
            continue
        }
        if ($txt.Length -gt 2 -and $txt.Length -lt 120) {
            if ($txt -match "^(ВВЕДЕНИЕ|ЗАКЛЮЧЕНИЕ|СПИСОК ИСПОЛЬЗОВАННЫХ ИСТОЧНИКОВ|ПРИЛОЖЕНИЕ|[1-4] [А-Я]|[1-4]\.[1-4])") {
                $pageNum = $p.Range.Information(3) # wdActiveEndPageNumber
                Write-Host "HEADING|$pageNum|$txt"
            }
        }
    }
    $doc.Close([ref]$false)
} catch {
    Write-Host "COM_ERROR: $($_.Exception.Message)"
} finally {
    $word.Quit([ref]$false)
}
"""

with open("scratch/get_clean_headings.ps1", "w", encoding="utf-8-sig") as f:
    f.write(ps_code)

res = subprocess.run(["powershell", "-ExecutionPolicy", "Bypass", "-File", "scratch/get_clean_headings.ps1"], capture_output=True, text=True, encoding="utf-8", errors="replace")

clean_map = {}
for line in res.stdout.splitlines():
    if line.startswith("HEADING|"):
        parts = line.split("|", 2)
        if len(parts) == 3:
            p_num = parts[1].strip()
            title = parts[2].strip()
            print(f"{p_num} : {title}")
            clean_map[title] = p_num

print("\nJSON-ready page map:")
import json
print(json.dumps(clean_map, ensure_ascii=False, indent=2))
