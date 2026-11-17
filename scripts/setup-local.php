<?php

// Solo preparación local: no imprime ni incluye credenciales en argumentos.
require __DIR__.'/../vendor/autoload.php';

$root = dirname(__DIR__);
$envPath = $root.'/.env';
if (! is_file($envPath)) {
    copy($root.'/.env.example', $envPath);
}
$content = file_get_contents($envPath);
$settings = Dotenv\Dotenv::parse($content);
$host = $settings['DB_HOST'] ?? '127.0.0.1';
$port = $settings['DB_PORT'] ?? '3306';
if (! in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
    fwrite(STDERR, "Este asistente solo prepara una base MySQL local.\n");
    exit(1);
}
try {
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $settings['DB_USERNAME'] ?? 'root', $settings['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS amarilis_bi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
} catch (PDOException $exception) {
    fwrite(STDERR, 'No se pudo preparar MySQL local. Inicie MySQL en Laragon y revise .env. Código: '.$exception->getCode()."\n");
    exit(1);
}
$changes = [
    'APP_NAME' => '"Amarilis Ambiental"', 'APP_ENV' => 'local', 'APP_DEBUG' => 'false',
    'APP_URL' => 'http://127.0.0.1:8000', 'APP_LOCALE' => 'es', 'APP_FALLBACK_LOCALE' => 'es',
    'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'amarilis_bi', 'SESSION_DRIVER' => 'file',
    'CACHE_STORE' => 'file', 'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'log',
];
if (empty($settings['APP_KEY'])) {
    $changes['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
}
foreach ($changes as $key => $value) {
    if (preg_match('/^'.preg_quote($key, '/').'=/m', $content)) {
        $content = preg_replace_callback('/^'.preg_quote($key, '/').'=.*$/m', fn () => $key.'='.$value, $content);
    } else {
        $content .= "\n{$key}={$value}\n";
    }
}
file_put_contents($envPath, $content);
echo "MySQL local disponible. Base dedicada: amarilis_bi. Configuración actualizada sin mostrar secretos.\n";
