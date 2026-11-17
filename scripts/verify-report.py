"""Render de QA del PDF generado por la aplicación; no modifica el reporte."""
from pathlib import Path
import json
import sys
import pypdfium2 as pdfium
from pypdf import PdfReader
from PIL import Image, ImageDraw

root = Path(__file__).resolve().parents[1]
source = Path(sys.argv[1]) if len(sys.argv) > 1 else Path.home() / 'Downloads/amarilis-documental.pdf'
out = root / '.tools/report-qa'
out.mkdir(parents=True, exist_ok=True)
pdf = PdfReader(source)
text = '\n'.join(p.extract_text() for p in pdf.pages)
checks = {'pages':len(pdf.pages), 'records_present': all(f'R{i:02d}' in text for i in range(1,42)),
          'strict_lower_bound_present': '> 262,27' in text, 'educca_discrepancy_present':'93,14' in text and '93,16' in text,
          'provenance_present':'SHA-256' in text, 'periods_present':'Corte parcial 2024' in text and 'Primer semestre 2025' in text}
(root/'docs/09-evidencias/reporte-generado-verificacion.json').write_text(json.dumps(checks,indent=2),encoding='utf-8')
rendered=pdfium.PdfDocument(str(source))
for i in range(len(rendered)):
    rendered[i].render(scale=1.4).to_pil().save(out/f'page-{i+1}.png')
print(json.dumps(checks))
assert all(v for k,v in checks.items() if k!='pages')
