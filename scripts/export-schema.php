<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

// Exporta solo estructura: no incluye cuentas, contraseñas, tokens ni datos.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$connection = DB::connection();
if ($connection->getDriverName() !== 'mysql') {
    throw new RuntimeException('Este exportador requiere MySQL.');
}
$tables = ['users', 'password_reset_tokens', 'failed_jobs', 'personal_access_tokens',
    'import_batches', 'sources', 'documentary_records', 'documentary_routes',
    'quality_issues', 'evidence_files', 'audit_events',
    'incident_types', 'operational_zones', 'incident_statuses', 'incident_status_transitions', 'operational_settings',
    'critical_sites', 'incidents', 'incident_evidence', 'incident_history', 'critical_site_history', 'migrations'];
$sql = "-- Amarilis Ambiental: estructura real de la primera implementación.\n"
    ."-- Solo esquema, sin datos ni credenciales. Para una base nueva.\n"
    ."-- La base actual ya tiene migraciones; no necesita ejecutar este archivo.\n"
    ."CREATE DATABASE IF NOT EXISTS `amarilis_bi` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n"
    ."USE `amarilis_bi`;\n\n";
foreach ($tables as $table) {
    $definition = (array) $connection->selectOne("SHOW CREATE TABLE `{$table}`");
    $create = $definition['Create Table'];
    $create = preg_replace('/ AUTO_INCREMENT=\d+\b/', '', $create);
    $sql .= $create.";\n\n";
}
$directory = database_path('schema');
File::ensureDirectoryExists($directory);
file_put_contents($directory.'/amarilis_bi.sql', $sql);
echo "Estructura MySQL exportada a database/schema/amarilis_bi.sql (sin datos).\n";
