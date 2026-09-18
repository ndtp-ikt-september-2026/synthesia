# -*- coding: utf-8 -*-
"""
Экспорт актуальной пояснительной записки в Markdown формат.
"""

import docx
import os

doc = docx.Document("ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx")

lines = []
lines.append("# ПОЯСНИТЕЛЬНАЯ ЗАПИСКА К ИССЛЕДОВАТЕЛЬСКОМУ ПРОЕКТУ\n")
lines.append("**Тема:** РАЗРАБОТКА МОДУЛЯ ПЕРСОНАЛИЗИРОВАННЫХ РЕКОМЕНДАЦИЙ НА ОСНОВЕ МАШИННОГО ОБУЧЕНИЯ ДЛЯ СПЕЦИАЛИЗИРОВАННОГО ИНТЕРНЕТ-МАГАЗИНА «SYNTHESIA»  \n")
lines.append("**Выполнил:** Забинский Артемий, учащийся УО «Национальный детский технопарк»  ")
lines.append("**Руководители проекта:** Сицко В. А., Крюкова Д. С., Парамонов А. И.  ")
lines.append("**Минск, 2026**\n\n---\n")

for p in doc.paragraphs:
    txt = p.text.strip()
    if not txt:
        continue
    
    # Check if section heading
    if any(txt.startswith(kw) for kw in ["ВВЕДЕНИЕ", "ЗАКЛЮЧЕНИЕ", "СПИСОК ИСПОЛЬЗОВАННЫХ ИСТОЧНИКОВ", "ПРИЛОЖЕНИЕ", "1 ТЕОРЕТИЧЕСКИЕ", "2 ПРОЕКТИРОВАНИЕ", "3 ПРОГРАММНАЯ", "4 ЭКСПЕРИМЕНТАЛЬНОЕ"]):
        lines.append(f"\n## {txt}\n")
    elif len(txt) > 3 and txt[0].isdigit() and txt[1] == '.' and (txt[3] == ' ' or txt[4] == ' '):
        lines.append(f"\n### {txt}\n")
    elif txt.startswith("Таблица"):
        lines.append(f"\n**{txt}**\n")
    elif txt.startswith("Рисунок"):
        lines.append(f"\n*[{txt}]*\n")
    else:
        lines.append(f"{txt}\n")

md_content = "\n".join(lines)

for f in ["ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.md", "ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SOUNDNET.md"]:
    with open(f, "w", encoding="utf-8") as fp:
        fp.write(md_content)
    print(f"Updated {f}")
