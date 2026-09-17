# -*- coding: utf-8 -*-
"""
Модуль стилей и типографики для оформления пояснительной записки
по ГОСТ 7.32-2001, ГОСТ 2.105-95 и СТП УО «Национальный детский технопарк».
"""

from docx.shared import Pt, Mm, RGBColor, Inches
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import qn, nsdecls

def setup_document_styles(doc):
    # Настройка параметров страницы (А4, поля: левое 30 мм, правое 10 мм, верхнее 20 мм, нижнее 20 мм)
    section = doc.sections[0]
    section.page_width = Mm(210)
    section.page_height = Mm(297)
    section.left_margin = Mm(30)
    section.right_margin = Mm(10)
    section.top_margin = Mm(20)
    section.bottom_margin = Mm(20)
    section.header_distance = Mm(10)
    section.footer_distance = Mm(10)
    
    # Настройка стиля Normal
    style = doc.styles['Normal']
    font = style.font
    font.name = 'Times New Roman'
    font.size = Pt(14)
    font.color.rgb = RGBColor(0, 0, 0)
    style.paragraph_format.line_spacing = 1.0
    style.paragraph_format.space_after = Pt(0)
    style.paragraph_format.space_before = Pt(0)
    style.paragraph_format.first_line_indent = Mm(12.5)
    style.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    
    # Для обеспечения шрифта Times New Roman в Word для кириллицы
    rPr = style.element.get_or_add_rPr()
    rFonts = OxmlElement('w:rFonts')
    rFonts.set(qn('w:ascii'), 'Times New Roman')
    rFonts.set(qn('w:hAnsi'), 'Times New Roman')
    rFonts.set(qn('w:cs'), 'Times New Roman')
    rFonts.set(qn('w:eastAsia'), 'Times New Roman')
    rPr.append(rFonts)
    
    # Нумерация страниц в колонтитуле (снизу по центру, на 1 странице нет)
    section.different_first_page_header_footer = True
    footer = section.footer
    p_foot = footer.paragraphs[0]
    p_foot.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_foot.paragraph_format.space_before = Pt(0)
    p_foot.paragraph_format.space_after = Pt(0)
    
    r_foot = p_foot.add_run()
    r_foot.font.name = 'Times New Roman'
    r_foot.font.size = Pt(12)
    r_foot.font.color.rgb = RGBColor(0, 0, 0)
    
    fldChar1 = OxmlElement('w:fldChar')
    fldChar1.set(qn('w:fldCharType'), 'begin')
    instrText = OxmlElement('w:instrText')
    instrText.set(qn('xml:space'), 'preserve')
    instrText.text = 'PAGE'
    fldChar2 = OxmlElement('w:fldChar')
    fldChar2.set(qn('w:fldCharType'), 'separate')
    fldChar3 = OxmlElement('w:fldChar')
    fldChar3.set(qn('w:fldCharType'), 'end')
    
    r_foot._r.append(fldChar1)
    r_foot._r.append(instrText)
    r_foot._r.append(fldChar2)
    r_foot._r.append(fldChar3)

def add_p(doc, text="", bold_prefix="", italic=False, space_before=0, space_after=0, align=WD_ALIGN_PARAGRAPH.JUSTIFY, first_indent=12.5):
    p = doc.add_paragraph()
    p.alignment = align
    p.paragraph_format.space_before = Pt(space_before)
    p.paragraph_format.space_after = Pt(space_after)
    p.paragraph_format.line_spacing = 1.0
    p.paragraph_format.first_line_indent = Mm(first_indent)
    
    if bold_prefix:
        rb = p.add_run(bold_prefix)
        rb.font.name = 'Times New Roman'
        rb.font.size = Pt(14)
        rb.font.bold = True
        rb.font.color.rgb = RGBColor(0, 0, 0)
        
    if text:
        rt = p.add_run(text)
        rt.font.name = 'Times New Roman'
        rt.font.size = Pt(14)
        if italic:
            rt.font.italic = True
        rt.font.color.rgb = RGBColor(0, 0, 0)
    return p

def add_h1(doc, title, page_break=True):
    p = doc.add_paragraph()
    if page_break:
        p.paragraph_format.page_break_before = True
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(16)
    p.paragraph_format.space_after = Pt(14)
    p.paragraph_format.line_spacing = 1.0
    p.paragraph_format.first_line_indent = Mm(0)
    
    r = p.add_run(title.upper())
    r.font.name = 'Times New Roman'
    r.font.size = Pt(14)
    r.font.bold = True
    r.font.color.rgb = RGBColor(0, 0, 0)
    return p

def add_h2(doc, title):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
    p.paragraph_format.space_before = Pt(14)
    p.paragraph_format.space_after = Pt(8)
    p.paragraph_format.line_spacing = 1.0
    p.paragraph_format.first_line_indent = Mm(12.5)
    
    r = p.add_run(title)
    r.font.name = 'Times New Roman'
    r.font.size = Pt(14)
    r.font.bold = True
    r.font.color.rgb = RGBColor(0, 0, 0)
    return p

def add_h3(doc, title):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
    p.paragraph_format.space_before = Pt(10)
    p.paragraph_format.space_after = Pt(6)
    p.paragraph_format.line_spacing = 1.0
    p.paragraph_format.first_line_indent = Mm(12.5)
    
    r = p.add_run(title)
    r.font.name = 'Times New Roman'
    r.font.size = Pt(14)
    r.font.bold = True
    r.font.italic = True
    r.font.color.rgb = RGBColor(0, 0, 0)
    return p

def add_formula(doc, formula_text, formula_num=""):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(8)
    p.paragraph_format.space_after = Pt(8)
    p.paragraph_format.line_spacing = 1.0
    p.paragraph_format.first_line_indent = Mm(0)
    
    r = p.add_run(formula_text)
    r.font.name = 'Times New Roman'
    r.font.size = Pt(14)
    r.font.italic = True
    r.font.color.rgb = RGBColor(0, 0, 0)
    
    if formula_num:
        r_space = p.add_run("   " * 6)
        r_num = p.add_run(f"({formula_num})")
        r_num.font.name = 'Times New Roman'
        r_num.font.size = Pt(14)
        r_num.font.italic = False
        r_num.font.color.rgb = RGBColor(0, 0, 0)
    return p

def set_cell_borders(cell, top="D3D3D3", bottom="D3D3D3", left="D3D3D3", right="D3D3D3"):
    tcPr = cell._tc.get_or_add_tcPr()
    tcBorders = parse_xml(
        f'<w:tcBorders {nsdecls("w")}>\n'
        f'  <w:top w:val="single" w:sz="4" w:space="0" w:color="{top}"/>\n'
        f'  <w:left w:val="single" w:sz="4" w:space="0" w:color="{left}"/>\n'
        f'  <w:bottom w:val="single" w:sz="4" w:space="0" w:color="{bottom}"/>\n'
        f'  <w:right w:val="single" w:sz="4" w:space="0" w:color="{right}"/>\n'
        f'</w:tcBorders>'
    )
    tcPr.append(tcBorders)

def set_cell_bg(cell, hex_color):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{hex_color}"/>')
    tcPr.append(shd)

def add_table(doc, headers, data, col_widths=None, title=""):
    if title:
        p_t = doc.add_paragraph()
        p_t.alignment = WD_ALIGN_PARAGRAPH.LEFT
        p_t.paragraph_format.space_before = Pt(12)
        p_t.paragraph_format.space_after = Pt(4)
        p_t.paragraph_format.line_spacing = 1.0
        p_t.paragraph_format.first_line_indent = Mm(12.5)
        r_t = p_t.add_run(title)
        r_t.font.name = 'Times New Roman'
        r_t.font.size = Pt(14)
        r_t.font.bold = True
        r_t.font.color.rgb = RGBColor(0, 0, 0)

    table = doc.add_table(rows=len(data) + 1, cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    # Table borders
    tblPr = table._tbl.tblPr
    borders = parse_xml(
        f'<w:tblBorders {nsdecls("w")}>\n'
        f'  <w:top w:val="single" w:sz="6" w:space="0" w:color="808080"/>\n'
        f'  <w:left w:val="single" w:sz="6" w:space="0" w:color="808080"/>\n'
        f'  <w:bottom w:val="single" w:sz="6" w:space="0" w:color="808080"/>\n'
        f'  <w:right w:val="single" w:sz="6" w:space="0" w:color="808080"/>\n'
        f'  <w:insideH w:val="single" w:sz="4" w:space="0" w:color="C0C0C0"/>\n'
        f'  <w:insideV w:val="single" w:sz="4" w:space="0" w:color="C0C0C0"/>\n'
        f'</w:tblBorders>'
    )
    tblPr.append(borders)
    
    # Header row
    hdr_cells = table.rows[0].cells
    for i, h in enumerate(headers):
        hdr_cells[i].text = ""
        p = hdr_cells[i].paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_before = Pt(4)
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.line_spacing = 1.0
        p.paragraph_format.first_line_indent = Mm(0)
        r = p.add_run(h)
        r.font.name = 'Times New Roman'
        r.font.size = Pt(11)
        r.font.bold = True
        set_cell_bg(hdr_cells[i], "F2F4F7")
        set_cell_margins(hdr_cells[i], top=100, bottom=100, left=140, right=140)
        
    # Data rows
    for r_idx, row in enumerate(data):
        row_cells = table.rows[r_idx + 1].cells
        for c_idx, val in enumerate(row):
            row_cells[c_idx].text = ""
            p = row_cells[c_idx].paragraphs[0]
            p.alignment = WD_ALIGN_PARAGRAPH.LEFT if c_idx > 0 else WD_ALIGN_PARAGRAPH.CENTER
            p.paragraph_format.space_before = Pt(3)
            p.paragraph_format.space_after = Pt(3)
            p.paragraph_format.line_spacing = 1.0
            p.paragraph_format.first_line_indent = Mm(0)
            r = p.add_run(str(val))
            r.font.name = 'Times New Roman'
            r.font.size = Pt(11)
            set_cell_margins(row_cells[c_idx], top=80, bottom=80, left=140, right=140)
            if r_idx % 2 == 1:
                set_cell_bg(row_cells[c_idx], "FAFAFA")
                
    if col_widths:
        for row in table.rows:
            for i, w in enumerate(col_widths):
                row.cells[i].width = Mm(w)
                
    # Отступ после таблицы
    p_after = doc.add_paragraph()
    p_after.paragraph_format.space_before = Pt(0)
    p_after.paragraph_format.space_after = Pt(6)
    p_after.paragraph_format.line_spacing = 1.0
    return table

def set_cell_margins(cell, top=100, bottom=100, left=150, right=150):
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = OxmlElement('w:tcMar')
    for m, val in [('top', top), ('bottom', bottom), ('left', left), ('right', right)]:
        node = OxmlElement(f'w:{m}')
        node.set(qn('w:w'), str(val))
        node.set(qn('w:type'), 'dxa')
        tcMar.append(node)
    tcPr.append(tcMar)
