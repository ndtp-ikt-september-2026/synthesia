
$word = New-Object -ComObject Word.Application
$word.Visible = $false
try {
    $doc = $word.Documents.Open("d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx")
    Write-Host "=== SYNTHESIA.docx Section Pages ==="
    foreach ($p in $doc.Paragraphs) {
        $txt = $p.Range.Text.Trim()
        if ($txt -match "^(ВВЕДЕНИЕ|СОДЕРЖАНИЕ|ЗАКЛЮЧЕНИЕ|СПИСОК ИСПОЛЬЗОВАННЫХ ИСТОЧНИКОВ|ПРИЛОЖЕНИЕ|[1-4] [А-Я])") {
            $pageNum = $p.Range.Information(3) # wdActiveEndPageNumber
            Write-Host "Page $pageNum : $txt"
        }
    }
    $doc.Close([ref]$false)

    $doc2 = $word.Documents.Open("d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA_ГОТОВО.docx")
    Write-Host "`n=== SYNTHESIA_ГОТОВО.docx Section Pages ==="
    foreach ($p in $doc2.Paragraphs) {
        $txt = $p.Range.Text.Trim()
        if ($txt -match "^(ВВЕДЕНИЕ|СОДЕРЖАНИЕ|ЗАКЛЮЧЕНИЕ|СПИСОК ИСПОЛЬЗОВАННЫХ ИСТОЧНИКОВ|ПРИЛОЖЕНИЕ|[1-4] [А-Я])") {
            $pageNum = $p.Range.Information(3) # wdActiveEndPageNumber
            Write-Host "Page $pageNum : $txt"
        }
    }
    $doc2.Close([ref]$false)
} catch {
    Write-Host "Error: $($_.Exception.Message)"
} finally {
    $word.Quit([ref]$false)
}
