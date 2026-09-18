import urllib.request
import re
import json

headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    'Accept-Language': 'ru-RU,ru;q=0.9,en-US;q=0.8',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'
}

urls = [
    'https://www.canva.com/design/DAHN5r9xu3c/3w8Zon9ByTqiycfTrk7kmg/view',
    'https://www.canva.com/design/DAGi3EXgNkg/4YtQUeaXcH4sKsoHEuWIpw/view',
    'https://www.canva.com/design/DAHCQbgEdjE/kqs3tIa8Czxb0KfO7-lv-A/view'
]

out = []
for url in urls:
    try:
        req = urllib.request.Request(url, headers=headers)
        html = urllib.request.urlopen(req, timeout=10).read().decode('utf-8', errors='ignore')
        title = re.search(r'<title>(.*?)</title>', html)
        og_title = re.search(r'property="og:title"\s+content="([^"]*)"', html) or re.search(r'content="([^"]*)"\s+property="og:title"', html)
        og_desc = re.search(r'property="og:description"\s+content="([^"]*)"', html) or re.search(r'content="([^"]*)"\s+property="og:description"', html)
        
        info = {
            'url': url,
            'title': title.group(1) if title else None,
            'og_title': og_title.group(1) if og_title else None,
            'og_desc': og_desc.group(1) if og_desc else None,
        }
        
        # look for text strings or slide titles
        # find patterns in html
        matches = re.findall(r'"text":\s*"([^"]{2,100})"', html)
        info['texts'] = matches[:50]
        
        # also find any other text in json blobs
        doc_titles = re.findall(r'"title":\s*"([^"]+)"', html)
        info['titles'] = doc_titles[:20]
        
        out.append(info)
    except Exception as e:
        out.append({'url': url, 'error': str(e)})

with open('scratch/canva_dump.json', 'w', encoding='utf-8') as f:
    json.dump(out, f, ensure_ascii=False, indent=2)

print("Done! Check scratch/canva_dump.json")
