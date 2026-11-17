# Amarilis Ambiental

Primera versión local del sistema de inteligencia ambiental para el análisis documental de residuos sólidos de Amarilis, Huánuco. Laravel 13, PHP 8.4, MySQL, Blade, Tailwind y Chart.js.

## Abrir en esta instalación

Inicie MySQL en Laragon y ejecute desde PowerShell:

```powershell
Set-Location 'C:\laragon\www\ResiduosSolidos'
& '.\.tools\php84\php.exe' artisan app:admin
.\Iniciar.ps1
```

Cree el administrador una sola vez, con su propio correo y contraseña. Después abra [http://127.0.0.1:8000](http://127.0.0.1:8000). Para las siguientes sesiones basta con `Iniciar.ps1`. No hay contraseña predeterminada.

## Funciones implementadas

- Inicio/cierre de sesión, roles técnicos, usuarios, perfil, recuperación con token y auditoría.
- Consulta de 41 registros y 11 rutas documentales, con fuente, período, unidad, operador y restricciones.
- Descarga autenticada de los cuatro adjuntos originales con SHA-256.
- Importación del Excel autorizado con previsualización, confirmación transaccional y control de duplicados.
- Vistas ejecutiva, operativa y de calidad, filtros, gráficos y 16 indicadores reproducibles.
- Reportes tabulares PDF y Excel con procedencia y condiciones de interpretación.
- Incidencias operativas con fotografías privadas, geolocalización, verificación y seguimiento; mapa Leaflet con calor, marcadores y filtros JSON.
- Sitios críticos verificados, asociación de avisos, catálogos configurables y centro de control operativo separado del histórico.

## Documentación

- [Manual de arranque, instalación y uso](docs/07-manuales/programa-local.md).
- [Estado real de implementación y pruebas](docs/09-evidencias/implementacion-inicial.md).
- [Índice de análisis, requisitos y diseño](docs/README.md).
- [Modelo inicial completo propuesto](docs/04-base-de-datos/modelo-inicial.md).

El MySQL local es 8.0.30 y la actualización del servidor a 8.4 LTS continúa pendiente. El PHP portable evita utilizar el PHP 8.1 de Laragon. La base dedicada es `amarilis_bi`; `.env`, `.tools` y los archivos privados no deben publicarse.

## Verificar o recompilar

```powershell
& '.\.tools\php84\php.exe' artisan test --compact
.\Compilar.ps1
```

Las pruebas normales usan SQLite en memoria. Las pruebas MySQL se ejecutan exclusivamente en `amarilis_bi_test`, siguiendo el manual. Las cuentas ficticias pertenecen a pruebas y QA; no se incorporan a la base principal.

Esta versión incorpora incidencias y sitios críticos operativos. Vehículos, rutas operativas, pesajes, seguimiento de decisiones, versionado documental, Data Warehouse, Power BI y despliegue municipal siguen pendientes. Los mapas usan OpenStreetMap con atribución; el proveedor de teselas y geocodificación requiere conexión a internet. La evidencia documental no demuestra por sí misma una mejora ambiental u operativa.
