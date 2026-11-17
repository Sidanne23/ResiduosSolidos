# Contrato de importación e indicadores

Diseño pendiente de implementación Laravel. Fuentes: Excel completo, Markdown Diccionario/Fórmulas/Discrepancias y artículo pp. 3–4. El script de inspección de Fase 1 no es el importador de producción.

## Contrato de las cinco hojas

| Hoja | Rango útil y estructura | Transformación autorizada |
|---|---|---|
| Registros | A1:J42; ID, Año, Indicador publicado, Fuente, Valor numérico, Unidad, Operador, Período, Observación, Localizador | R01–R41 exactos; fuente existente; valor numérico, año entero; observación vacía conserva NULL sin indicar ausencia del dato; Exacto→`=`, Mayor que→`>` |
| Rutas2024 | A1:H12; Ruta, Lugar, Participantes registrados, Día, Horario, Tipo de residuo, Fuente, Corte | 11 filas con ruta 1–11; cantidad entera; conservar orden, lugar agrupado, día, horario textual e inorgánico |
| Procedencia | A1:H8; Fuente, Documento, Año, Localizador, Período declarado, URL institucional, Acceso y alcance, Fecha de consulta | 7 fichas, fechas Excel como fechas; no inferir que el recurso catalogado está incorporado |
| Observaciones | A1:E10; Caso, Fuente, Hallazgo, Tratamiento analítico, Dato requerido | Q01–Q09 como excepciones documentales; mapear a registros afectados sin cambiar texto crudo |
| Indicadores | A5:E21; Indicador calculado, Resultado, Unidad, Período, Interpretación | B6:B21 son fórmulas de referencia; guardar expresión/caché/formato, recalcular con motor autorizado; A24:C27 son tabla auxiliar, no registros adicionales |

El rango usado de Indicadores alcanza fila 40/columna K por formato y objetos: no son 40 observaciones. Se procesan tablas identificadas por encabezado, evitando convertir decoración y gráficos en filas. Este contrato es para la versión adjunta: futuros archivos requieren versión explícita del mapeo. No se aceptarán encabezados silenciosamente reordenados sin identificarlos por nombre.

La previsualización mostrará archivo, SHA-256, versión de mapeo, filas por hoja, campos originales y normalizados, errores estructurales y restricciones de uso. Encabezados ausentes, ID duplicado, fuente inexistente, unidad/operador no autorizado o tipos inválidos impedirán confirmar. Discrepancias conocidas se conservan con advertencias y restricciones, sin descartar los registros válidos para consulta.

La fórmula de entrada no se ejecuta como código. No se evaluarán vínculos externos, macros, funciones arbitrarias ni DDE. El motor solo ejecutará definiciones internas versionadas con operandos de IDs conocidos. Toda discrepancia entre caché y cálculo quedará en el reporte de importación.

## Flujo transaccional

1. Autorizar al analista, validar archivo, MIME, tamaño, estructura ZIP y límites de expansión. Conservar binario en disco privado, calcular SHA-256 real y registrar lote.
2. Detectar binario ya conocido; devolver lote y estado. Permitir reintentar un lote fallido sin duplicar filas. Control adicional por clave documental de cada registro y ruta; nunca deduplicar solo por valor.
3. Extraer cinco hojas a filas crudas inmutables con coordenadas, formato, fórmula y caché. Mantener los textos originales de períodos y unidades.
4. Validar referencias, tipos, unicidad y restricciones semánticas. Generar previa y hash del snapshot junto a usuario, reglas y versión de mapeo.
5. Al confirmar, revalidar permisos, hash y estado. Bloquear lote en BD y cargar fuente/versiones, registros curados, excepciones y DW dentro de una transacción.
6. Si falla, rollback de curado y DW; guardar estado fallido y detalle seguro fuera de la transacción revertida. El archivo, staging e intentos continúan disponibles.
7. Si termina, registrar conteos insertados, omitidos y advertidos; linaje por fila; usuario y fecha. La importación inicial deberá reconciliar 41 observaciones, 11 participaciones, 7 fichas y 9 observaciones de calidad.

Un binario Excel puede documentar varias fuentes municipales: el archivo de importación es evidencia de transcripción, no el PDF municipal original. Este vínculo debe quedar explícito en el linaje.

## Catálogo de cálculo permitido

En la tabla, Rxx significa **ID de Registros**, y ruta N significa fila de Rutas2024. Los cocientes se conservan como fracciones en el motor; se multiplican por 100 al mostrar porcentaje. El porcentaje publicado de Registros se almacena como puntos porcentuales (93,14), por lo que se divide entre 100 para compararlo con una fracción. B12 ya devuelve puntos porcentuales.

| Celda / código | Fórmula semántica | Resultado para mostrar | Restricción |
|---|---|---|---|
| B6 / IC01 | Σ participantes de las 11 rutas | 1 308 registros | Mismo cuadro M24; no personas únicas |
| B7 / IC02 | (ruta 1 + ruta 8) / B6 | 32,80 % | Concentración interna; si filtro excluye rutas, no renombrar el resultado como este indicador |
| B8 / IC03 | R09 / R08 | 62,85 % | Recolección y transporte M24 |
| B9 / IC04 | R08 − R09 | S/ 1 458 432,88 | Diferencia aritmética al corte |
| B10 / IC05 | R06 / R05 | 69,26 % | Barrido M24 |
| B11 / IC06 | R12 / R11 | 93,16 % | Mantener también R13=93,14 % |
| B12 / IC07 | (B11 − R13/100) × 100 | 0,0162 puntos porcentuales | Valor sin redondear 0,016230411… |
| B13 / IC08 | R22 / R21 | 48,15 % | PP0036 primer semestre 2025 |
| B14 / IC09 | R21 − R22 | S/ 2 720 410,00 | Diferencia PIM menos ejecutado |
| B15 / IC10 | R17 + R18 | 615,90 t | Valorización M25 del corte publicado, sin serie mensual |
| B16 / IC11 | umbral R35 + umbral R36, propagar `>` | >262,27 t | Selectiva M26 primeros seis meses; no valorización |
| B17 / IC12 | R20 / R19 | 0 % | Conjunto identificado M25; cero publicado válido |
| B18 / IC13 | R28 + R29 + R30 | 13 vehículos | Comparar composición con R27, no sumar ambos |
| B19 / IC14 | R34 / R33 | 18,17 días equivalentes | Diagnóstico temporal; no días de trabajo medidos |
| B20 / IC15 | R40 / R39 | 42,22 % | Asignación escolar aprobada E26 |
| B21 / IC16 | R41 / R39 | 57,78 % | Asignación comunitaria aprobada E26 |

Antes de calcular: comprobar presencia, unidad, corte, ámbito, proceso y naturaleza de todos los operandos. La autorización se define por fórmula: por ejemplo, IC13 suma subtipos de flota, e IC11 admite desigualdades. Un NULL devuelve `No calculable` con causa; divisor cero devuelve causa específica. Nunca se redondean operandos para calcular. Se propone BCMath a escala 30 y persistencia DECIMAL(65,30), sin redondeo adicional previo a visualización; los casos de referencia independientes a 40 dígitos están en el JSON de evidencia. La prueba Laravel debe verificar esa precisión y la conservación del linaje.

## Consultas BI y filtros

La vista ejecutiva mostrará IC03 M24, IC08 M25 y R39 E26 por separado, junto con su corte y concepto. La operativa mostrará R26/R27/R37/R38 de M26 y una sección independiente para participación M24. La vista de calidad presentará Q01–Q09, sus registros afectados y operaciones restringidas.

Se habilitan fuente, año declarado, etiqueta/precisión del período, proceso, tipo de residuo y ámbito según disponibilidad. El año de documento no sustituye al período de observación. La ausencia de residuo identificado se muestra como dato no documentado; no se asigna un tipo por defecto. El filtro de ruta aplica al cuadro de participación, no reparte el presupuesto entre rutas. Selecciones incompatibles se muestran en paneles separados con explicación.

Si un filtro excluye un operando obligatorio, se indica `No calculable con los filtros actuales`. Para una distribución de rutas seleccionadas se podrá definir una fórmula distinta que explicite el denominador filtrado. No se cambiará silenciosamente el denominador de IC02. Sin observaciones mensuales no se dibuja una línea mensual ni se interpolan valores.

## Carga dimensional y actualización

Primero se cargan dimensiones por claves naturales y versión, luego los tres hechos mediante sus FK de origen únicas. Se verifican medidas y conteos frente al curado antes de confirmar. Una nueva versión de fuente genera nuevos miembros y hechos trazables; las vistas corrientes seleccionan interpretaciones activas. Un reintento sobre el mismo lote no crea hechos duplicados.

No se usarán seeders para inventar operaciones. El futuro seeder documental deberá invocar la misma validación y exigir el archivo autorizado o un dataset verificable con manifiesto; las fábricas ficticias quedarán limitadas a pruebas aisladas. Las vistas SQL de consulta tendrán permisos de lectura y no permitirán actualizar hechos desde Power BI.
