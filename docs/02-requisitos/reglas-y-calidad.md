# Reglas de negocio y calidad

Diseño propuesto para implementación en servicios Laravel y restricciones de datos. Las reglas siguientes aún no están instaladas en la aplicación. Cada publicación y exportación deberá invocar las mismas reglas del servidor.

| Regla | Decisión de sistema | Evidencia | Prueba de aceptación futura |
|---|---|---|---|
| RN01 Procedencia | Exigir versión fuente, localizador, unidad, operador, período, territorio, proceso, naturaleza y autoría de transformación | PDF pp. 3–4; Registros A:J | Toda cifra de BI permite recorrer el linaje hasta archivo/hoja/fila o documento/apartado |
| RN02 Ausencia | NULL más motivo; distinguir dato no disponible, pendiente de validación y no calculable | PDF p. 9; Markdown Datos no calculables | Una masa ausente no se grafica como cero; R20=0 sí se conserva |
| RN03 Evidencia inmutable | Original y fila cruda solo anexables; corrección crea revisión con motivo y actor | PDF p. 4 | UPDATE/DELETE del original se deniega mediante servicio/permisos; revisión anterior consultable |
| RN04 Tiempo | Guardar etiqueta y precisión; no fabricar fechas de intervalo | Markdown Control temporal | M24 inicio/fin NULL; R15 ambiguo; registrar fecha de aprobación E26 sin usarla como fecha de gasto |
| RN05 Compatibilidad | Mismo proceso, unidad, universo, ámbito, naturaleza y base temporal para agregación/comparación autorizada | PDF pp. 6–7, 10 | Valorización 2025 frente a selectiva 2026 no genera variación ni línea de tendencia |
| RN06 Desigualdad | Conservar `>` en registros, cálculo, gráfico y exportación; sumar umbrales solo para masas compatibles | Registros R35/R36; Q07 | Resultado >262,27 t, nunca =262,27 t |
| RN07 Cobertura | R01 y R14 son porcentajes publicados sin componentes; no sumables ni recalculables | PDF p. 5; Q04 | No inferir mejora de 99 a 100 % ni cobertura por sectores |
| RN08 Presupuesto | Separar actividad M24, PP0036 M25 y programación E26; diferencia aritmética no es ahorro | PDF p. 7 | E26 ejecutado NULL, ninguna tasa de ejecución; etiqueta visible en exportación |
| RN09 Discrepancias | Mantener publicado y calculado simultáneamente; resolución solo con nueva evidencia | Q02, Q03, Q05, Q06, Q09 | RN bloqueante o advertencia visible tanto en consulta como reporte |
| RN10 Rutas | Participación es distribución interna por cuadro; identificar rutas dentro de fuente/cuadro | PDF p. 5; Rutas2024 | 1308 no se etiqueta personas únicas, hogares ni población cubierta |
| RN11 Recursos e incidencias | Agregado histórico no crea objetos individuales ni eventos | PDF pp. 5, 8 | No generar placas, 18 sitios, viajes o coordenadas a partir de conteos |
| RN12 Duplicados | Distinguir repetición de logros, binario repetido y clave de fila repetida | Q01; pedido ETL | Binario idéntico no crea segunda carga; una nueva versión diferente no se descarta por compartir valor |
| RN13 EDUCCA 2025 | Guardar 1585 declarado y 1136 de componentes solo como conciliación; diferencia 449 sin asignación | Q05; Markdown Discrepancias §5 | No crear 449 participantes ni incrementar componentes |
| RN14 Escala territorial | No atribuir a Amarilis datos nacionales o instalaciones compartidas | PDF pp. 2, 10; Markdown Control territorial | No cargar 150 t/día de Chilepampa como generación distrital |
| RN15 Naturaleza | Separar publicado, calculado y operativo posterior; programación también tiene categoría propia | PDF pp. 4, 7 | Un plan no entra como ejecución; pruebas ficticias solo en base de pruebas |
| RN16 No aditividad | No sumar porcentajes, snapshots de flota ni presupuestos acumulados entre cortes | PDF p. 4 | Seleccionar corte compatible; suma solo de componentes expresamente autorizados |
| RN17 Aproximación | Preservar calificativo aproximado de R32/R34 aunque Excel codifique operador Exacto | Registros R32/R34 y PDF | Operador `=` no convierte una estimación publicada en pesaje exacto |
| RN18 Sin evidencia adicional | DA26/C25/C26 permanecen procedencia sin cifras importadas | Procedencia; Q08 | Ningún dato local reconstruido desde catálogo inaccesible |

## Estados verificables de calidad

La naturaleza del dato y su calidad son ejes distintos. `publicado` o `calculado` no significan validado institucionalmente. Cada registro puede tener varias incidencias, sin reducirlas a un semáforo o puntaje inventado. Etiquetas propuestas: repetición documental detectada (Q01), discrepancia aritmética (Q02), período ambiguo (Q03), denominador no verificable (Q04), conciliación pendiente (Q05/Q06), cota inferior (Q07), recurso no incorporado (Q08), inconsistencia de versión (Q09).

La validez de uso se decide **por operación**: R15 puede consultarse y exportarse con advertencia, pero no anualizarse ni usarse como denominador. Una cota inferior puede agregarse con otra compatible y conservar `>`. La falta de incidencias registradas no se etiqueta como calidad municipal perfecta. El dashboard de calidad contará excepciones del corpus y explicará su universo, sin estimar completitud institucional.

## Requisitos no funcionales y comprobación

| ID | Requisito | Verificación prevista |
|---|---|---|
| RNF01 Seguridad de acceso | CSRF, sesiones regeneradas, hash de contraseña Laravel, limitación de intentos, Gates/Policies, Form Requests | Pruebas de CSRF y autorización, expiración de recuperación y suspensión de usuario; revisión de rutas |
| RNF02 Entradas y salidas | Eloquent/Query Builder parametrizado, asignación masiva limitada, Blade con escape por defecto | Solicitudes con campos inesperados, HTML y filtros maliciosos rechazadas o escapadas |
| RNF03 Archivos | Disco privado, tamaño/tipo/MIME y estructura permitidos, nombre generado, rutas sin entrada arbitraria; límites de expansión XLSX | PDF/XLSX/DOCX/MD solo según caso autorizado; ZIP corrupto o excesivo rechazado; sin evaluación de fórmulas externas |
| RNF04 Auditoría | Actor, acción, entidad, revisión, fecha, motivo y correlación; no contraseñas ni tokens en log | Acción autorizada deja evento; credenciales y contenido sensible ausentes |
| RNF05 Atomicidad | Carga curada y DW transaccionales, idempotentes y bloqueadas por snapshot | Fallo de mitad de carga no publica datos parciales; concurrencia no duplica |
| RNF06 Precisión | DECIMAL/BCMath, unidades explícitas, redondeo solo al mostrar; división por cero controlada | Valores de referencia y límites NULL/0/>; discrepancia conservada |
| RNF07 Interfaz | Responsive, teclado, etiquetas, mensajes concretos y estado además de color | Revisión de formularios, tablas paginadas y estados en móvil/escritorio |
| RNF08 Mantenibilidad | Servicios de dominio, pruebas, migraciones, dependencias bloqueadas y manuales coherentes | CI local: pruebas, Pint, build y auditorías; lockfiles conservados |
| RNF09 Recuperación | Respaldar BD y archivos privados, restaurar en entorno aislado con verificación de hashes | Ensayo real de restauración antes de entrega; RPO/RTO institucionales pendientes de acordar |
| RNF10 Desempeño | Paginación, índices por filtros y lotes de importación; evitar consultas N+1 | Medir consultas y tiempos con datos autorizados; no prometer cifras de rendimiento sin medir |

En producción: HTTPS, APP_DEBUG=false, secretos fuera del control de versiones, usuario MySQL de mínimo privilegio y permisos privados de archivos. Power BI usará acceso de solo lectura a vistas aprobadas cuando esa integración exista.
