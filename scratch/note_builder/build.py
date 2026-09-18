# -*- coding: utf-8 -*-
"""
Главный сборочный скрипт генерации пояснительной записки Synthesia
по стандартам УО «Национальный детский технопарк» (Минск 2026).
Поддерживает двухпроходную сборку для точной синхронизации номеров страниц в Содержании.
"""

import os
import sys
import shutil
import subprocess
from docx import Document

# Добавляем путь к корню в sys.path
sys.path.insert(0, os.path.abspath('.'))

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')
if hasattr(sys.stderr, 'reconfigure'):
    sys.stderr.reconfigure(encoding='utf-8')

from scratch.note_builder.styles import setup_document_styles
from scratch.note_builder.content_intro import build_title_page, build_toc, build_intro
from scratch.note_builder.content_ch1 import build_chapter_1
from scratch.note_builder.content_ch2 import build_chapter_2
from scratch.note_builder.content_ch3 import build_chapter_3
from scratch.note_builder.content_ch4 import build_chapter_4
from scratch.note_builder.content_conclusion import build_conclusion, build_bibliography, build_appendices

def build_single_document(page_map=None):
    doc = Document()
    setup_document_styles(doc)
    build_title_page(doc)
    build_toc(doc, page_map)
    build_intro(doc)
    build_chapter_1(doc)
    build_chapter_2(doc)
    build_chapter_3(doc)
    build_chapter_4(doc)
    build_conclusion(doc)
    build_bibliography(doc)
    build_appendices(doc)
    return doc

def get_word_page_numbers(docx_path):
    ps_code = f"""
$OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$word = New-Object -ComObject Word.Application
$word.Visible = $false
try {{
    $doc = $word.Documents.Open("{docx_path}")
    $doc.Repaginate()
    for ($i = 1; $i -le $doc.Paragraphs.Count; $i++) {{
        $p = $doc.Paragraphs.Item($i)
        if ($p.Range.Tables.Count -eq 0) {{
            $pageNum = $p.Range.Information(3) # wdActiveEndPageNumber
            if ($pageNum -ge 3) {{
                $txt = $p.Range.Text.Trim()
                $align = $p.Format.Alignment
                # Centered section headings or subsection headings
                if ($align -eq 1 -and $txt.Length -gt 5 -and $txt.Length -lt 120) {{
                    if ($txt -match '^(ВВЕДЕНИЕ|ЗАКЛЮЧЕНИЕ|СПИСОК ИСПОЛЬЗОВАННЫХ ИСТОЧНИКОВ|ПРИЛОЖЕНИЕ|[1-4] [А-Я]|[1-4]\\.[1-4])') {{
                        Write-Host "HEADING_MATCH|$pageNum|$txt"
                    }}
                }}
            }}
        }}
    }}
    $doc.Close([ref]$false)
}} catch {{
    Write-Host "COM_ERROR:$($_.Exception.Message)"
}} finally {{
    $word.Quit([ref]$false)
}}
"""
    ps_file = "scratch/temp_get_pages.ps1"
    with open(ps_file, "w", encoding="utf-8-sig") as f:
        f.write(ps_code)
        
    res = subprocess.run(["powershell", "-ExecutionPolicy", "Bypass", "-File", ps_file], capture_output=True, text=True, encoding="utf-8", errors="replace")
    
    pages = {}
    for line in res.stdout.splitlines():
        if line.startswith("HEADING_MATCH|"):
            parts = line.split("|", 2)
            if len(parts) == 3:
                p_num = parts[1].strip()
                title = parts[2].strip()
                # Clean up title
                for key in [
                    "ВВЕДЕНИЕ",
                    "1 ТЕОРЕТИЧЕСКИЕ ОСНОВЫ ПОСТРОЕНИЯ РЕКОМЕНДАТЕЛЬНЫХ СИСТЕМ В МУЗЫКАЛЬНОЙ ИНДУСТРИИ",
                    "1.1 Анализ предметной области и специфика рекомендаций",
                    "1.2 Методы машинного обучения в задачах подбора",
                    "1.3 Сравнительный анализ существующих платформ",
                    "1.4 Обоснование архитектурных решений комплекса",
                    "2 ПРОЕКТИРОВАНИЕ МОДУЛЯ ПЕРСОНАЛИЗИРОВАННЫХ РЕКОМЕНДАЦИЙ",
                    "2.1 Общая структура и функциональная схема комплекса",
                    "2.2 Архитектура сбора данных synesthesia-parser",
                    "2.3 Модель данных и расширение схемы MySQL",
                    "2.4 Математическая модель центроидных векторов",
                    "3 ПРОГРАММНАЯ РЕАЛИЗАЦИЯ МОДУЛЯ И ИНТЕРНЕТ-МАГАЗИНА",
                    "3.1 Разработка подсистемы сбора данных",
                    "3.2 Сервис векторного кодирования и поиска FastAPI",
                    "3.3 Модуль структурированного треклиста OpenCart",
                    "3.4 Пользовательский интерфейс витрины «Synthesia»",
                    "3.5 Пакетный импорт данных и подсистема StopKran",
                    "4 ЭКСПЕРИМЕНТАЛЬНОЕ ИССЛЕДОВАНИЕ И ОЦЕНКА ЭФФЕКТИВНОСТИ",
                    "4.1 Методика и результаты автоматизированных тестов",
                    "4.2 Исследование времени отклика и масштабируемости",
                    "4.3 Надежность механизмов отказоустойчивости",
                    "4.4 Практическая апробация в магазине «Synthesia»",
                    "ЗАКЛЮЧЕНИЕ",
                    "СПИСОК ИСПОЛЬЗОВАННЫХ ИСТОЧНИКОВ",
                    "ПРИЛОЖЕНИЕ А"
                ]:
                    if title.startswith(key) or key.startswith(title[:30]):
                        if key == "ПРИЛОЖЕНИЕ А":
                            pages["ПРИЛОЖЕНИЕ А. Спецификация программных компонентов"] = p_num
                        else:
                            pages[key] = p_num
                        break
    return pages

def main():
    print("=== НАЧАЛО СБОРКИ ПОЯСНИТЕЛЬНОЙ ЗАПИСКИ SYNTHESIA ===")
    
    # 1. Генерация диаграмм
    print("[1/4] Проверка графических схем...")
    from scratch.generate_diagrams import generate_arch_diagram, generate_db_diagram, generate_perf_chart
    generate_arch_diagram(r"scratch\fig_arch.png")
    generate_db_diagram(r"scratch\fig_db.png")
    generate_perf_chart(r"scratch\fig_perf.png")

    target_docx = r"d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx"
    target_ready = r"d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA_ГОТОВО.docx"
    
    # 2. Проход 1: Первичная сборка
    print("[2/4] Проход 1: Сборка структуры документа...")
    doc1 = build_single_document(page_map=None)
    doc1.save(target_docx)
    print(f"Документ сохранен в {target_docx}")

    # 3. Извлечение реальных номеров страниц через Word COM
    print("[3/4] Извлечение реальных номеров страниц через Microsoft Word...")
    real_pages = get_word_page_numbers(target_docx)
    print("Обнаруженные реальные страницы:")
    for k, v in real_pages.items():
        print(f"  {k} -> стр. {v}")

    # 4. Проход 2: Финальная сборка с точными номерами страниц
    print("[4/4] Проход 2: Финальная сборка с точным оглавлением...")
    final_doc = build_single_document(page_map=real_pages)
    final_doc.save(target_docx)
    final_doc.save(target_ready)
    print(f"Успешно сгенерировано:\n  -> {target_docx}\n  -> {target_ready}")

    # Копирование в Downloads
    dl_dir = r"C:\Users\zabazaba\Downloads\AyuGram Desktop"
    if os.path.exists(dl_dir):
        try:
            shutil.copyfile(target_ready, os.path.join(dl_dir, "ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA_ГОТОВО.docx"))
            print(f"Скопировано в {dl_dir}")
        except Exception as e:
            print("Ошибка копирования в Downloads:", e)

if __name__ == "__main__":
    main()
