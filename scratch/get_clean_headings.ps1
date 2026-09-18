
$word = New-Object -ComObject Word.Application
$word.Visible = $false
try {
    $doc = $word.Documents.Open("d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx")
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
