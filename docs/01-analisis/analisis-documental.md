# Análisis documental y alcance

## Problema y objetivos

El artículo plantea qué indicadores pueden obtenerse de las publicaciones municipales y cómo estructurarlos para sustentar decisiones trazables (PDF p. 2; DOCX, Introducción). Hay cifras de cobertura, presupuesto, masas y participación con unidades, universos y cortes diferentes. Integrarlas sin contexto produciría tasas y tendencias sin sustento.

El objetivo de investigación es analizar la gestión de residuos sólidos de Amarilis mediante información pública y establecer su integración en una arquitectura de inteligencia ambiental basada en BI. Los objetivos específicos del artículo son examinar indicadores disponibles, estructurar registros, desarrollar análisis descriptivo y determinar condiciones de uso del modelo para decisiones municipales. El objetivo de ingeniería propuesto es implementar una aplicación Laravel que permita consultar, importar, verificar y reportar esa evidencia, conservando sus límites. La construcción del software no acredita por sí sola mejora institucional.

## Método y contraste de los cuatro adjuntos

Se leyó todo el Markdown de fuentes y cálculos, se extrajeron las 12 páginas del PDF y los 240 párrafos OOXML del DOCX, incluidas las celdas de tablas. Se revisaron las páginas del PDF renderizadas en tres láminas, con especial atención a las figuras 1–5. Se inspeccionaron todas las celdas no vacías de las cinco hojas del Excel, incluyendo fórmulas, formatos y valores guardados. No se modificó ni recalculó el archivo de origen.

El contraste del texto PDF/DOCX, omitiendo espacios y folios numéricos, no encontró diferencias. Esto verifica concordancia textual con ese método, no identidad binaria ni renderizado independiente del Word. Las imágenes del DOCX no se compararon píxel a píxel. La revisión visual se hizo sobre el PDF suministrado.

La investigación es documental aplicada, cuantitativa descriptiva, no experimental y con componente tecnológico propositivo (PDF p. 3). Ventana documental 2024–2026, consulta histórica 7 de noviembre de 2026. No hay encuestas, trabajo de campo, mediciones independientes ni validación de funcionarios. La monografía citada y los PDF municipales originales no forman parte de los cuatro archivos recibidos.

## Inventario de información

| Archivo o hoja | Contenido comprobado | Uso y límite |
|---|---|---|
| PDF y DOCX | Objetivos, metodología, 4 tablas, 5 figuras, discusión, conclusiones y referencias | El prototipo del artículo es documental y estático; MySQL y Power BI son diseño (pp. 4 y 9) |
| Registros A1:J42 | Encabezado y R01–R41, sin IDs repetidos | Valores publicados, unidad, operador, corte, observación y localizador |
| Rutas2024 A1:H12 | 11 filas, rutas numéricas 1–11 | Lugar, cantidad de registros, día, horario, tipo, fuente y corte; no personas únicas |
| Indicadores A5:E21 | 16 fórmulas en B6:B21 | Resultados calculados, separados de publicados; tablas auxiliares en A24:C27 |
| Procedencia A1:H8 | M24, M25, M26, E26, DA26, C25 y C26 | Solo las primeras cuatro sustentan R01–R41 |
| Observaciones A1:E10 | Q01–Q09 | Hallazgo, tratamiento y dato requerido |
| Markdown | Diccionario, 41 registros, rutas, fórmulas, discrepancias, hashes declarados y referencias | Complementa trazabilidad, no reemplaza adjuntos municipales ausentes |

Los códigos R01–R11 usados para rutas en el artículo coinciden textualmente con códigos de indicadores. En Excel las rutas son números 1–11. El sistema mantendrá espacios de identificación separados: `registro:R01` y `ruta:M24:1`, con etiqueta visual R01 para la ruta cuando proceda.

## Catálogo documental

| Fuente | Localizador declarado | Alcance y restricción |
|---|---|---|
| M24 | PDF municipal pp. 28–29, 7.1.1–7.1.3 | Rendición parcial 2024 sin fecha propia del cuadro. Nunca asignar 30 de junio o cierre anual |
| M25 | PDF municipal pp. 5, 20, 51–53, 7.1.3–7.1.6 | Documento general enero–junio, EDUCCA mayo–agosto, masas sin desglose mensual |
| M26 | PDF municipal pp. 103–106 | Primer semestre para servicio, primeros seis meses para RECICLA; rótulos gráficos de 2025 heredados |
| E26 | Resolución 024-2026-MDA/GM, arts. 1–2 y apartado 3 | Programación anual aprobada el 29/01/2026; contenido recuperado en consulta histórica, descarga 403 |
| DA26 | Catálogo MINAM, recursos 2019–2024 | Descargas 403/502 en el estudio; ningún registro incorporado |
| C25 y C26 | Reportes de control interno | Contexto de control; no son ejecución POI/PEI ni series de valorización |

Las URL y la fecha de consulta se preservan en `libro-extraido.json`, hoja Procedencia, y en los originales. Los SHA-256 de PDF municipales que menciona el Markdown son **huellas declaradas por los autores**, no hashes de archivos recibidos en esta sesión. El inventario nuevo contiene exclusivamente hashes calculados sobre los cuatro adjuntos efectivamente leídos.

## Resultados reproducidos y significado

Los 16 resultados de B6:B21 se reprodujeron con Decimal a 40 dígitos y se compararon con el caché del Excel. Destacan 1 308 registros de rutas; concentración de rutas 1 y 8 de 32,80 %; ejecución de recolección M24 de 62,85 %; PP 0036 de 48,15 %; valorización M25 de 615,90 t; cota de recolección selectiva M26 **>262,27 t**. Los resultados completos, fórmulas y diferencias numéricas están en `../09-evidencias/verificacion-calculos.json`. No son pruebas del futuro motor Laravel.

La ejecución EDUCCA M24 publicada es 93,14 %; los importes producen 93,156230411… %, visible como 93,16 %. Se conservan ambos y su diferencia de 0,016230411… puntos porcentuales. El cero de R20 es un cero documentado válido, distinto de un valor desconocido.

## Alcance funcional y actores propuestos

Se propone consultar evidencia documental, importar el Excel con previsualización, calcular indicadores permitidos y desarrollar las tres vistas de la Figura 5. Se propone además una captura operativa futura con evidencia y autoría. Los módulos operativos partirán vacíos; los agregados de flota, rutas y puntos críticos no se descompondrán en entidades ficticias.

Administrador, analista, registrador y consultor son roles técnicos propuestos por el pedido, no cargos municipales acreditados. El administrador gestiona cuentas; el analista revisa e importa evidencia y cálculos; el registrador captura operaciones y adjuntos; el consultor consulta y exporta según permiso. Los permisos efectivos se comprobarán en servidor.

Faltan padrones de participantes únicos, denominadores de cobertura, pesajes y días efectivos, viajes y rutas cumplidas, inventario de placas, costos por proceso, sitios individualizados y coordenadas, lotes y destinos de valorización, gasto EDUCCA 2026, responsables institucionales, acceso SMTP y autorización de evaluación de campo. La ausencia en el corpus no demuestra inexistencia municipal.

Quedan excluidos por ahora aprendizaje automático, pronósticos, sensores, mapas con coordenadas supuestas, costo por tonelada, productividad t/h, cobertura recalculada, disposición final distrital y tendencias 2025–2026 entre valorización y recolección selectiva.

## Riesgos y decisiones

El mayor riesgo es una agregación semánticamente inválida; se controla con compatibilidad explícita y fórmulas autorizadas. La divergencia de las versiones se controla conservando originales y revisiones. La instalación base está desactualizada; la Fase 2 debe renovar el entorno y portar el esqueleto de forma verificable. No se promete integración Power BI ni mejoras municipales antes de implementarlas y evaluarlas.
