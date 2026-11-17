"""Inspección de solo lectura de los adjuntos; no es el importador Laravel."""
import hashlib
import json
from datetime import date, datetime
from pathlib import Path
from zipfile import ZipFile
from xml.etree import ElementTree as ET
from decimal import Decimal, getcontext
import re
from difflib import SequenceMatcher
import openpyxl
from pypdf import PdfReader

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'docs/09-evidencias'
DOWNLOADS = Path.home() / 'Downloads'
FILES = [
    'Fuentes_y_Calculos_Articulo_Amarilis (1).md',
    'Datos_Residuos_Amarilis_Analisis (1).xlsx',
    'Articulo_Cientifico_BI_Residuos_Amarilis_2026 (1).pdf',
    'Articulo_Cientifico_BI_Residuos_Amarilis_2026 (1).docx',
]

def serialize(value):
    if isinstance(value, (date, datetime)):
        return value.isoformat()
    raise TypeError(type(value).__name__)

def save(name, data):
    (OUT / name).write_text(json.dumps(data, ensure_ascii=False, indent=2, default=serialize), encoding='utf-8')

manifest = []
for name in FILES:
    path = DOWNLOADS / name
    manifest.append(dict(file=name, path=str(path), bytes=path.stat().st_size,
                         sha256=hashlib.sha256(path.read_bytes()).hexdigest()))
save('inventario-adjuntos.json', manifest)

pdf = PdfReader(DOWNLOADS / FILES[2])
(OUT / 'articulo-pdf.txt').write_text('\n\n'.join(f'--- PÁGINA {i} ---\n{p.extract_text()}' for i, p in enumerate(pdf.pages, 1)), encoding='utf-8')
with ZipFile(DOWNLOADS / FILES[3]) as z:
    document = ET.fromstring(z.read('word/document.xml'))
ns = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
paragraphs = [''.join(t.text or '' for t in p.findall('.//w:t', ns)) for p in document.findall('.//w:p', ns)]
(OUT / 'articulo-docx.txt').write_text('\n'.join(paragraphs), encoding='utf-8')

wb = openpyxl.load_workbook(DOWNLOADS / FILES[1], data_only=False)
cached = openpyxl.load_workbook(DOWNLOADS / FILES[1], data_only=True)
sheets = {}
for sheet in wb:
    rows = []
    for row in sheet:
        if not any(c.value is not None for c in row):
            continue
        rows.append({c.coordinate: dict(value=c.value, cached=cached[sheet.title][c.coordinate].value,
                                        format=c.number_format) for c in row if c.value is not None})
    sheets[sheet.title] = dict(max_row=sheet.max_row, max_column=sheet.max_column, rows=rows)
save('libro-extraido.json', sheets)

assert wb.sheetnames == ['Indicadores', 'Registros', 'Rutas2024', 'Procedencia', 'Observaciones']
records = {row[0]: row for row in wb['Registros'].iter_rows(min_row=2, values_only=True)}
assert set(records) == {f'R{i:02}' for i in range(1, 42)}
assert len(records) == 41
assert {row[3] for row in records.values()} == {'M24', 'M25', 'M26', 'E26'}
assert [row[0] for row in wb['Rutas2024'].iter_rows(min_row=2, values_only=True)] == list(range(1, 12))
assert {row[0] for row in wb['Observaciones'].iter_rows(min_row=2, values_only=True)} == {f'Q{i:02}' for i in range(1, 10)}
assert all(records[r][6] == 'Mayor que' for r in ['R35', 'R36'])
getcontext().prec = 40
def r(i):
    return Decimal(str(records[f'R{i:02}'][4]))
routes = [Decimal(str(row[2])) for row in wb['Rutas2024'].iter_rows(min_row=2, values_only=True)]
results = [sum(routes), (routes[0]+routes[7])/sum(routes), r(9)/r(8), r(8)-r(9),
           r(6)/r(5), r(12)/r(11), (r(12)/r(11)-r(13)/100)*100,
           r(22)/r(21), r(21)-r(22), r(17)+r(18), r(35)+r(36), r(20)/r(19),
           r(28)+r(29)+r(30), r(34)/r(33), r(40)/r(39), r(41)/r(39)]
checks = []
for row, result in enumerate(results, 6):
    cell = f'B{row}'
    expected = cached['Indicadores'][cell].value
    delta = abs(result - Decimal(str(expected)))
    ok = delta < Decimal('1e-10')
    checks.append(dict(cell=cell, formula=wb['Indicadores'][cell].value,
                       recomputed=str(result), cached=expected, absolute_difference=str(delta),
                       operator='>' if row == 16 else '=', passed=ok))
    assert ok, (cell, result, expected)
errors = []
for sheet in cached:
    errors.extend(f'{sheet.title}!{c.coordinate}: {c.value}' for row in sheet for c in row if c.data_type == 'e')
assert not errors, errors
save('verificacion-calculos.json', dict(method='Decimal independiente; comparación con caché, sin recalcular ni modificar Excel',
    tolerance='1e-10 por representación binaria del caché, no umbral de calidad municipal',
    records=41, route_rows=11, sources=7, observations=9, formula_errors_cached=errors, checks=checks))

# Contraste textual: se omiten números de página y espacios propios de maquetación.
pdf_text = '\n'.join(p.extract_text() for p in pdf.pages)
pdf_lines = [line for line in pdf_text.splitlines() if not re.fullmatch(r'\s*\d+\s*', line)]
normalize = lambda value: re.sub(r'\s+', '', value).replace('\u00ad', '')
a, b = normalize('\n'.join(pdf_lines)), normalize('\n'.join(paragraphs))
matcher = SequenceMatcher(None, a, b, autojunk=False)
differences = [dict(operation=tag, pdf=a[i:j], docx=b[k:l])
               for tag, i, j, k, l in matcher.get_opcodes() if tag != 'equal']
save('contraste-pdf-docx.json', dict(method='Texto OOXML frente a texto PDF sin espacios ni folios numéricos; imágenes no comparadas',
    pdf_pages=len(pdf.pages), docx_paragraphs=len(paragraphs), differences=differences))

# Render de lectura con PDFium, sin modificar el PDF original.
import pypdfium2 as pdfium
from PIL import Image, ImageDraw
rendered = pdfium.PdfDocument(str(DOWNLOADS / FILES[2]))
for start in range(0, len(rendered), 4):
    canvas = Image.new('RGB', (1260, 1780), 'white')
    draw = ImageDraw.Draw(canvas)
    for idx in range(start, min(start+4, len(rendered))):
        im = rendered[idx].render(scale=1.1).to_pil().convert('RGB')
        im.thumbnail((620, 855))
        x, y = (idx-start)%2*630, (idx-start)//2*890
        canvas.paste(im, (x, y+25))
        draw.text((x+5, y+5), f'Pagina {idx+1}', fill='black')
    canvas.save(OUT / f'lectura-pdf-{start+1:02}-{min(start+4,len(rendered)):02}.png')
print(json.dumps(dict(pages=len(pdf.pages), docx_paragraphs=len(paragraphs),
                      sheets={s.title: [s.max_row, s.max_column] for s in wb}), ensure_ascii=False))
