# Implementación inicial y evidencia de pruebas

Entrega del 16 de noviembre de 2026, America/Lima. Primera versión funcional local; el proyecto completo continúa en desarrollo. El diseño de Fase 1 se mantiene como objetivo y no se confunde con la implementación parcial descrita aquí.

## Componentes efectivamente implementados

Laravel 13.42.0 y PHP 8.4.27 portable, conexión MySQL a una base dedicada, migraciones y modelos documentales, autenticación Fortify, cuatro roles técnicos con Gates, administración de usuarios, perfil y auditoría. Importador del Excel suministrado con validación, zona original, previa, confirmación atómica e idempotencia. Consulta paginada y trazabilidad, rutas documentadas, fuentes y descarga autenticada de adjuntos. Panel con vistas ejecutiva, operativa y calidad, 16 cálculos BCMath a escala 30, Chart.js y filtros. Exportación tabular PDF/Excel con contexto y operadores.

La implementación inicial simplifica el diseño de Fase 1: el rol está en `users.role`, las cinco hojas crudas se conservan en el JSON del lote y las fuentes representan una única versión del corpus. Las relaciones Eloquent y FK mantienen la procedencia del lote y cada registro. No hay alta de observaciones operativas ni gestión de revisiones documentales. Cualquier extensión deberá migrar estas estructuras sin borrar evidencia. Las dimensiones y hechos DW continúan pendientes.

## Verificaciones ejecutadas

| Verificación | Resultado observado |
|---|---|
| Arranque de Laravel | 13.42.0 bajo PHP 8.4.27; Laragon PHP antiguo conservado |
| Conexión y migración MySQL | Correctas en `amarilis_bi`; servidor 8.0.30; no se modificaron otras BD de usuario |
| Carga de evidencia | 41 registros, 11 rutas, 7 fuentes, 9 observaciones y 4 archivos; SHA de privados coincide con originales |
| PHPUnit SQLite final | 15 pruebas aprobadas, 124 aserciones; incluye precisión de t/día, caducidad de token y administración de cuentas |
| PHPUnit MySQL aislado final | 15 pruebas aprobadas, 124 aserciones en `amarilis_bi_test` |
| Cálculos de referencia | Las 16 fórmulas contrastadas con referencia independiente; cota >262,27 conservada |
| Fallo inducido | Fuentes y registros curados revertidos; original y lote fallido conservados; reintento completado |
| Sesiones y permisos | Consultor no importa/administra; suspendido no inicia sesión; administrador no se quita su propio acceso |
| Recuperación | Token funciona una vez y no se reutiliza; entrega SMTP real no probada |
| Exportación | XLSX reabierto con tres hojas y operador `>` en R35; PDF válido con procedencia |
| Navegador | Inicio/cierre de sesión de QA, menú responsive, filtros y calidad; sin errores/warnings JavaScript observados |
| CSRF HTTP real | POST a /login sin token devuelve 419 |
| PDF descargado desde UI | Cuatro páginas renderizadas e inspeccionadas; registros, cotas, discrepancia EDUCCA, corte y hash presentes |
| Compilación | Vite 8.3.8 compiló Tailwind y Chart.js correctamente; sin fuentes remotas en la configuración final |
| Dependencias | Composer audit sin avisos/paquetes abandonados; npm audit sin vulnerabilidades al instalar |

La revisión del PDF detectó que R33 se mostraba con dos decimales; se corrigió la presentación de t/día a tres decimales para conservar 100,216. El dato persistido siempre conservó sus decimales. El reporte final se volvió a generar y renderizar; se verificó la página afectada y la aserción de precisión quedó aprobada.

## Aislamiento y acceso

`amarilis_bi` conserva solo evidencia real y ningún usuario ficticio. El administrador se crea mediante `app:admin` con contraseña ingresada interactivamente. `amarilis_bi_test` aloja pruebas transaccionales y `amarilis_bi_qa` la cuenta ficticia identificada para revisión visual. La cuenta QA no permite acceder a la base principal. Se detendrán los servidores temporales de revisión al cerrar esta entrega; el arranque está disponible con `Iniciar.ps1`.

Los intentos iniciales que fallaron fueron corregidos y reprobados: sintaxis de una directiva Blade, uso de IDs 1 fijos en pruebas MySQL, aislamiento de sesión al alternar cuentas de prueba y ejecución de Pint con opción Git en un checkout sin repositorio. No se registran como pruebas aprobadas. Las salidas finales deben consultarse en los archivos de evidencia finales.

## Evidencia consultable

- `instalacion-programa.json`: versiones, conexión, conteos y hashes reales.
- `programa-panel.jpg` y `programa-movil.jpg`: capturas auténticas de QA, con datos documentales y cuenta ficticia identificada.
- `reporte-generado-verificacion.json`: extracción y verificaciones del PDF de aplicación.
- `pruebas-sqlite-final.txt` y `pruebas-mysql-final.txt`: salidas finales de PHPUnit.
- `dependencias-final.json`: auditoría final de Composer.

El alcance pendiente y el manual de ejecución están en `../07-manuales/programa-local.md`. No se atribuye a esta implementación una mejora ambiental, una validación institucional ni un sistema terminado.
