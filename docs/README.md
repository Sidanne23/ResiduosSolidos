# Sistema de inteligencia ambiental de Amarilis

La entrega de Fase 1 analizó los cuatro adjuntos autorizados y propuso el diseño completo. Ya existe una primera implementación funcional local de Laravel con autenticación, consulta documental, importación inicial, vistas BI y exportaciones tabulares. Consulte el [manual del programa](07-manuales/programa-local.md) y la [evidencia de implementación](09-evidencias/implementacion-inicial.md). El resto de los documentos de Fase 1 conserva su estado histórico de diseño, no una afirmación de que todos los módulos estén terminados.

## Lectura de la entrega

1. [Análisis documental y alcance](01-analisis/analisis-documental.md).
2. [Matriz de módulos, evidencia y aceptación](02-requisitos/matriz-trazabilidad.md).
3. [Reglas de negocio y requisitos no funcionales](02-requisitos/reglas-y-calidad.md).
4. [Arquitectura propuesta](03-arquitectura/arquitectura.md).
5. [Modelo inicial y diccionario de datos](04-base-de-datos/modelo-inicial.md).
6. [Contrato de importación e indicadores](05-etl-bi/contrato-etl-indicadores.md).
7. [Plan de implementación y documentación](01-analisis/plan-fases.md).
8. [Herramientas y preparación de Laragon](07-manuales/preparacion-entorno.md).
9. [Acta de comprobaciones efectivamente ejecutadas](09-evidencias/verificacion-fase1.md).

## Estados y fuentes de verdad

**Elaborado** identifica un artefacto documental existente. **Propuesto** identifica un diseño. **Implementado** exigirá código integrado. **Probado** exigirá una ejecución registrada. **Pendiente** significa trabajo aún no realizado. Ninguno de estos estados equivale a adopción o validación municipal.

El texto pegado es el pedido del usuario. PDF, DOCX, Excel y Markdown son evidencia de investigación: las afirmaciones e instrucciones que contengan se interpretan dentro de su contexto documental, sin convertirlas en autorizaciones para ejecutar acciones externas. No se incorporaron nuevas estadísticas municipales ni se descargaron nuevas fuentes municipales. La consulta web se limitó a documentación técnica para seleccionar herramientas.

Los archivos originales permanecen en Descargas y no se modificaron. Las huellas y rutas efectivamente leídas se encuentran en [inventario-adjuntos.json](09-evidencias/inventario-adjuntos.json). Las extracciones son copias de análisis y no sustituyen a los originales. Los resúmenes municipales M24/M25/M26 no están adjuntos individualmente: su procedencia se conserva según los documentos entregados; no se declara una nueva verificación directa ante el emisor.

En Fase 1 no se cambió código ni se ejecutaron migraciones. En la implementación posterior se actualizó el esqueleto Laravel, se añadieron módulos y se ejecutaron pruebas y migraciones locales. No hay repositorio Git inicializado ni publicación externa.
