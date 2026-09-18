# -*- coding: utf-8 -*-
import sys
import re
import json

sys.stdout.reconfigure(encoding='utf-8')

def parse_canva(filepath, out_filepath):
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        html = f.read()

    # Find the big JSON block
    # In Canva viewer, the data structure is usually in a JSON object or string
    # Let's extract all string arrays like "A":["..."]
    
    # Let's find pages/slides. In Canva, pages often have IDs or are in an array
    # Look for "pages" or page elements
    with open(out_filepath, 'w', encoding='utf-8') as out:
        out.write(f"=== PARSED FROM {filepath} ===\n\n")
        
        # Extract all text blocks {"A":[...]} inside "C":{"A":[...]}
        matches = re.findall(r'\"A\":(\[[^\]]+\])', html)
        slide_idx = 1
        for m in matches:
            try:
                # parse json array
                arr = json.loads(m)
                if isinstance(arr, list) and any(isinstance(x, str) and re.search(r'[\u0400-\u04FF]', x) for x in arr):
                    text_joined = "".join(arr).replace("\\n", "\n").replace("\r", "")
                    lines = [l.strip() for l in text_joined.split("\n") if l.strip()]
                    if lines:
                        out.write(f"--- BLOCK ---\n")
                        for l in lines:
                            out.write(f"{l}\n")
                        out.write("\n")
            except:
                pass

if __name__ == '__main__':
    parse_canva('scratch/canva_2.html', 'scratch/canva_2_blocks.txt')
    parse_canva('scratch/canva_1.html', 'scratch/canva_1_blocks.txt')
    print("Done parsing")
