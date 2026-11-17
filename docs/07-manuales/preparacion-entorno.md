# Preparación del entorno Windows con Laragon

Documento de planificación de Fase 2. **Los comandos de instalación y actualización de esta guía no se ejecutaron en Fase 1.** No es todavía un manual de instalación del sistema terminado. La renovación del esqueleto y sus pruebas se harán antes de desarrollar módulos.

## Diagnóstico ejecutado

| Componente | Observado el 16/11/2026 | Acción propuesta |
|---|---|---|
| Proyecto | Laravel Framework 10.50.4; composer.json exige ^10.10 | Adaptar a Laravel 13; no instalar librerías actuales sobre Laravel 10 |
| PHP Laragon | 8.1.10 ZTS x64 | Instalar y seleccionar PHP 8.4 x64 mantenido, compatible con servidor Laragon |
| Composer Laragon | 2.4.1 | Actualizar a Composer 2 estable y comprobar requisitos |
| Cliente MySQL | 8.0.30 Win64 | Planificar MySQL 8.4 LTS, con respaldo/restauración de cualquier BD existente |
| Node Laragon | 18.8.0; npm 8.18.0 | Instalar Node 24 LTS y su npm |
| Git Laragon | 2.37.3.windows.1 | Revisar actualización; no hay repositorio .git inicializado aquí |
| PATH de esta sesión | php, composer, mysql y npm no resueltos; node apunta al runtime de Codex | Usar terminal Laragon y verificar rutas; no confundir runtime de Codex con entorno del proyecto |

El comando `mysql --version` informa del cliente instalado, no verifica servidor, credenciales o conexión. No se leyó el contenido de `.env` ni se probó una conexión a MySQL. PHP actual incluye BCMath, PDO MySQL, curl, DOM, fileinfo, GD, intl, mbstring, OpenSSL, XML y ZIP; al cambiar PHP hay que comprobarlas otra vez.

## Herramientas que hacen falta

Laravel 13 requiere PHP >=8.3 y soporta PHP 8.3–8.5; su tabla oficial sitúa el fin de seguridad el 17/03/2028. Laravel 10 terminó seguridad el 04/02/2025. Se propone PHP 8.4 con parche estable actual, Laravel 13 y MySQL 8.4 LTS. Estas son decisiones técnicas, no resultados de compatibilidad ya ensayados. Véanse las [notas oficiales de Laravel](https://laravel.com/docs/13.x/releases), las [versiones PHP mantenidas](https://www.php.net/supported-versions.php) y la [selección de versión MySQL](https://dev.mysql.com/doc/refman/8.4/en/which-version.html).

Node 24 LTS evita continuar con Node 18; comprobar estado y parche al instalar en el [calendario oficial Node](https://github.com/nodejs/Release). Vite publica requisitos de Node 20.19+ o 22.12+; Node 24 satisface ese piso, sujeto a los engines de la versión elegida ([Vite](https://vite.dev/guide/)). El esqueleto Laravel elegirá una combinación Vite/plugin compatible, que debe quedar bloqueada y compilada.

Descargar PHP para Windows desde la distribución oficial y elegir TS/NTS según Apache módulo o FastCGI de Laragon, con arquitectura x64 y runtime Visual C++ que corresponda. Seleccionar la nueva versión desde Laragon y reiniciar servicios. Instalar Node 24 LTS desde su distribución oficial. Instalar MySQL 8.4 como instancia nueva o migración ensayada; **no apuntarlo directamente al directorio de datos 8.0**. Respaldo y prueba de restauración preceden al cambio. Composer dispone de [instalador Windows y guía oficial](https://getcomposer.org/download/); no copiar un hash de instalador antiguo.

| Librería | Uso y estado | Instalación prevista |
|---|---|---|
| laravel/fortify | Backend oficial auth con vistas Blade propias; compatible 13 según metadatos consultados | Fase 4 |
| phpoffice/phpspreadsheet | Lectura y exportación XLSX, exige PHP >=8.2 y extensiones | Fase 6/8 |
| barryvdh/laravel-dompdf | PDF desde Blade; metadatos admiten Illuminate 13 | Fase 8 |
| Tailwind CSS 4 y @tailwindcss/vite | Estilos con Vite; adaptar CSS y plugin al esqueleto | Fase 4 |
| Chart.js | Gráficos BI, instalado localmente, sin depender de CDN para uso local | Fase 7 |
| PHPUnit y Pint | Pruebas y formato, según esqueleto compatible | Fases 2–9 |
| Alpine.js | Opcional si los diálogos lo necesitan | Fase 4 o posterior |

No hacen falta Python para ejecutar Laravel, Docker, Redis, un framework SPA ni servicio de mapas. Python se usó aquí solo para inspección documental. Power BI y gateway son opcionales y pendientes de viabilidad.

Los manifiestos de [Fortify](https://github.com/laravel/fortify/blob/1.x/composer.json), [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet/blob/master/composer.json) y [Dompdf Laravel](https://github.com/barryvdh/laravel-dompdf/blob/master/composer.json) se consultaron como evidencia de restricciones declaradas. Una rama de desarrollo no garantiza que un tag estable concreto las incluya: Composer deberá resolver una versión estable, ejecutar auditoría y probarla antes de adoptarla. No se instalará `dev-master` ni se ignorarán requisitos de plataforma.

## Comandos de diagnóstico reproducibles ahora

PowerShell, sin depender del PATH:

```powershell
Set-Location 'C:\laragon\www\ResiduosSolidos'
& 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe' -v
& 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe' -m
& 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe' artisan --version
& 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe' 'C:\laragon\bin\composer\composer.phar' --version
& 'C:\laragon\bin\nodejs\node-v18\node.exe' --version
& 'C:\laragon\bin\nodejs\node-v18\npm.cmd' --version
& 'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe' --version
```

## Comandos después de instalar y seleccionar las herramientas nuevas

Abrir una terminal nueva de Laragon. Primero verificar que los ejecutables corresponden a las versiones seleccionadas. Si no resuelven, corregir PATH de Laragon antes de continuar.

```powershell
Set-Location 'C:\laragon\www\ResiduosSolidos'
Get-Command php, composer, node, npm, mysql, git | Select-Object Name,Source
php -v
php --ini
php -m
composer --version
node --version
npm.cmd --version
mysql --version
composer diagnose
```

Si Composer sigue antiguo, desde terminal Laragon con PHP nuevo:

```powershell
php 'C:\laragon\bin\composer\composer.phar' self-update --stable
php 'C:\laragon\bin\composer\composer.phar' --version
```

No ejecutar `composer create-project ... .` en el proyecto existente. En Fase 2 se puede obtener un esqueleto de referencia dentro de una carpeta de preparación nueva, sin sobrescribir la aplicación ni ejecutar sus scripts automáticamente:

```powershell
Set-Location 'C:\laragon\www\ResiduosSolidos'
if (Test-Path -LiteralPath '.phase2\laravel13') { throw 'La referencia ya existe; revisarla antes de continuar.' }
composer create-project laravel/laravel '.phase2/laravel13' '^13.0' --no-scripts --no-interaction
Set-Location '.phase2\laravel13'
composer validate --strict
composer check-platform-reqs
composer audit
php artisan --version
```

La carpeta `.phase2/laravel13` es solo referencia temporal: no es otro proyecto municipal publicado. Antes de instalar auth, BI o exportadores, se adaptarán sus archivos al checkout actual revisando diferencias, conservando `docs/`, lockfiles y cambios existentes. Esta adaptación no se automatiza con comandos de copia masiva. Si la revisión descubre código de dominio adicional, se seguirá la guía de actualización por versión de Laravel.

Ya con Laravel 13 integrado en la raíz, los siguientes comandos revisan las dependencias **antes** de instalarlas:

```powershell
Set-Location 'C:\laragon\www\ResiduosSolidos'
composer require laravel/fortify phpoffice/phpspreadsheet barryvdh/laravel-dompdf --dry-run
composer show laravel/fortify --all
composer show phpoffice/phpspreadsheet --all
composer show barryvdh/laravel-dompdf --all
```

Si la resolución estable es compatible, instalar en sus fases correspondientes, conservar lockfiles y ejecutar verificaciones:

```powershell
composer require laravel/fortify phpoffice/phpspreadsheet barryvdh/laravel-dompdf
composer check-platform-reqs
composer audit
npm.cmd install
npm.cmd install chart.js
npm.cmd install --save-dev tailwindcss '@tailwindcss/vite'
npm.cmd audit
```

Estos comandos instalan dependencias; las vistas Fortify, permisos, CSS/Vite, gráficos y exportadores requieren implementación posterior. La instalación de Fortify y su provider se realizará según la [guía oficial](https://laravel.com/docs/13.x/fortify). Tailwind se integrará según [Vite y Tailwind](https://tailwindcss.com/docs/installation/using-vite), sin mezclar configuración de Tailwind 3 y 4. Chart.js usa el [paquete oficial](https://www.chartjs.org/docs/latest/getting-started/installation.html).

## Configuración y arranque posteriores

Se configurarán DB_CONNECTION=mysql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME y DB_PASSWORD con valores reales elegidos localmente. No se usará cuenta root para el servicio de la aplicación. La BD de pruebas tendrá credenciales independientes. El correo de desarrollo deberá permitir comprobar recuperación; un log no acredita entrega real de correo.

Una vez existan configuración y migraciones verificadas:

```powershell
php artisan config:clear
php artisan migrate:status
php artisan migrate --pretend
php artisan migrate
php artisan test
php vendor/bin/pint --test
npm.cmd run build
php artisan serve --host=127.0.0.1 --port=8000
```

`serve` es alternativa de desarrollo. Para Laragon, configurar el host local con DocumentRoot `C:\laragon\www\ResiduosSolidos\public`, reiniciar servidor y verificar que `.env` y originales no sean accesibles. El nombre de host local debe comprobarse en la configuración real; no se afirma que un dominio determinado ya exista. En otra terminal, usar `npm.cmd run dev` si se necesita Vite en desarrollo.

No ejecutar `migrate:fresh`, sembrar fábricas de prueba ni borrar directorios de datos durante la preparación. No volver a generar APP_KEY de una instalación con información cifrada sin un plan de migración. La conexión MySQL, recuperación de contraseñas y arranque web continúan pendientes de prueba.
