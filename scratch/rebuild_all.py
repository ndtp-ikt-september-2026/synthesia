import os
import sys
import shutil

sys.path.insert(0, os.path.abspath('.'))

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

from scratch.note_builder.build import build_single_document

print("Building final document...")
doc = build_single_document()

paths = [
    r"d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx",
    r"d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA_ГОТОВО.docx",
    r"d:\OSPanel\domains\synthesia\ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SOUNDNET.docx",
]

for p in paths:
    try:
        doc.save(p)
        print(f"Successfully saved to: {p}")
    except Exception as e:
        print(f"Error saving {p}: {e}")

dl_dir = r"C:\Users\zabazaba\Downloads\AyuGram Desktop"
if os.path.exists(dl_dir):
    try:
        shutil.copyfile(paths[0], os.path.join(dl_dir, "ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA.docx"))
        shutil.copyfile(paths[1], os.path.join(dl_dir, "ПОЯСНИТЕЛЬНАЯ_ЗАПИСКА_SYNTHESIA_ГОТОВО.docx"))
        print(f"Copied to {dl_dir}")
    except Exception as e:
        print("Error copying to Downloads:", e)
