
$OutputEncoding = [System.Text.Encoding]::UTF8
$word = $null
$outLines = @()
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $targets = @(
        'C:\Users\zabazaba\Downloads\AyuGram Desktop\РАЗРАБОТКА_МОДУЛЯ_СИНХРОНИЗАЦИИ_ТОВАРОВ_OPENCART_С_МАРКЕТПЛЕЙСОМ.docx',
        'C:\Users\zabazaba\Downloads\AyuGram Desktop\Чугунов_ПЗ.docx',
        'd:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx'
    )
    foreach ($t in $targets) {
        if (Test-Path $t) {
            $doc = $word.Documents.Open($t)
            $pages = $doc.ComputeStatistics(2)
            $name = [System.IO.Path]::GetFileName($t)
            $outLines += ($name + ' | Pages: ' + $pages)
            $doc.Close([ref]$false)
        }
    }
} catch {
    $outLines += ('Error: ' + $_.Exception.Message)
} finally {
    if ($word -ne $null) {
        $word.Quit([ref]$false)
    }
}
$outLines | Out-File -FilePath 'scratch/pages_report.txt' -Encoding utf8
