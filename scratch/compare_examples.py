import docx
import sys

sys.stdout.reconfigure(encoding='utf-8')

for name, path in [('EMALL', r'C:\Users\zabazaba\Downloads\AyuGram Desktop\РАЗРАБОТКА_МОДУЛЯ_СИНХРОНИЗАЦИИ_ТОВАРОВ_OPENCART_С_МАРКЕТПЛЕЙСОМ.docx'),
                   ('CHUGUNOV', r'C:\Users\zabazaba\Downloads\AyuGram Desktop\Чугунов_ПЗ.docx')]:
    doc = docx.Document(path)
    print("="*30, name, "="*30)
    print("--- TITLE PARAGRAPHS 15..27 ---")
    for i in range(14, min(28, len(doc.paragraphs))):
        p = doc.paragraphs[i]
        t = p.text.strip()
        pf = p.paragraph_format
        print(f"P{i}: align={p.alignment} left_indent={pf.left_indent.mm if pf.left_indent else 0:.1f}mm right_indent={pf.right_indent.mm if pf.right_indent else 0:.1f}mm first_line={pf.first_line_indent.mm if pf.first_line_indent else 0:.1f}mm : '{t}'")

    print("\n--- TOC STRUCTURE ---")
    in_toc = False
    for i, p in enumerate(doc.paragraphs):
        t = p.text.strip()
        if t == "СОДЕРЖАНИЕ":
            in_toc = True
            print(f"TOC Heading: P{i}, align={p.alignment}, runs={[(r.font.name, r.font.size.pt if r.font.size else None, r.bold) for r in p.runs]}")
            continue
        if in_toc:
            if t == "ВВЕДЕНИЕ":
                break
            if t:
                pf = p.paragraph_format
                tabs = [(t_item.position.mm, t_item.alignment, t_item.leader) for t_item in pf.tab_stops]
                print(f"TOC line: indent={pf.first_line_indent.mm if pf.first_line_indent else 0:.1f}mm, left_indent={pf.left_indent.mm if pf.left_indent else 0:.1f}mm, tabs={tabs}, text='{t[:60]}'")

    print("\n--- FIRST 3 HEADINGS ---")
    for i, p in enumerate(doc.paragraphs):
        t = p.text.strip()
        if t in ["ВВЕДЕНИЕ", "ЗАКЛЮЧЕНИЕ"] or (len(t) > 2 and t[0].isdigit() and (' ' in t[:4] or '.' in t[:4])):
            pf = p.paragraph_format
            pb = pf.page_break_before or "type=\"page\"" in p._element.xml
            print(f"Heading P{i}: align={p.alignment}, pb={pb}, sb={pf.space_before.pt if pf.space_before else 0}, sa={pf.space_after.pt if pf.space_after else 0}, text='{t[:50]}'")

    print("\n--- FIGURES SAMPLES ---")
    for i, p in enumerate(doc.paragraphs):
        t = p.text.strip()
        if t.startswith("Рисунок") or t.startswith("Рис."):
            pf = p.paragraph_format
            runs = [(r.font.name, r.font.size.pt if r.font.size else None, r.bold, r.italic) for r in p.runs]
            print(f"Figure caption P{i}: align={p.alignment}, indent={pf.first_line_indent.mm if pf.first_line_indent else 0:.1f}mm, text='{t}', runs={runs}")

    print("\n--- TABLES SAMPLES ---")
    for i, p in enumerate(doc.paragraphs):
        t = p.text.strip()
        if t.startswith("Таблица") or t.startswith("Табл."):
            pf = p.paragraph_format
            runs = [(r.font.name, r.font.size.pt if r.font.size else None, r.bold, r.italic) for r in p.runs]
            print(f"Table caption P{i}: align={p.alignment}, indent={pf.first_line_indent.mm if pf.first_line_indent else 0:.1f}mm, text='{t}', runs={runs}")

    print("\n--- BIBLIOGRAPHY SAMPLES ---")
    in_b = False
    for p in doc.paragraphs:
        t = p.text.strip()
        if "СПИСОК" in t:
            in_b = True
            print(f"Bib Heading: align={p.alignment}, text='{t}'")
            continue
        if in_b:
            if "ПРИЛОЖЕНИЕ" in t: break
            if t and t[0].isdigit():
                pf = p.paragraph_format
                runs = [(r.font.name, r.font.size.pt if r.font.size else None, r.bold) for r in p.runs[:2]]
                print(f"Bib Item: align={p.alignment}, indent={pf.first_line_indent.mm if pf.first_line_indent else 0:.1f}mm, text='{t[:75]}', runs={runs}")
