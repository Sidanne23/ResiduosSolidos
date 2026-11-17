# Plan de validación

Las comprobaciones efectivamente ejecutadas en esta entrega están en `../09-evidencias/verificacion-fase1.md`. Las pruebas de este documento son pendientes de implementación.

## Cobertura y entornos

Las pruebas unitarias cubrirán fórmula, valor NULL, divisor cero, operador `>`, aproximación y compatibilidad. Las funcionales cubrirán permisos, formularios, recuperación de cuenta, archivos y exportaciones. Las de integración utilizarán MySQL aislado para FK, DECIMAL, bloqueo e importación transaccional; SQLite no sustituirá la prueba de esas características.

Los fixtures ficticios deberán llevar identificación explícita y permanecer exclusivamente en pruebas. Una copia del Excel autorizado podrá usarse como fixture documental de lectura. Los casos corruptos se crearán como copias de prueba y nunca reemplazarán la evidencia original.

| Prueba | Entrada o acción | Resultado requerido |
|---|---|---|
| T01 | Acceso por cuatro roles, token repetido/expirado, logout, suspensión | 403 ante permiso ausente, sesión invalidada, token de un uso, correo probado |
| T02 | Filtro con/sin registros y apertura de procedencia | Fuente, período, unidad y estado visibles; sin registros no se presenta cero |
| T03 | Dos versiones de fuente, binario repetido, descarga sin permiso | Ambas versiones preservadas, hash real, sin acceso no autorizado |
| T04 | Nueva observación sin fuente; dato operativo en borrador | Rechazo de evidencia incompleta; borrador excluido de BI publicado |
| T05 | Importar Rutas2024 y filtrar rutas 1/8 | 11 filas, 1308 registros, 429 y 32,80 % con denominador del cuadro documentado |
| T06 | Cargar conteos R03 y R27–R30 | Sin vehículos individuales generados; composición suma 13, sin sumar adquisiciones |
| T07 | Agregado de puntos y cierre de incidencia sin evidencia | 0/18=0 %; no mapa ni sitios inventados; cierre rechazado |
| T08 | Presupuestos M24/M25/E26 y discrepancia EDUCCA | Programación separada, ejecutado E26 NULL, 93,14/93,16 simultáneos |
| T09 | Previa, confirmar dos veces, archivo corrupto, fallo a mitad, dos confirmaciones concurrentes | Sin datos antes de confirmar, sin duplicados, rollback, staging y original conservados |
| T10 | Valorización M25 contra selectiva M26; presupuesto actividad contra PP | Advertencia y paneles separados; sin tendencia o eficiencia conjunta |
| T11 | Las 16 fórmulas; valores desconocidos y cotas estrictas | Resultados de referencia, >262,27 persistente, No calculable con causa |
| T12 | Exportar por filtros y por permisos, reabrir XLSX, renderizar PDF | Misma consulta y snapshot, fórmulas/limitaciones/fuentes visibles, sin filtración de información |
| T13 | HTML malicioso, campos extra, SQL como filtro, CSRF ausente | Entrada validada, texto escapado, consultas parametrizadas y rechazo CSRF |
| T14 | Respaldo y restauración aislada de BD y disco privado | Conteos, integridad referencial y hashes reproducidos; registrar tiempos reales |

Las pruebas documentales no acreditan seguridad o funcionamiento de la aplicación. Los criterios visuales se revisarán en móvil y escritorio con navegación de teclado y capturas reales. Cada resultado registrará versión, comando o pasos, fecha, entorno, esperado, observado y evidencia. No se declarará aprobación a partir de una captura o de una fórmula sin ejecutar.
