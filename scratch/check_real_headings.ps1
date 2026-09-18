
$word = New-Object -ComObject Word.Application
$word.Visible = $false
try {
    $doc = $word.Documents.Open("d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx")
    for ($i = 1; $i -le $doc.Paragraphs.Count; $i++) {
        $p = $doc.Paragraphs.Item($i)
        $txt = $p.Range.Text.Trim()
        $pageNum = $p.Range.Information(3)
        # Skip TOC lines (they have tabs `t`)
        if ($txt.Contains("`t")) {
            continue
        }
        if ($txt.Length -gt 2 -and $txt.Length -lt 120) {
            if ($txt -match "^(ВВЕДЕНИЕ|ЗАКЛЮЧЕНИЕ|СПИСОК ИСПОЛЬЗОВАННЫХ ИСТОЧНИКОВ|ПРИЛОЖЕНИЕ|[1-4] [А-Я]|[1-4]\.[1-4])") {
                Write-Host "REAL_HEADING|$pageNum|$txt"
            }
        }
    }
    $doc.Close([ref]$false)
} catch {
    Write-Host "COM_ERROR:$($_.Exception.Message)"
} finally {
    $word.Quit([ref]$false)
}
