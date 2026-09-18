
$word = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $files = Get-ChildItem "d:\OSPanel\domains\synthesia" -Filter "*.docx"
    foreach ($f in $files) {
        $doc = $word.Documents.Open($f.FullName)
        $pages = $doc.ComputeStatistics(2)
        $words = $doc.ComputeStatistics(0)
        $chars = $doc.ComputeStatistics(3)
        $paras = $doc.ComputeStatistics(4)
        Write-Host "$($f.Name) | Pages: $pages | Words: $words | Chars: $chars | Paras: $paras"
        $doc.Close([ref]$false)
    }
} catch {
    Write-Host "Error: $($_.Exception.Message)"
} finally {
    if ($word -ne $null) {
        $word.Quit([ref]$false)
    }
}
