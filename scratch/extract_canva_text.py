import re
import json
import urllib.request
import sys

headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    'Accept-Language': 'ru-RU,ru;q=0.9,en-US;q=0.8',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'
}

urls = {
    "canva_1": 'https://www.canva.com/design/DAHN5r9xu3c/3w8Zon9ByTqiycfTrk7kmg/view',
    "canva_2": 'https://www.canva.com/design/DAGi3EXgNkg/4YtQUeaXcH4sKsoHEuWIpw/view',
    "canva_3": 'https://www.canva.com/design/DAHCQbgEdjE/kqs3tIa8Czxb0KfO7-lv-A/view'
}

for name, url in urls.items():
    try:
        req = urllib.request.Request(url, headers=headers)
        html = urllib.request.urlopen(req, timeout=15).read().decode('utf-8', errors='ignore')
        with open(f'scratch/{name}.html', 'w', encoding='utf-8') as f:
            f.write(html)
        
        # find JSON or text
        # In Canva view pages, strings are often in JSON blobs
        cyrillic = re.findall(r'[\u0400-\u04FF][\u0400-\u04FF\s\d\.,!?:;\-\(\)\"\'«»/%—–]{3,}', html)
        # filter out common noise
        cleaned = []
        seen = set()
        for c in cyrillic:
            item = c.strip()
            if len(item) > 3 and item not in seen:
                seen.add(item)
                cleaned.append(item)
                
        with open(f'scratch/{name}_text.txt', 'w', encoding='utf-8') as f:
            f.write(f"URL: {url}\n\n")
            for line in cleaned:
                f.write(line + "\n")
                
        print(f"{name}: successfully saved {len(cleaned)} snippets")
    except Exception as e:
        print(f"{name}: error {e}")

