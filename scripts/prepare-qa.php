<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// Usuario ficticio exclusivo de amarilis_bi_qa. Nunca ejecutar en la BD de aplicación.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'amarilis_bi_qa') {
    fwrite(STDERR, "Se requiere DB_DATABASE=amarilis_bi_qa.\n");
    exit(1);
}
Artisan::call('migrate', ['--force' => true]);
$user = User::firstOrNew(['email' => 'qa-ficticio@example.test']);
$user->forceFill(['name' => 'Cuenta ficticia QA', 'password' => 'SoloQA!2026-Amarilis-local', 'role' => 'administrador', 'active' => true])->save();
Artisan::call('evidence:load', ['directory' => getenv('USERPROFILE').'/Downloads']);
echo "QA aislado preparado. Credencial ficticia definida en este script, sin cuenta en amarilis_bi.\n";
