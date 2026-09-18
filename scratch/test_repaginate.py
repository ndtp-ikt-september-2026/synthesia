import subprocess
import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

ps_code = """
$word = New-Object -ComObject Word.Application
$word.Visible = $false
try {
    $doc = $word.Documents.Open('d:\\OSPanel\\domains\\synthesia\\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx')
    $doc.Repaginate()
    for ($i = 1; $i -le $doc.Paragraphs.Count; $i++) {
        $p = $doc.Paragraphs.Item($i)
        $txt = $p.Range.Text.Trim()
        $align = $p.Format.Alignment
        if ($txt.Length -gt 2 -and $txt.Length -lt 90) {
            if ($txt -match '^(ВВЕДЕНИЕ|ЗАКЛЮЧЕНИЕ|СПИСОК ИСПОЛЬЗОВАННЫХ ИСТОЧНИКОВ|ПРИЛОЖЕНИЕ|[1-4] [А-Я]|[1-4]\\.[1-4])') {
                $pageNum = $p.Range.Information(3) # wdActiveEndPageNumber
                Write-Host ($pageNum.ToString() + ' | ' + $txt)
            }
        }
    }
    $doc.Close([ref]$false)
} finally {
    $word.Quit([ref]$false)
}
"""

with open('scratch/test_repaginate.ps1', 'w', encoding='utf-8-sig') as f:
    f.write(ps_code)

res = subprocess.run(['powershell', '-ExecutionPolicy', 'Bypass', '-File', 'scratch/test_repaginate.ps1'], capture_output=True, text=True, encoding='utf-8', errors='replace')
print("STDOUT:")
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)
