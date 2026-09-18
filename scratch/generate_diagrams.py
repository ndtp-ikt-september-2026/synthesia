# -*- coding: utf-8 -*-
"""
Генератор графических схем и диаграмм для пояснительной записки Synthesia
с использованием Pillow.
"""

import os
from PIL import Image, ImageDraw, ImageFont

def get_font(size=14, bold=False):
    # Try standard system fonts
    font_paths = [
        r"C:\Windows\Fonts\arialbd.ttf" if bold else r"C:\Windows\Fonts\arial.ttf",
        r"C:\Windows\Fonts\timesbd.ttf" if bold else r"C:\Windows\Fonts\times.ttf",
        r"C:\Windows\Fonts\calibrib.ttf" if bold else r"C:\Windows\Fonts\calibri.ttf",
    ]
    for p in font_paths:
        if os.path.exists(p):
            try:
                return ImageFont.truetype(p, size)
            except Exception:
                pass
    return ImageFont.load_default()

def draw_rounded_box(draw, xy, fill, outline, width=2, radius=8):
    x0, y0, x1, y1 = xy
    draw.rounded_rectangle([x0, y0, x1, y1], radius=radius, fill=fill, outline=outline, width=width)

def draw_arrow(draw, start, end, fill="#2C3E50", width=2, arrow_size=6):
    x0, y0 = start
    x1, y1 = end
    draw.line([x0, y0, x1, y1], fill=fill, width=width)
    # arrow head
    if x0 == x1: # vertical
        sign = 1 if y1 > y0 else -1
        draw.polygon([(x1, y1), (x1 - arrow_size, y1 - sign * arrow_size * 1.5), (x1 + arrow_size, y1 - sign * arrow_size * 1.5)], fill=fill)
    elif y0 == y1: # horizontal
        sign = 1 if x1 > x0 else -1
        draw.polygon([(x1, y1), (x1 - sign * arrow_size * 1.5, y1 - arrow_size), (x1 - sign * arrow_size * 1.5, y1 + arrow_size)], fill=fill)

def generate_arch_diagram(output_path):
    width = 1100
    height = 540
    img = Image.new("RGB", (width, height), "#FFFFFF")
    draw = ImageDraw.Draw(img)

    f_title = get_font(18, bold=True)
    f_box_h = get_font(15, bold=True)
    f_box_t = get_font(12, bold=False)
    f_badge = get_font(11, bold=True)

    # Outer border
    draw.rectangle([10, 10, width - 10, height - 10], outline="#E0E0E0", width=2)

    # Title
    draw.text((width // 2, 35), "Архитектурная схема программного комплекса «Synthesia»", fill="#1A252F", font=f_title, anchor="mm")

    # Layer 1: Client / Web Storefront
    draw_rounded_box(draw, [50, 80, 480, 200], fill="#F4F8FB", outline="#2980B9", width=2)
    draw.text((265, 105), "Витрина интернет-магазина (Клиентский уровень)", fill="#1B4F72", font=f_box_h, anchor="mm")
    draw.text((265, 135), "OpenCart 3 LiveStore Frontend • JS ES6 • CSS3 Dark Theme", fill="#2C3E50", font=f_box_t, anchor="mm")
    draw.text((265, 160), "Адаптивный каталог товаров • Интерактивный треклист • Модуль рекомендаций", fill="#566573", font=f_box_t, anchor="mm")

    # Layer 2: Ingestion & CLI
    draw_rounded_box(draw, [620, 80, 1050, 200], fill="#F9F5FB", outline="#8E44AD", width=2)
    draw.text((835, 105), "Подсистема сбора и импорта данных", fill="#512E5F", font=f_box_h, anchor="mm")
    draw.text((835, 135), "synesthesia-parser (Python 3.13) + cli/catalog_ingest.php", fill="#2C3E50", font=f_box_t, anchor="mm")
    draw.text((835, 160), "Парсинг pop-music.ru (curl_cffi) • Discogs API • Нормализация атрибутов", fill="#566573", font=f_box_t, anchor="mm")

    # Layer 3: Backend Core & DB
    draw_rounded_box(draw, [50, 270, 480, 500], fill="#F5EEF8", outline="#2C3E50", width=2)
    draw.text((265, 295), "Серверное транзакционное ядро", fill="#1A252F", font=f_box_h, anchor="mm")
    draw.text((265, 325), "PHP 7.4 / 8.x • OpenCart MVC-L Framework", fill="#2C3E50", font=f_box_t, anchor="mm")
    draw.text((265, 350), "Модули: soundnet_storefront, soundnet_tracklist", fill="#566573", font=f_box_t, anchor="mm")
    draw.text((265, 375), "Отказоустойчивость: StopKran (Watchdog & Recovery)", fill="#7B241C", font=f_box_t, anchor="mm")
    
    # Nested MySQL box
    draw_rounded_box(draw, [70, 410, 460, 485], fill="#FFFFFF", outline="#1F618D", width=1)
    draw.text((265, 430), "Реляционная СУБД MySQL (InnoDB)", fill="#1B4F72", font=f_box_h, anchor="mm")
    draw.text((265, 458), "Таблицы: oc_product, oc_product_tracklist, oc_product_vector_status", fill="#2C3E50", font=f_box_t, anchor="mm")

    # Layer 4: AI & Vector Recommendations
    draw_rounded_box(draw, [620, 270, 1050, 500], fill="#EAF2F8", outline="#2E86C1", width=2)
    draw.text((835, 295), "Микросервис машинного обучения (synesthesia-ai)", fill="#1B4F72", font=f_box_h, anchor="mm")
    draw.text((835, 325), "FastAPI • Python 3.13 • sentence-transformers", fill="#2C3E50", font=f_box_t, anchor="mm")
    draw.text((835, 350), "Модель paraphrase-multilingual-MiniLM-L12-v2 (dim=384)", fill="#2980B9", font=f_box_t, anchor="mm")
    draw.text((835, 375), "Расчет центроидов релизов • Косинусная метрика сходства", fill="#566573", font=f_box_t, anchor="mm")

    # Nested Qdrant & Redis boxes
    draw_rounded_box(draw, [640, 410, 825, 485], fill="#FFFFFF", outline="#D35400", width=1)
    draw.text((732, 432), "Qdrant Vector DB", fill="#B9770E", font=f_box_h, anchor="mm")
    draw.text((732, 458), "Графовый индекс HNSW (dim=384)", fill="#566573", font=f_box_t, anchor="mm")

    draw_rounded_box(draw, [845, 410, 1030, 485], fill="#FFFFFF", outline="#C0392B", width=1)
    draw.text((937, 432), "Redis In-Memory", fill="#922B21", font=f_box_h, anchor="mm")
    draw.text((937, 458), "Кэш ответов (t < 1.5 мс)", fill="#566573", font=f_box_t, anchor="mm")

    # Connectors
    draw_arrow(draw, (265, 200), (265, 270), fill="#2980B9", width=2) # Frontend -> Core
    draw_arrow(draw, (835, 200), (835, 270), fill="#8E44AD", width=2) # Parser -> AI / Ingest
    draw_arrow(draw, (620, 140), (480, 140), fill="#8E44AD", width=2) # Ingest -> Storefront/DB
    draw_arrow(draw, (480, 360), (620, 360), fill="#16A085", width=2) # Core <-> FastAPI
    draw_arrow(draw, (620, 380), (480, 380), fill="#16A085", width=2)

    img.save(output_path, "PNG")
    print(f"Generated: {output_path}")

def generate_db_diagram(output_path):
    width = 1100
    height = 500
    img = Image.new("RGB", (width, height), "#FFFFFF")
    draw = ImageDraw.Draw(img)

    f_title = get_font(18, bold=True)
    f_tbl_h = get_font(14, bold=True)
    f_tbl_t = get_font(12, bold=False)

    draw.rectangle([10, 10, width - 10, height - 10], outline="#E0E0E0", width=2)
    draw.text((width // 2, 35), "Логическая схема расширения реляционной базы данных MySQL", fill="#1A252F", font=f_title, anchor="mm")

    # Table 1: oc_product (Core)
    draw_rounded_box(draw, [40, 90, 340, 440], fill="#F8F9F9", outline="#2C3E50", width=2)
    draw_rounded_box(draw, [40, 90, 340, 125], fill="#34495E", outline="#2C3E50", width=2)
    draw.text((190, 108), "oc_product (Товары)", fill="#FFFFFF", font=f_tbl_h, anchor="mm")
    fields_p = [
        "product_id: INT [PK]",
        "model: VARCHAR(64) [SKU]",
        "quantity: INT",
        "price: DECIMAL(15,4)",
        "image: VARCHAR(255)",
        "status: TINYINT(1)",
        "date_added: DATETIME",
        "date_modified: DATETIME"
    ]
    y = 145
    for f in fields_p:
        draw.text((60, y), f, fill="#1A252F", font=f_tbl_t)
        y += 26

    # Table 2: oc_product_tracklist (NEW)
    draw_rounded_box(draw, [410, 90, 720, 440], fill="#F4F6F7", outline="#1E8449", width=2)
    draw_rounded_box(draw, [410, 90, 720, 125], fill="#27AE60", outline="#1E8449", width=2)
    draw.text((565, 108), "oc_product_tracklist (Треклисты)", fill="#FFFFFF", font=f_tbl_h, anchor="mm")
    fields_t = [
        "track_id: INT [PK, AutoInc]",
        "product_id: INT [FK -> oc_product]",
        "track_num: INT (Номер дорожки)",
        "title: VARCHAR(255) (Название)",
        "duration: VARCHAR(32) (Хронометраж)",
        "preview_file: VARCHAR(255)",
        "sort_order: INT",
        "status: TINYINT(1)",
        "date_added: DATETIME"
    ]
    y = 145
    for f in fields_t:
        draw.text((430, y), f, fill="#1A252F", font=f_tbl_t)
        y += 26

    # Table 3: oc_product_vector_status (NEW)
    draw_rounded_box(draw, [780, 90, 1060, 440], fill="#F4F8FB", outline="#2874A6", width=2)
    draw_rounded_box(draw, [780, 90, 1060, 125], fill="#2980B9", outline="#2874A6", width=2)
    draw.text((920, 108), "oc_product_vector_status (Векторы)", fill="#FFFFFF", font=f_tbl_h, anchor="mm")
    fields_v = [
        "product_id: INT [PK, FK -> oc_product]",
        "is_indexed: TINYINT(1) (Статус Qdrant)",
        "has_audio: TINYINT(1)",
        "audio_path: VARCHAR(255)",
        "content_hash: VARCHAR(32) [MD5]",
        "updated_at: TIMESTAMP"
    ]
    y = 145
    for f in fields_v:
        draw.text((800, y), f, fill="#1A252F", font=f_tbl_t)
        y += 26

    # Relationship arrows
    # oc_product -> oc_product_tracklist (1:N)
    draw_arrow(draw, (340, 175), (410, 175), fill="#27AE60", width=2)
    draw.text((375, 160), "1 : N", fill="#1E8449", font=get_font(12, bold=True), anchor="mm")

    # oc_product -> oc_product_vector_status (1:1)
    draw_arrow(draw, (340, 240), (780, 240), fill="#2980B9", width=2)
    draw.text((560, 225), "1 : 1 (Индекс векторов)", fill="#2874A6", font=get_font(12, bold=True), anchor="mm")

    img.save(output_path, "PNG")
    print(f"Generated: {output_path}")

def generate_perf_chart(output_path):
    width = 1000
    height = 500
    img = Image.new("RGB", (width, height), "#FFFFFF")
    draw = ImageDraw.Draw(img)

    f_title = get_font(18, bold=True)
    f_axis = get_font(13, bold=True)
    f_val = get_font(12, bold=True)
    f_legend = get_font(13, bold=False)

    draw.rectangle([10, 10, width - 10, height - 10], outline="#E0E0E0", width=2)
    draw.text((width // 2, 35), "Сравнительный график времени отклика рекомендательного сервиса", fill="#1A252F", font=f_title, anchor="mm")

    # Axes
    ox = 120
    oy = 400
    graph_w = 800
    graph_h = 300

    draw.line([ox, oy, ox + graph_w, oy], fill="#34495E", width=2) # X
    draw.line([ox, oy, ox, oy - graph_h], fill="#34495E", width=2) # Y

    # Y grid lines & labels (0, 10, 20, 30, 40, 50 ms)
    for ms in [0, 10, 20, 30, 40, 50]:
        y_pos = oy - int((ms / 50.0) * graph_h)
        draw.line([ox - 5, y_pos, ox + graph_w, y_pos], fill="#EAEDED" if ms > 0 else "#34495E", width=1)
        draw.text((ox - 15, y_pos), f"{ms} мс", fill="#5D6D7E", font=f_axis, anchor="rm")

    # Categories on X (1 000, 10 000, 50 000 векторов)
    cats = ["1 000 сущностей", "10 000 сущностей", "50 000 сущностей"]
    qdrant_vals = [14.8, 18.2, 26.4]
    redis_vals = [1.4, 1.6, 1.7]

    col_w = 55
    spacing = 240

    for idx, cat in enumerate(cats):
        cx = ox + 150 + idx * spacing
        # X label
        draw.text((cx, oy + 25), cat, fill="#1A252F", font=f_axis, anchor="mm")

        # Bar 1: Qdrant
        q_h = int((qdrant_vals[idx] / 50.0) * graph_h)
        draw_rounded_box(draw, [cx - col_w - 5, oy - q_h, cx - 5, oy], fill="#2980B9", outline="#1F618D", width=1, radius=4)
        draw.text((cx - col_w // 2 - 5, oy - q_h - 12), f"{qdrant_vals[idx]} мс", fill="#1F618D", font=f_val, anchor="mm")

        # Bar 2: Redis
        r_h = int((redis_vals[idx] / 50.0) * graph_h)
        draw_rounded_box(draw, [cx + 5, oy - r_h, cx + col_w + 5, oy], fill="#27AE60", outline="#1E8449", width=1, radius=4)
        draw.text((cx + col_w // 2 + 5, oy - r_h - 12), f"{redis_vals[idx]} мс", fill="#1E8449", font=f_val, anchor="mm")

    # Legend
    draw_rounded_box(draw, [width - 340, 60, width - 40, 120], fill="#F8F9F9", outline="#BDC3C7", width=1)
    # Qdrant box
    draw.rectangle([width - 325, 75, width - 305, 90], fill="#2980B9", outline="#1F618D")
    draw.text((width - 295, 82), "Qdrant (прямой k-NN поиск)", fill="#2C3E50", font=f_legend, anchor="lm")
    # Redis box
    draw.rectangle([width - 325, 95, width - 305, 110], fill="#27AE60", outline="#1E8449")
    draw.text((width - 295, 102), "Redis (кэшированный ответ)", fill="#2C3E50", font=f_legend, anchor="lm")

    img.save(output_path, "PNG")
    print(f"Generated: {output_path}")

if __name__ == "__main__":
    generate_arch_diagram(r"scratch\fig_arch.png")
    generate_db_diagram(r"scratch\fig_db.png")
    generate_perf_chart(r"scratch\fig_perf.png")
