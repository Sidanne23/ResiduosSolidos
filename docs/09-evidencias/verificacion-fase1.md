# Acta de verificación de Fase 1

Fecha de elaboración: 16 de noviembre de 2026, America/Lima. Estado: análisis y documentación elaborados. No se ha implementado la aplicación de dominio.

## Comprobaciones ejecutadas

| Comprobación | Resultado observado | Evidencia |
|---|---|---|
| Lectura de cuatro adjuntos | Archivos accesibles y SHA-256 calculado sobre cada binario | inventario-adjuntos.json |
| Extracción PDF | 12 páginas, texto leído íntegro; render PDFium y revisión visual de las páginas agrupadas | articulo-pdf.txt y lectura-pdf-*.png |
| Extracción DOCX | 240 párrafos OOXML, incluidas celdas | articulo-docx.txt |
| Contraste de texto PDF/DOCX | Sin diferencias tras excluir espacios y folios; imágenes no comparadas | contraste-pdf-docx.json |
| Inspección de Excel | Cinco hojas; R01–R41 únicos, rutas 1–11, 7 fichas y Q01–Q09 | libro-extraido.json |
| Fórmulas B6:B21 | 16 resultados reproducidos independientemente y coincidentes con caché, diferencia absoluta <1e-10 | verificacion-calculos.json |
| Errores en caché de Excel | No se encontraron celdas de tipo error | verificacion-calculos.json; no equivale a recalcular en Excel |
| Operadores selectivos | R35/R36 codificados Mayor que; resultado conserva `>` | Extracción y cálculo B16 |
| Entorno PHP/Composer/Laravel | PHP 8.1.10, Composer 2.4.1, Laravel 10.50.4 | Comandos ejecutados con rutas Laragon |
| Entorno Node/MySQL/Git | Node 18.8.0, npm 8.18.0, cliente MySQL 8.0.30, Git 2.37.3.windows.1 | Comandos de versión ejecutados |
| Repositorio | `git status --short` devolvió que no es repositorio Git | No se inicializó ni creó commit |

El umbral 1e-10 solo admite representación binaria del caché frente al cálculo decimal independiente. No es un umbral de calidad municipal. Se inspeccionaron fórmulas y precedentes; el script reproduce las 16 operaciones autorizadas y no ejecuta fórmulas arbitrarias del archivo. No se alteraron datos, estilos, fórmulas ni archivos adjuntos.

## Reproducción

El script requiere Python con openpyxl, pypdf, pypdfium2 y Pillow. En esta sesión se usó el runtime de dependencias de Codex. Con Python que disponga de esas librerías:

```powershell
Set-Location 'C:\laragon\www\ResiduosSolidos'
python docs/09-evidencias/analizar_fuentes.py
```

Por defecto busca los cuatro nombres exactos en Descargas del usuario actual; conservarlos para reproducir. La ruta `python.exe` del PATH de esta sesión es un alias Windows, por lo que el comando anterior requiere un Python real configurado. La ejecución comprobada utilizó la ruta absoluta del runtime, no ese alias. El script reescribe únicamente las extracciones e informes en `docs/09-evidencias`, no los originales.

## Límites de lo comprobado

No se ejecutaron migraciones, instalaciones, pruebas PHPUnit/Pest de dominio, servidor web, conexión MySQL, integración Power BI, autenticación o reportes. No se midió desempeño, seguridad integral, aceptación de funcionarios o mejora municipal. No se renderizó Word independientemente ni se abrió el libro en Excel. Los hashes de los PDF municipales citados en el Markdown no se recalcularon porque esos binarios originales no fueron adjuntados.

Las referencias técnicas web se consultaron para versiones y requisitos. No se incorporaron cifras municipales nuevas ni se corrigieron las discrepancias de las fuentes. La entrega conserva el código y README del esqueleto existente y añade documentación y herramientas de análisis bajo `docs/`.
