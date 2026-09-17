# -*- coding: utf-8 -*-
"""
Главный сборочный скрипт генерации пояснительной записки Synthesia
по ГОСТам Республики Беларусь.
"""

import os
import sys
import shutil
from docx import Document

# Добавляем путь к scratch в sys.path
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

def main():
    print("=== НАЧАЛО ГЕНЕРАЦИИ ПОЯСНИТЕЛЬНОЙ ЗАПИСКИ SYNTHESIA ===")
    
    doc = Document()
    
    print("[1/10] Настройка стилей документа, полей и колонтитулов...")
    setup_document_styles(doc)
    
    print("[2/10] Формирование титульного листа...")
    build_title_page(doc)
    
    print("[3/10] Формирование содержания...")
    build_toc(doc)
    
    print("[4/10] Формирование введения...")
    build_intro(doc)
    
    print("[5/10] Формирование Главы 1 (Теоретическая часть)...")
    build_chapter_1(doc)
    
    print("[6/10] Формирование Главы 2 (Проектирование и архитектура)...")
    build_chapter_2(doc)
    
    print("[7/10] Формирование Главы 3 (Практическая реализация)...")
    build_chapter_3(doc)
    
    print("[8/10] Формирование Главы 4 (Тестирование и оптимизация)...")
    build_chapter_4(doc)
    
    print("[9/10] Формирование заключения и списка источников...")
    build_conclusion(doc)
    build_bibliography(doc)
    
    print("[10/10] Формирование приложений А, Б, В, Г с листингами кода...")
    build_appendices(doc)
    
    # Сохранение итогового DOCX файла
    output_docx_project = r"d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx"
    output_docx_project_alt = r"d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA_ГОТОВО.docx"
    output_docx_soundnet = r"d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SOUNDNET.docx"
    output_docx_downloads = r"C:\Users\zabazaba\Downloads\AyuGram Desktop\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx"
    output_docx_downloads_alt = r"C:\Users\zabazaba\Downloads\AyuGram Desktop\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA_ГОТОВО.docx"
    
    saved_file = None
    try:
        print(f"Сохранение документа в проект: {output_docx_project} ...")
        doc.save(output_docx_project)
        saved_file = output_docx_project
        print("Успешно сохранено в ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx!")
    except PermissionError:
        print(f"Предупреждение: {output_docx_project} заблокирован Word! Сохранение в альтернативный файл: {output_docx_project_alt} ...")
        doc.save(output_docx_project_alt)
        saved_file = output_docx_project_alt
        print(f"Успешно сохранено в {output_docx_project_alt}!")
    except Exception as e:
        print(f"Ошибка сохранения {output_docx_project}: {e}")

    try:
        doc.save(output_docx_soundnet)
    except Exception:
        pass
    
    if saved_file:
        try:
            downloads_dir = os.path.dirname(output_docx_downloads)
            if os.path.exists(downloads_dir):
                try:
                    shutil.copyfile(saved_file, output_docx_downloads)
                    print("Успешно скопировано в Downloads (ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx)!")
                except PermissionError:
                    shutil.copyfile(saved_file, output_docx_downloads_alt)
                    print("Downloads файл заблокирован, скопировано в ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA_ГОТОВО.docx!")
        except Exception as e:
            print(f"Предупреждение при копировании в Downloads: {e}")

    # Экспорт полной версии в Markdown прямо из объекта doc
    export_markdown(doc, r"d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.md")
    try:
        shutil.copyfile(r"d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.md", r"d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SOUNDNET.md")
    except Exception:
        pass
    print("Генерация полностью завершена!")

def export_markdown(doc, target_md):
    print(f"Экспорт полного текста пояснительной записки в Markdown: {target_md} ...")
    from docx.text.paragraph import Paragraph
    from docx.table import Table

    md_lines = []
    in_code_block = False

    for child in doc._element.body:
        tag = child.tag.split('}')[-1]
        if tag == 'p':
            p = Paragraph(child, doc)
            text = p.text.strip()
            if not text:
                if in_code_block:
                    md_lines.append('\n```\n')
                    in_code_block = False
                continue
                
            is_code = False
            if p.runs and p.runs[0].font.name == 'Consolas':
                is_code = True
                
            if is_code:
                if not in_code_block:
                    lang = 'php' if any(kw in text for kw in ['<?php', 'class ', 'function ', '$this']) else 'python' if any(kw in text for kw in ['def ', 'import ', 'async ']) else ''
                    md_lines.append(f'```{lang}')
                    in_code_block = True
                md_lines.append(text)
                continue
            else:
                if in_code_block:
                    md_lines.append('```\n')
                    in_code_block = False
                    
            is_h1 = False
            is_h2 = False
            if text.isupper() and (any(kw in text for kw in ['ВВЕДЕНИЕ', 'ЧАСТЬ', 'АРХИТЕКТУРА', 'РЕАЛИЗАЦИЯ', 'ТЕСТИРОВАНИЕ', 'ЗАКЛЮЧЕНИЕ', 'СПИСОК', 'ПРИЛОЖЕНИЕ', 'СОДЕРЖАНИЕ']) or len(text) < 60):
                is_h1 = True
            elif any(text.startswith(f'{i}.{j}') for i in range(1, 10) for j in range(1, 20)):
                is_h2 = True
                
            if is_h1:
                md_lines.append(f'\n# {text}\n')
            elif is_h2:
                md_lines.append(f'\n## {text}\n')
            elif text.startswith('Таблица ') or text.startswith('Рисунок '):
                md_lines.append(f'\n**{text}**\n')
            elif text.startswith('(') and text.endswith(')') and '=' in text:
                md_lines.append(f'\n$$\n{text}\n$$\n')
            else:
                if p.runs and p.runs[0].bold and len(p.runs[0].text) > 2 and len(p.runs) > 1:
                    prefix = p.runs[0].text
                    rest = ''.join(r.text for r in p.runs[1:])
                    md_lines.append(f'**{prefix}**{rest}\n')
                else:
                    md_lines.append(f'{text}\n')
                    
        elif tag == 'tbl':
            if in_code_block:
                md_lines.append('```\n')
                in_code_block = False
            tbl = Table(child, doc)
            headers = [cell.text.strip().replace('\n', ' ') for cell in tbl.rows[0].cells]
            md_lines.append('\n| ' + ' | '.join(headers) + ' |')
            md_lines.append('| ' + ' | '.join(['---'] * len(headers)) + ' |')
            for row in tbl.rows[1:]:
                row_data = [cell.text.strip().replace('\n', '<br>') for cell in row.cells]
                md_lines.append('| ' + ' | '.join(row_data) + ' |')
            md_lines.append('\n')

    if in_code_block:
        md_lines.append('```\n')

    with open(target_md, 'w', encoding='utf-8') as f:
        f.write('\n'.join(md_lines))
    print(f"Файл Markdown {target_md} успешно сформирован.")

if __name__ == '__main__':
    main()
