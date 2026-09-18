
$word = New-Object -ComObject Word.Application
$word.Visible = $false
try {
    $doc = $word.Documents.Open("d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx")
    $total_pages = $doc.ComputeStatistics(2)
    Write-Host "TOTAL_PAGES: $total_pages"
    $doc.Close([ref]$false)
} catch {
    Write-Host "COM_ERROR: $($_.Exception.Message)"
} finally {
    $word.Quit([ref]$false)
}
