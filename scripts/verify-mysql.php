<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Crea únicamente bases de pruebas dedicadas. No modifica tablas de amarilis_bi.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$pdo = DB::connection()->getPdo();
foreach (['amarilis_bi_test', 'amarilis_bi_qa'] as $database) {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$database} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}
echo "Bases MySQL aisladas preparadas para pruebas y revisión visual.\n";
