# Plan de implementación y documentación

El plan original de Fase 1 se conserva abajo. Ya se implementó una primera versión que recorre parte de las fases 2–9: entorno PHP/Laravel portable, base documental MySQL, acceso, consultas, ETL inicial, panel y reportes tabulares con pruebas. La actualización del servidor MySQL, DW, versionado documental y operaciones futuras continúan pendientes. Véase el estado detallado en `../02-requisitos/matriz-trazabilidad.md` y el manual en `../07-manuales/programa-local.md`. Ninguna fase posterior se declara completa solo por disponer de esta primera versión. El proyecto completo requiere satisfacer todos los criterios del pedido §14.

| Fase | Implementación y dependencia | Artefactos reales que deberán existir | Puerta de salida |
|---|---|---|---|
| 1 Análisis | Leer cuatro adjuntos, alcance, requisitos, riesgos, arquitectura y modelo inicial | Documentos enlazados en docs/README; inventario SHA-256, extracción y verificación independiente | 12 módulos trazados a evidencia o propuesta; datos faltantes explícitos; cálculos contrastados |
| 2 Entorno | Verificar PHP/Composer/Node/MySQL, actualizar versiones y adaptar esqueleto Laravel; conservar checkout | composer/package lock actualizados, .env.example sin secretos, registro de dependencias, guía Laragon y arranque | Laravel mantenido arranca; assets compilan; conexión MySQL y plataforma probadas; ningún borrado de BD existente |
| 3 Datos | Llevar modelo a migraciones, Models, relaciones, índices, restricciones, vistas y carga dimensional | Migraciones, modelos Eloquent, ER final, diccionario por columna, SQL de consulta, plan de respaldo | Migración y rollback en BD de prueba; FK/NULL/unicidad verificadas; ninguna evidencia ficticia en semilla |
| 4 Acceso e interfaz | Fortify/Blade, roles/Gates/Policies, perfil y usuarios, layout responsive | Código auth/admin, pantallas base, diagrama de casos de uso, manual inicial y capturas auténticas | T01, rutas protegidas, recuperación probada con correo de desarrollo; Pint y build |
| 5 Módulos | Documentos/versiones, catálogo de procesos y registros, rutas, recursos, incidencias y presupuesto | Controllers, Requests, Policies, Services y vistas completas; formularios de futuro uso vacíos; manuales por rol | T03–T08, auditoría y archivos privados; fuentes/versiones consultables; agregados sin descomposición ficticia |
| 6 ETL | Cinco hojas, previa, errores, confirmación, idempotencia, carga curada y DW | Importador real, reporte por lote, mapeo versionado, diagramas de actividad/secuencia y manual de importación | T09 y conciliación 41/11/7/9; fallo inducido y concurrencia; original conservado |
| 7 BI | Motor versionado y vistas ejecutiva/operativa/calidad; filtros compartidos | Servicios de cálculo/compatibilidad, gráficos, trazabilidad y catálogo de indicadores | T02/T10/T11; 16 fórmulas y negativas de comparabilidad; todas las discrepancias visibles |
| 8 Reportes | PDF y Excel con snapshot de consulta y permisos | Exportadores, plantillas, tablas/gráficos pertinentes, manual de reporte | T12; abrir XLSX y renderizar PDF, verificar cifras/operadores/cortes y autorización |
| 9 Validación | Pruebas de integración, seguridad, cálculos, importación, exportación y restauración | Casos y resultados fechados, salidas reales de comandos, defectos y correcciones, evidencias de UI | Pruebas completas pasan; auditoría de dependencias atendida; restauración ensayada; limitaciones expresas |
| 10 Entrega | Consolidar manuales, investigación, mantenimiento y despliegue | README del sistema, instalación, administrador/analista/registrador/consultor, respaldo/restauración, documentación técnica y académica | Instalación limpia en Laragon con MySQL, flujos comprobados y manual reproducible; checklist del pedido §14 completo |

## Orden de trabajo y seguimiento

La Fase 2 comienza por renovar el entorno: PHP 8.1 no satisface Laravel 13 y la instalación base de Laravel 10 terminó soporte de seguridad. Se preservará el proyecto actual y se comparará un esqueleto 13 limpio antes de adaptar bootstrap, dependencias y pruebas. No se hará un reemplazo ciego del repositorio.

En cada funcionalidad se entregarán archivos completos en el proyecto, comandos PowerShell precisos, relación con componentes existentes, prueba ejecutada y resultado observado. La documentación se actualizará con el estado final; capturas pendientes no se sustituirán por imágenes de prototipos presentadas como funcionamiento real.

Las fases 5–8 requieren una base de acceso segura y un modelo de evidencia estable. El motor puede desarrollarse con fixtures ficticios aislados antes de la carga real, pero la publicación municipal solo usará el corpus autorizado. Una corrección de reglas exige repetir las pruebas afectadas y revisar reportes.

## Documentación por carpeta

| Carpeta | Contenido de esta entrega o contenido futuro |
|---|---|
| 01-analisis | Análisis, alcance y plan presentes; decisiones y riesgos se actualizarán |
| 02-requisitos | Matriz, casos de uso, historias, reglas y RNF presentes |
| 03-arquitectura | Componentes, despliegue y secuencia propuestos; UML y decisiones finales al implementar |
| 04-base-de-datos | ER, diccionario lógico/físico inicial y DW presentes; migraciones/SQL/modelos en Fase 3 |
| 05-etl-bi | Contrato, fórmulas, comparabilidad y actualización presentes; manual técnico final al implementar |
| 06-pruebas | Plan de pruebas presente; resultados del sistema y matriz de defectos en Fases 4–9 |
| 07-manuales | Preparación del entorno presente; manuales operativos y recuperación al existir los módulos |
| 08-investigacion | Delimitación metodológica y evaluación futura presentes; resultados técnicos reales se actualizarán |
| 09-evidencias | Hashes, extracciones, contraste, cálculo reproducido y revisión de Fase 1 presentes; capturas y logs auténticos futuros |

## Dependencias externas aún necesarias

Configurar un correo de pruebas para recuperación y luego un remitente institucional si se despliega. Obtener responsables y criterios de validación para nuevas operaciones. Conseguir evidencia fuente individual si se quiere resolver Q02/Q03/Q05/Q06/Q09, elaborar mapas o medir desempeño. No hace falta resolver esas discrepancias para conservar y consultar sus cifras con restricciones.

Power BI se evalúa después de estabilizar vistas BI y permisos. Su conexión, actualización y visuales deben verificarse antes de anunciarla como disponible. No bloquea la entrega web Laravel solicitada.
