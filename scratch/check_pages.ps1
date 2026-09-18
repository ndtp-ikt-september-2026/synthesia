$word = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    Write-Host "Word application successfully created."
    
    $path1 = "d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA_ГОТОВО.docx"
    $doc1 = $word.Documents.Open($path1)
    $pages1 = $doc1.ComputeStatistics(2)
    $words1 = $doc1.ComputeStatistics(0)
    Write-Host "ГОТОВО: Pages = $pages1, Words = $words1"
    $doc1.Close([ref]$false)

    $path2 = "d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx"
    $doc2 = $word.Documents.Open($path2)
    $pages2 = $doc2.ComputeStatistics(2)
    $words2 = $doc2.ComputeStatistics(0)
    Write-Host "SYNTHESIA: Pages = $pages2, Words = $words2"
    $doc2.Close([ref]$false)
}
catch {
    Write-Host "Error: $_"
}
finally {
    if ($word -ne $null) {
        $word.Quit([ref]$false)
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
    }
}
