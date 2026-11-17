# Arquitectura propuesta

Estado: diseño de Fase 1. Base documental: artículo pp. 4 y 9, figuras 1 y 5. Se propone un monolito modular Laravel con tres niveles y una base MySQL que separa tablas operacionales de tablas analíticas. Resulta suficiente para el corpus inicial y reduce complejidad de despliegue en Laragon.

## Componentes

| Nivel | Indispensable | Responsabilidad |
|---|---|---|
| Presentación | Blade, Tailwind, JavaScript, Chart.js | Formularios, tablas paginadas, vistas ejecutiva/operativa/calidad, trazabilidad, previsualización y reportes |
| Aplicación | Laravel, Fortify, Form Requests, Gates/Policies, servicios, Jobs cuando corresponda | Sesiones, validación, autorización, reglas semánticas, cálculos y coordinación ETL |
| Datos | MySQL InnoDB, disco privado original, filas crudas, tablas curadas, dimensiones y tres hechos | Evidencia, transacciones, linaje y consulta dimensional |

Fortify es la opción oficial propuesta para autenticación con vistas Blade propias. Se deshabilitará registro público; recuperación dependerá de un transporte de correo probado. No se necesita SPA, API pública, Sanctum para sesiones web, microservicios ni un paquete externo de permisos para cuatro roles iniciales.

PhpSpreadsheet leerá y exportará XLSX; se propone usarlo directamente para evitar un adaptador innecesario. Dompdf generará PDF desde vistas de reporte. Los gráficos PDF requerirán imágenes locales generadas y verificadas o una representación tabular equivalente: Dompdf no ejecuta Chart.js. No se aceptará HTML o URL remota arbitraria como plantilla. BCMath preservará cálculo decimal.

Alpine.js es opcional para diálogos y filtros; Jobs con cola de base de datos son opcionales si las importaciones crecen. Redis, contenedores, servicios de geocodificación y aprendizaje automático no son necesarios. Power BI Desktop, conector MySQL y gateway se evaluarán después y permanecerán opcionales; no existe conexión comprobada en esta entrega.

## Flujo y componentes

```mermaid
flowchart LR
    U[Usuario con rol técnico] --> B[Blade y gráficos]
    B --> W[Middleware de sesión y CSRF]
    W --> C[Controllers y Form Requests]
    C --> A[Gates y Policies]
    A --> D[Servicios documentales y operativos]
    A --> E[Importación y ETL]
    D --> O[(Tablas operacionales)]
    E --> F[Disco privado de originales]
    E --> S[(Lotes y filas crudas)]
    E --> V[Validación y compatibilidad]
    V --> O
    V --> Q[(Excepciones y auditoría)]
    O --> L[Carga dimensional transaccional]
    L --> H[(Dimensiones y tres hechos)]
    H --> I[Consultas BI y motor de indicadores]
    I --> B
    I --> R[Exportación PDF y XLSX]
    H -. opcional y pendiente .-> P[Power BI de solo lectura]
```

La zona original conserva el binario sin modificación; staging conserva hoja, celda, fórmula, caché y fila. La zona curada añade interpretación versionada y referencia a la fila cruda. El DW es una proyección reproducible, nunca la única copia de la evidencia. El servicio de compatibilidad se usa en cálculos, filtros y exportaciones.

## Despliegue local previsto

```mermaid
flowchart TB
    N[Navegador local] --> HTTP[Laragon Apache o Nginx]
    HTTP --> PUB[public/index.php]
    PUB --> PHP[PHP y Laravel]
    PHP --> SQL[(MySQL InnoDB)]
    PHP --> PRIVATE[storage/app/private]
    PHP --> LOG[Logs y auditoría]
    VITE[Vite en desarrollo] --> N
    BUILD[Assets compilados en public/build] --> HTTP
```

El DocumentRoot debe ser `C:\laragon\www\ResiduosSolidos\public`. No se servirá la raíz del repositorio. MySQL no se expondrá públicamente. Las credenciales se configuran localmente en `.env`, sin publicarlas. Para despliegue externo se verificarán HTTPS, correo, respaldos, permisos, logs y tareas programadas.

## Organización Laravel propuesta

```text
app/Http/Controllers/{Documents,Imports,Operations,BI,Reports,Admin}
app/Http/Requests/
app/Models/
app/Policies/
app/Services/{Documents,Imports,Quality,Indicators,Warehouse,Reports}
app/Jobs/
app/Enums/
database/{migrations,seeders,factories}
resources/views/{auth,layouts,documents,imports,operations,bi,reports,admin}
resources/{css,js}
tests/{Unit,Feature}
docs/01-analisis ... docs/09-evidencias
```

No se creará una carpeta o abstracción sin una responsabilidad concreta. Los Controllers coordinan solicitudes; los servicios concentran transacciones y reglas; las Policies verifican acceso a archivos y entidades; los Models declaran relaciones y casts explícitos. El uso de convenciones de bootstrap, middleware y providers deberá ajustarse al esqueleto de Laravel 13 en Fase 2, sin copiar mecánicamente la estructura de Laravel 10.

## Secuencia de importación propuesta

```mermaid
sequenceDiagram
    actor A as Analista
    participant C as ImportController
    participant P as Disco privado
    participant V as ImportPreviewService
    participant DB as MySQL
    A->>C: Subir archivo autorizado
    C->>C: Autorización y Form Request
    C->>P: Conservar binario y hash
    C->>V: Extraer cinco hojas
    V->>DB: Guardar staging y excepciones
    V-->>A: Previsualización y restricciones
    A->>C: Confirmar snapshot validado
    C->>DB: Bloquear lote y comenzar transacción
    C->>DB: Cargar curado y dimensiones/hechos
    alt Carga válida
        C->>DB: Confirmar y marcar completado
        C-->>A: Reporte de importación
    else Error
        C->>DB: Revertir transacción
        C->>DB: Guardar fallo fuera de la transacción revertida
        C-->>A: Error verificable y original conservado
    end
```

Los diagramas UML detallados de casos de uso, actividades, componentes internos y pantallas se ampliarán junto al código en las fases indicadas en el plan. Este documento no afirma que los componentes ya estén implementados.
