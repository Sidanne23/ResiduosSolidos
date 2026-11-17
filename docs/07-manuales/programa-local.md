# Ejecutar la primera versión del programa

Esta versión ya implementa acceso y roles, gestión de usuarios, perfil, recuperación de contraseña, auditoría, consulta documental, importación del Excel suministrado, tres vistas BI, cálculos y reportes tabulares PDF/Excel. Usa Laravel 13, Blade, Tailwind, Chart.js, Fortify, PhpSpreadsheet y Dompdf. La base local dedicada es `amarilis_bi`.

## Abrir el programa en esta máquina

Iniciar MySQL desde Laragon. En una terminal PowerShell dentro del proyecto:

```powershell
Set-Location 'C:\laragon\www\ResiduosSolidos'
& '.\.tools\php84\php.exe' artisan app:admin
.\Iniciar.ps1
```

El primer comando solicita nombre, correo y contraseña dos veces. La contraseña debe tener al menos 12 caracteres; no se muestra ni se incluye en argumentos. Cree esta cuenta una sola vez. Después abra [el programa local](http://127.0.0.1:8000) e ingrese con sus datos. En usos posteriores basta con `Iniciar.ps1`. Para detenerlo, Ctrl+C en esa terminal.

No existe contraseña universal ni administrador precreado. La base de aplicación no contiene cuentas ficticias. La captura de pantalla se obtuvo con una cuenta identificada como ficticia en la base separada `amarilis_bi_qa`; sus credenciales no abren la aplicación principal.

La máquina conserva el PHP 8.1 y Node 18 de Laragon. `Iniciar.ps1` usa el PHP 8.4 portable en `.tools/php84`, sin depender de esa instalación antigua. Para usar Apache de Laragon directamente debe configurar una versión PHP compatible y el DocumentRoot `C:\laragon\www\ResiduosSolidos\public`; esa configuración de Apache no se cambió. El arranque verificado de esta entrega fue con el servidor local de PHP actualizado, conectado al MySQL de Laragon.

## Qué se puede usar

| Función | Ruta y comportamiento |
|---|---|
| Inicio/cierre de sesión | `/login`; sesiones y hash de Laravel, limitación de intentos, registro público deshabilitado |
| Panel | `/panel`; vistas ejecutiva, operativa y calidad; filtros de fuente, año, proceso, tipo, corte y búsqueda |
| Registros | `/registros`; consulta paginada R01–R41 y detalle con fila original, localizador y SHA-256 |
| Rutas | `/rutas`; once rutas M24, zonas agrupadas, horarios y registros del cuadro, sin geolocalización supuesta |
| Fuentes | `/fuentes`; siete fichas y descarga autenticada de los cuatro adjuntos originales |
| Importaciones | `/importaciones`; administrador/analista, previa y confirmación transaccional; binario repetido no duplica |
| Usuarios | `/usuarios`; solo administrador; cuatro roles técnicos, suspensión y cambio de contraseña |
| Perfil | `/perfil`; exige contraseña actual para actualizar datos; rol nunca se acepta desde este formulario |
| Auditoría | `/auditoria`; solo administrador; accesos, importaciones, exportaciones y cambios de cuentas |
| Reportes | Botones Excel/PDF de panel y registros; conservan selección, fuente, corte, fórmula y restricciones |

Los roles administrador y analista pueden importar; todos los usuarios activos pueden consultar y exportar este corpus documental. El registrador aún no tiene formularios de operaciones posteriores: conserva acceso de consulta hasta que esos módulos existan. Suspender una cuenta impide acceso a las rutas protegidas y nuevos inicios de sesión.

## Recuperación de contraseña

Fortify genera tokens expirables y de un solo uso. En este entorno `MAIL_MAILER=log`: el enlace queda en `storage/logs/laravel.log`, que puede contener un token de recuperación y debe mantenerse privado. No se afirma entrega a correo real. Para habilitarla, configurar un SMTP autorizado en `.env`, verificar remitente y probar entrega; luego limpiar la caché de configuración. El administrador también puede asignar una contraseña nueva desde Usuarios y entregarla por un canal seguro.

## Evidencia e importación

Los cuatro adjuntos se copiaron a `storage/app/private`, con sus SHA-256 verificados. No se modificaron los archivos de Descargas. R01–R41 conservan sus valores, unidades, operadores y períodos; las rutas mantienen un espacio de identificación distinto de los indicadores.

Para reproducir la carga con los originales autorizados:

```powershell
& '.\.tools\php84\php.exe' artisan evidence:load 'C:\Users\sidan\Downloads'
```

Es idempotente: una segunda ejecución no duplica registros. El flujo web permite elegir el mismo Excel, revisar las 41 filas y las excepciones, y confirmar solo después de validarlo. Esta primera versión admite exclusivamente el SHA-256 del libro entregado y el mapeo `amarilis-1`; las nuevas versiones y plantillas necesitan implementación adicional. No se sobrescribe un corpus existente con un archivo diferente.

El original, las cinco hojas extraídas, sus fórmulas sin ejecutar y los datos curados se mantienen separados. El archivo Excel se incorpora como transcripción documental, no como PDF municipal original. Los hashes de documentos municipales mencionados en las notas originales no se presentan como hashes de binarios recibidos.

## Recompilar y ejecutar pruebas

```powershell
.\Compilar.ps1
& '.\.tools\php84\php.exe' artisan test --compact
& '.\.tools\php84\php.exe' vendor/bin/pint --test app routes tests database config scripts
& '.\.tools\php84\php.exe' '.tools/composer.phar' check-platform-reqs
& '.\.tools\php84\php.exe' '.tools/composer.phar' audit
```

Las pruebas normales usan SQLite en memoria. Para probar MySQL aislado:

```powershell
& '.\.tools\php84\php.exe' scripts/verify-mysql.php
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='amarilis_bi_test'
& '.\.tools\php84\php.exe' artisan test --compact
Remove-Item Env:DB_CONNECTION,Env:DB_DATABASE
```

No dirija las pruebas a `amarilis_bi`: usan migraciones y transacciones de pruebas. El Excel fixture es una copia exacta del adjunto público autorizado, identificada como documental; las cuentas y entradas alteradas de prueba permanecen aisladas.

## Preparar otra instalación Windows

Requiere MySQL de Laragon iniciado y acceso a internet para distribuciones oficiales y registros de paquetes. Copiar el proyecto con sus lockfiles y documentación; `.tools`, `vendor`, `node_modules` y archivos privados son locales y están excluidos de Git. Configurar `.env` a partir de `.env.example` con las credenciales de MySQL local que correspondan; nunca copiarlas a un repositorio.

```powershell
.\scripts\Preparar.ps1
& '.\.tools\php84\php.exe' artisan evidence:load 'CARPETA_CON_LOS_CUATRO_ADJUNTOS'
& '.\.tools\php84\php.exe' artisan app:admin
.\Iniciar.ps1
```

`Preparar.ps1` descarga PHP 8.4 y Node 24 desde sus distribuidores oficiales, verifica SHA, instala Composer verificado, resuelve los lockfiles, prepara la BD dedicada sin borrar otras bases y compila. Si PowerShell bloquea scripts, puede ejecutar el archivo con una excepción limitada a ese proceso según la política local; no necesita desactivar globalmente la protección. El ensayo desde una máquina limpia permanece pendiente: en esta máquina se ejecutaron y comprobaron los pasos individuales de preparación.

## Límites de esta versión

La consulta BI trabaja sobre tablas documentales normalizadas del corpus inicial. Los tres hechos y dimensiones DW del diseño de Fase 1 aún no están creados; no existe conexión Power BI. Tampoco hay versionado completo de fuentes, formularios operativos, mapas, respaldo/restauración automatizados, gráficos incrustados en PDF, despliegue externo ni evaluación municipal. Los reportes actuales son tabulares. La consulta de rutas muestra el cuadro completo; filtros de día/zona y exportación específica de rutas quedan pendientes.

El servidor MySQL observado es 8.0.30. Su actualización a 8.4 LTS sigue pendiente y requiere respaldo y ensayo independientes para no afectar otras aplicaciones de Laragon. Esta versión local no se presenta como despliegue de producción. Para una entrega de producción se deberá verificar HTTPS, correo, base de mínimo privilegio, restauración, versiones del servidor, concurrencia y toda la aceptación del pedido original.
