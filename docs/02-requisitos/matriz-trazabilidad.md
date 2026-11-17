# Matriz de requisitos y evidencia

La matriz siguiente conserva la especificación de Fase 1. El estado de la primera implementación se registra al final y en `../09-evidencias/implementacion-inicial.md`; los criterios originales no se consideran satisfechos en su totalidad. P = función solicitada por el usuario para el sistema; D = contenido respaldado por documentos. Un dato D no demuestra que el módulo exista. Los localizadores PDF de esta matriz corresponden al artículo; los de M24/M25/M26 corresponden a las fuentes municipales citadas en los adjuntos.

| ID y módulo | Funciones requeridas | Datos disponibles | Datos faltantes y límites | Sustento | Criterio verificable y prueba prevista |
|---|---|---|---|---|---|
| RF01 Autenticación P | Login/logout, recuperación, perfil, usuarios, roles y permisos, auditoría | Esqueleto Laravel con modelo User; sin autenticación de dominio comprobada | Cuentas autorizadas, correo SMTP, política institucional | Pedido §6.1 y §9; no evidencia de cargos municipales | T01: login correcto, regeneración de sesión, logout invalida sesión, recuperación con token expirable de un uso, consultor recibe 403 al administrar usuarios |
| RF02 Panel P+D | Tarjetas, gráficos, filtros, enlaces a evidencia y calidad | R01–R41 y resultados B6:B21 | Series mensuales y universos homologados | Figura 5, PDF p. 9; Registros e Indicadores | T02: cada tarjeta muestra concepto, unidad, operador, corte, fuente, naturaleza y advertencias; filtro sin observaciones muestra dato no disponible |
| RF03 Documentos P+D | Alta, adjuntos, versiones, SHA-256, deduplicación, procedencia y correcciones | 4 adjuntos, 7 fichas de procedencia, hashes municipales declarados | Archivos municipales originales, versiones rectificadas y autorizaciones futuras | Procedencia A1:H8; Markdown Fuentes y Huellas; PDF pp. 3–4 | T03: archivo privado descargable solo con permiso; nueva versión no borra anterior; hash calculado solo para binario incorporado |
| RF04 Residuos P+D | Catálogo de procesos, consulta histórica y formularios de observaciones nuevas | R01–R41 con unidad, operador y contexto | Generación distrital, disposición por origen, pesajes y registros de tratamiento | Registros; PDF pp. 3, 6 y 8 | T04: bloquear registro sin evidencia y proceso; separar publicado/calculado/operativo; consulta histórica no incorpora borradores |
| RF05 Rutas P+D | Consulta de 11 rutas, zonas, día, horario, participación, filtros y exportación; programación/viajes futuros | Rutas2024 A1:H12; M24 p. 29, horario 06:00–14:00, inorgánicos | Geometrías, padrones únicos, viajes fechados, evidencia de recorridos | PDF p. 5 y Figura 2; Markdown Detalle de rutas | T05: 11 filas y total 1308, orden y agrupación originales; rutas no se convierten en tasa de cumplimiento |
| RF06 Recursos P+D | Consulta de agregados; futura captura de vehículos, estados, asignaciones y actividades | R03 adquisición de 2 barandas; R26–R32 estructura M26; flota 9+3+1=13 | Placas, identificadores individuales, mantenimiento y estados por fecha | PDF Tabla 1 p. 5; M26 p. 104 | T06: la carga de agregados no crea 13 vehículos ficticios; 2 adquiridos no se suman a 13 operativos |
| RF07 Puntos críticos P+D | Mostrar agregados; futura incidencia con sitio, ubicación, identificación, estado, intervención, responsable, evidencia y cierre | R19=18 identificados; R20=0 erradicados al corte M25 | Sitios, coordenadas, fechas e intervenciones individuales | PDF Tabla 2 p. 6 y Tabla 4 p. 8; M25 p. 52 | T07: 0/18=0 %; ningún sitio ni mapa generado desde el agregado; cierre exige evidencia y fecha coherente |
| RF08 Presupuesto P+D | Asignación/PIM, ejecución, porcentaje, diferencia, actividad y corte | R05–R13, R21–R23, R39–R41 | Fase presupuestaria detallada y ejecución E26 | PDF Tabla 3 p. 7; M24 p. 29, M25 p. 20, E26 apartado 3 | T08: EDUCCA M24 conserva 93,14 y 93,16; E26 ejecutado NULL y porcentaje no calculable; no total conjunto entre programas |
| RF09 ETL P+D | Cinco hojas, encabezados, tipos, operador, unidades, duplicados, previsualización, confirmación, transacción y reporte | 41 registros, 11 rutas, 7 fuentes y 9 observaciones; 16 fórmulas principales | Importador web y política de nuevas plantillas | PDF pp. 3–4; Excel completo y Markdown Diccionario | T09: repetir binario no duplica filas; fallo inducido revierte carga curada y DW, conserva original y bitácora; previa no publica datos |
| RF10 BI P+D | Vistas ejecutiva, operativa y calidad; filtros fuente, año, corte, proceso, residuo y territorio | Indicadores y Figura 5; 18 rutas, 13 unidades, 5 y 11 rutas selectivas M26 | Comparabilidad interanual y geodatos verificados | PDF pp. 4, 9–10; Observaciones Q01–Q09 | T10: selección incompatible se muestra separada con razón, sin tendencia ni porcentaje conjunto; sin inventar puntuación de calidad |
| RF11 Motor P+D | Fórmulas versionadas, operandos, precisión, operadores y restricciones | Indicadores B6:B21 y Markdown Fórmulas | Componentes para costo/t, productividad, cobertura y valorización/generación | PDF p. 3 y Tablas 2–3; contrato ETL | T11: reproducir 16 resultados con operandos documentados; >235,78 + >26,49 devuelve >262,27; NULL y divisor cero no producen cero |
| RF12 Reportes P+D | PDF y Excel con filtros, fecha, tablas/gráficos pertinentes, fuente, corte, fórmula y limitaciones | Dataset y fórmulas inspeccionados | Plantillas finales, renderizado y exportadores integrados | Pedido §6.12; PDF figuras 2–5 como referencia de contenido | T12: ambos formatos reproducen valores, > y advertencias; distinguir histórico y nuevas operaciones, verificar archivo reabierto y PDF renderizado |

## Casos de uso e historias de usuario

| Caso | Actor e historia | Flujo y alternativa | Aceptación |
|---|---|---|---|
| CU01 Acceder | Como usuario autorizado necesito acceder a mis funciones | Credenciales → sesión → permisos; fallo devuelve error genérico y aplica limitación de intentos | RF01/T01; registro público deshabilitado |
| CU02 Incorporar fuente | Como analista necesito registrar versión y procedencia | Metadatos → adjunto → validación → hash → versión; duplicado enlaza a binario existente | RF03/T03; consulta y descarga autorizadas |
| CU03 Importar | Como analista necesito revisar antes de publicar | Subir → validar → previa y excepciones → confirmar snapshot → transacción → reporte | RF09/T09; binario o previa caducada exige nueva validación |
| CU04 Consultar BI | Como consultor necesito entender alcance y fuente | Elegir vista y filtros → evaluar compatibilidad → consultar valor → abrir registro y evidencia | RF02/RF10, T02/T10; sin selección válida no se inventan resultados |
| CU05 Verificar cálculo | Como analista necesito reproducir un indicador | Abrir fórmula → revisar operandos → comprobar regla → resultado o causa no calculable | RF11/T11; publicados nunca se sobrescriben |
| CU06 Capturar operación | Como registrador necesito incorporar un evento acreditado | Crear borrador → evidencia → revisión de analista → aprobación; corrección crea revisión | RF04–RF07/T04–T07; borrador excluido de BI documental |
| CU07 Conciliar excepción | Como analista necesito dejar constancia de una rectificación | Consultar Q → adjuntar nueva evidencia → registrar decisión y autor → nueva interpretación | RF03/RF10/T03/T10; original y excepción histórica siguen visibles |
| CU08 Exportar | Como consultor necesito conservar una consulta interpretable | Congelar filtros y snapshot → generar PDF/Excel → descargar por permiso | RF12/T12; no exportar datos fuera de su autorización |
| CU09 Administrar acceso | Como administrador necesito gestionar usuarios y roles | Crear usuario/invitar → asignar permisos → cambiar o suspender → auditoría | RF01/T01; no usar contraseñas predeterminadas en seeders |

Las pruebas T01–T12 describen la aceptación del sistema completo. Las pruebas de la implementación inicial están en `tests/Feature/DocumentarySystemTest.php`; su resultado se registra separadamente del acta de Fase 1.

## Estado de la primera implementación

| Requisito | Implementado y probado localmente | Pendiente para completar el requisito |
|---|---|---|
| RF01 | Fortify, sesiones, recuperación con token, usuarios, cuatro roles, Gates, perfil y auditoría | Entrega SMTP real y permisos configurables más allá de Gates |
| RF02 | Panel, gráficos y filtros, valor/corte/fuente/estado y consulta de evidencia | Extensiones ligadas a nuevas operaciones y DW |
| RF03 | Siete fichas, cuatro adjuntos privados con hash y descarga autenticada | Alta documental general, versiones y correcciones |
| RF04 | Clasificación y consulta de registros históricos por proceso | Formularios de observaciones operativas y revisión |
| RF05 | Once rutas originales, zonas, días, horarios, gráfico y conteos | Filtros específicos de ruta, exportación de rutas y programación/viajes |
| RF06 | Consulta de indicadores declarados y composición de flota | Inventario y actividades por unidad real |
| RF07 | Consulta de agregados y erradicación 0/18 documentada | Sitios individuales e incidencias con evidencia |
| RF08 | Cálculos y consulta documental de actividades M24, PP0036 y plan E26 separados | Gestión de nuevos cortes y registros presupuestarios |
| RF09 | Importación del binario Excel autorizado, previa, transacción, reversión, reintento e idempotencia | Nuevos mapeos, versiones, concurrencia ensayada y carga DW |
| RF10 | Tres vistas BI y filtros compatibles sobre el corpus inicial | Proyección dimensional e integración Power BI opcional |
| RF11 | Dieciséis fórmulas, precisión BCMath, NULL/divisor cero/incompatibilidad y operador estricto | Versionado editable de catálogo y linaje persistido de ejecuciones |
| RF12 | PDF y Excel tabulares con contexto, filtros, fuente y restricciones | Gráficos en PDF, reportes de operaciones nuevas y rutas |

No se declara aceptación completa del proyecto ni validación municipal.
