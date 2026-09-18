
 = New-Object -ComObject Word.Application
.Visible = False
try {
     = .Documents.Open('C:\Users\zabazaba\Downloads\AyuGram Desktop\Чугунов_ПЗ.docx')
    Write-Host 'Chugunov pages:' .ComputeStatistics(2)
    .Close([ref]False)
     = .Documents.Open('C:\Users\zabazaba\Downloads\AyuGram Desktop\РАЗРАБОТКА_МОДУЛЯ_СИНХРОНИЗАЦИИ_ТОВАРОВ_OPENCART_С_МАРКЕТПЛЕЙСОМ.docx')
    Write-Host 'Emall pages:' .ComputeStatistics(2)
    .Close([ref]False)
} finally {
    .Quit([ref]False)
}
