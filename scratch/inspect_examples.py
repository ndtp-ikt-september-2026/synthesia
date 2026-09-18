import docx
import sys

sys.stdout.reconfigure(encoding='utf-8')

def inspect_example(path):
    print("\n" + "="*80)
    print(f"DETAILED ANALYSIS OF: {path}")
    print("="*80)
    doc = docx.Document(path)
    
    # 1. Title page paragraphs (first 20)
    print("\n--- 1. TITLE PAGE (first 25 paragraphs) ---")
    for i in range(min(25, len(doc.paragraphs))):
        p = doc.paragraphs[i]
        txt = p.text.strip()
        if txt:
            runs_info = [(r.text.replace('\n', '\\n'), r.font.name, r.font.size.pt if r.font.size else None, r.bold, r.italic) for r in p.runs[:5]]
            print(f"P#{i:2d} [align={p.alignment}, indent={p.paragraph_format.first_line_indent.mm if p.paragraph_format.first_line_indent else 0:.1f}mm, sp_bef={p.paragraph_format.space_before.pt if p.paragraph_format.space_before else 0}, sp_aft={p.paragraph_format.space_after.pt if p.paragraph_format.space_after else 0}]:")
            print(f"     text='{txt[:90]}'")
            print(f"     runs={runs_info}")
            
    # Tables on title page or first table
    if doc.tables:
        print(f"\n--- TABLES (Total {len(doc.tables)}) ---")
        t0 = doc.tables[0]
        print(f"Table 0: {len(t0.rows)} rows x {len(t0.columns)} cols")
        for r_i, row in enumerate(t0.rows[:3]):
            for c_i, cell in enumerate(row.cells):
                txt = cell.text.strip().replace('\n', ' ')
                p = cell.paragraphs[0] if cell.paragraphs else None
                runs_info = [(r.text.replace('\n', '\\n'), r.font.name, r.font.size.pt if r.font.size else None, r.bold) for r in p.runs[:3]] if p and p.runs else []
                print(f"  Cell ({r_i}, {c_i}): align={p.alignment if p else None}, text='{txt[:70]}', runs={runs_info}")

    # 2. Check headings
    print("\n--- 2. HEADINGS IN DOCUMENT ---")
    major_kw = ["СОДЕРЖАНИЕ", "ВВЕДЕНИЕ", "ЗАКЛЮЧЕНИЕ", "СПИСОК", "ПРИЛОЖЕНИЕ"]
    headings_found = []
    for i, p in enumerate(doc.paragraphs):
        txt = p.text.strip()
        if not txt:
            continue
        
        is_major = any(txt.startswith(kw) for kw in major_kw) or (len(txt) > 2 and txt[0].isdigit() and (txt[1] == ' ' or (len(txt) > 2 and txt[2] == ' ')) and txt.isupper())
        is_sub = len(txt) > 3 and txt[0].isdigit() and '.' in txt[:4] and not is_major
        
        if is_major or is_sub:
            pb = p.paragraph_format.page_break_before or "type=\"page\"" in p._element.xml
            if i > 0 and "type=\"page\"" in doc.paragraphs[i-1]._element.xml:
                pb = True
            runs_info = [(r.font.name, r.font.size.pt if r.font.size else None, r.bold, r.italic) for r in p.runs[:3]]
            headings_found.append((i, "H1" if is_major else "H2", p.alignment, p.paragraph_format.first_line_indent.mm if p.paragraph_format.first_line_indent else 0, pb, txt[:70], runs_info))

    for h in headings_found[:35]:
        print(f"P#{h[0]:3d} [{h[1]}] align={h[2]} indent={h[3]:.1f}mm pb={h[4]}: '{h[5]}' runs={h[6]}")

    # 3. Typography & formatting of regular paragraphs
    print("\n--- 3. REGULAR PARAGRAPH SAMPLES ---")
    count = 0
    for i, p in enumerate(doc.paragraphs):
        txt = p.text.strip()
        if len(txt) > 100:
            indent = p.paragraph_format.first_line_indent.mm if p.paragraph_format.first_line_indent else 0
            ls = p.paragraph_format.line_spacing
            sb = p.paragraph_format.space_before.pt if p.paragraph_format.space_before else 0
            sa = p.paragraph_format.space_after.pt if p.paragraph_format.space_after else 0
            runs_info = [(r.font.name, r.font.size.pt if r.font.size else None) for r in p.runs[:2]]
            print(f"P#{i:3d} align={p.alignment} indent={indent:.2f}mm ls={ls} sb={sb} sa={sa} runs={runs_info}:")
            print(f"     '{txt[:90]}...'")
            count += 1
            if count >= 4:
                break

    # 4. Figures & Captions
    print("\n--- 4. FIGURES AND CAPTIONS ---")
    for i, p in enumerate(doc.paragraphs):
        txt = p.text.strip()
        if any(txt.startswith(kw) for kw in ["Рисунок", "Рис.", "рисунок"]):
            indent = p.paragraph_format.first_line_indent.mm if p.paragraph_format.first_line_indent else 0
            runs_info = [(r.font.name, r.font.size.pt if r.font.size else None, r.bold, r.italic) for r in p.runs[:3]]
            print(f"P#{i:3d} align={p.alignment} indent={indent:.1f}mm: '{txt}' runs={runs_info}")
            # check previous paragraph for picture
            if i > 0:
                prev_p = doc.paragraphs[i-1]
                has_pic = "w:drawing" in prev_p._element.xml or "pic:pic" in prev_p._element.xml
                print(f"     prev P#{i-1} has picture: {has_pic}, align={prev_p.alignment}")

    # 5. Tables and Captions
    print("\n--- 5. TABLES AND CAPTIONS ---")
    for i, p in enumerate(doc.paragraphs):
        txt = p.text.strip()
        if txt.startswith("Таблица") or txt.startswith("Табл."):
            indent = p.paragraph_format.first_line_indent.mm if p.paragraph_format.first_line_indent else 0
            runs_info = [(r.font.name, r.font.size.pt if r.font.size else None, r.bold, r.italic) for r in p.runs[:3]]
            print(f"P#{i:3d} align={p.alignment} indent={indent:.1f}mm: '{txt}' runs={runs_info}")

    for t_idx, t in enumerate(doc.tables[1:4]):
        row0 = [c.text.strip().replace('\n', ' ') for c in t.rows[0].cells]
        c0_p = t.rows[0].cells[0].paragraphs[0] if t.rows[0].cells[0].paragraphs else None
        c0_fonts = [(r.font.name, r.font.size.pt if r.font.size else None, r.bold) for r in c0_p.runs[:2]] if c0_p else []
        print(f"  Table {t_idx+1}: {len(t.rows)} rows x {len(t.columns)} cols | hdr={row0[:3]} | hdr_font={c0_fonts}")

    # 6. Footers
    sec = doc.sections[0]
    print("\n--- 6. FOOTER ---")
    for p in sec.footer.paragraphs:
        print(f"Footer align={p.alignment}, text='{p.text}', xml_len={len(p._element.xml)}")
        runs_info = [(r.font.name, r.font.size.pt if r.font.size else None) for r in p.runs]
        print(f"  runs={runs_info}")

    # 7. Bibliography sample
    print("\n--- 7. BIBLIOGRAPHY SAMPLE ---")
    in_bib = False
    bib_count = 0
    for p in doc.paragraphs:
        txt = p.text.strip()
        if any(kw in txt for kw in ["СПИСОК ИСПОЛЬЗОВАННЫХ ИСТОЧНИКОВ", "СПИСОК ЛИТЕРАТУРЫ", "СПИСОК ИСТОЧНИКОВ"]):
            in_bib = True
            print(f"Bibliography heading: '{txt}'")
            continue
        if in_bib:
            if any(txt.startswith(kw) for kw in ["ПРИЛОЖЕНИЕ", "1 ", "2 "]):
                break
            if txt and (txt[0].isdigit() or txt.startswith("[")):
                indent = p.paragraph_format.first_line_indent.mm if p.paragraph_format.first_line_indent else 0
                runs_info = [(r.font.name, r.font.size.pt if r.font.size else None) for r in p.runs[:2]]
                print(f"  Item: indent={indent:.1f}mm, align={p.alignment}, text='{txt[:85]}', runs={runs_info}")
                bib_count += 1
                if bib_count >= 5:
                    break

inspect_example(r'C:\Users\zabazaba\Downloads\AyuGram Desktop\РАЗРАБОТКА_МОДУЛЯ_СИНХРОНИЗАЦИИ_ТОВАРОВ_OPENCART_С_МАРКЕТПЛЕЙСОМ.docx')
inspect_example(r'C:\Users\zabazaba\Downloads\AyuGram Desktop\Чугунов_ПЗ.docx')
