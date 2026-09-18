import subprocess
import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

ps_code = """
$OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$word = New-Object -ComObject Word.Application
$word.Visible = $false
try {
    $doc = $word.Documents.Open('d:\\OSPanel\\domains\\synthesia\\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx')
    $doc.Repaginate()
    for ($i = 1; $i -le $doc.Paragraphs.Count; $i++) {
        $p = $doc.Paragraphs.Item($i)
        if ($p.Range.Tables.Count -eq 0) {
            $pageNum = $p.Range.Information(3) # wdActiveEndPageNumber
            if ($pageNum -ge 3) {
                $txt = $p.Range.Text.Trim()
                $align = $p.Format.Alignment
                # Headings are centered
                if ($align -eq 1 -and $txt.Length -gt 5 -and $txt.Length -lt 120) {
                    if ($txt -match '^(ВВЕДЕНИЕ|ЗАКЛЮЧЕНИЕ|СПИСОК ИСПОЛЬЗОВАННЫХ ИСТОЧНИКОВ|ПРИЛОЖЕНИЕ|[1-4] [А-Я]|[1-4]\\.[1-4])') {
                        Write-Host ($pageNum.ToString() + ' | ' + $txt)
                    }
                }
            }
        }
    }
    $doc.Close([ref]$false)
} finally {
    $word.Quit([ref]$false)
}
"""

with open('scratch/test_clean_pages.ps1', 'w', encoding='utf-8-sig') as f:
    f.write(ps_code)

res = subprocess.run(['powershell', '-ExecutionPolicy', 'Bypass', '-File', 'scratch/test_clean_pages.ps1'], capture_output=True, text=True, encoding='utf-8', errors='replace')
print("CLEAN PAGES OUTPUT:")
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)
