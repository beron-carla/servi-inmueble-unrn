<?php

declare(strict_types=1);

/**
 * Verificación de entorno para ServiInmueble 2026.
 *
 * Uso (Windows / XAMPP):
 *   C:\xampp\php\php.exe scripts\check_env.php
 *
 * Uso (WSL):
 *   /mnt/c/xampp/php/php.exe scripts/check_env.php
 */

$root = dirname(__DIR__);

/** @var list<array{label: string, ok: bool, detail: string, warning: bool}> $checks */
$checks = [];

/**
 * @param list<array{label: string, ok: bool, detail: string, warning: bool}> $checks
 */
function add_check(array &$checks, string $label, bool $ok, string $detail = '', bool $warning = false): void
{
    $checks[] = [
        'label'   => $label,
        'ok'      => $ok,
        'detail'  => $detail,
        'warning' => $warning,
    ];
}

/**
 * Lee pares clave=valor de un archivo .env sin depender de CodeIgniter.
 *
 * @return array<string, string>
 */
function read_env(string $path): array
{
    if (! is_file($path)) {
        return [];
    }

    $values = [];

    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (! preg_match('/^([A-Za-z0-9_.]+)\s*=\s*(.*)$/', $line, $matches)) {
            continue;
        }

        $value = trim($matches[2]);

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];

            if (($first === "'" && $last === "'") || ($first === '"' && $last === '"')) {
                $value = substr($value, 1, -1);
            }
        }

        $values[$matches[1]] = $value;
    }

    return $values;
}

// ---------------------------------------------------------------------
// PHP y extensiones
// ---------------------------------------------------------------------

add_check($checks, 'PHP >= 8.2', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION);

foreach (['intl', 'mbstring', 'mysqli', 'openssl', 'json', 'curl'] as $extension) {
    $loaded = extension_loaded($extension);

    add_check($checks, "Extensión {$extension}", $loaded, $loaded ? 'cargada' : 'faltante');
}

// ---------------------------------------------------------------------
// Directorios y archivos base
// ---------------------------------------------------------------------

foreach (['writable', 'writable/cache', 'writable/logs', 'writable/session', 'writable/uploads'] as $dir) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir);
    $ok   = is_dir($path) && is_writable($path);

    add_check($checks, "Directorio {$dir} escribible", $ok, is_dir($path) ? 'ok' : 'no existe');
}

foreach (['vendor/autoload.php', 'composer.lock', '.env', 'spark'] as $file) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
    $ok   = is_file($path);

    add_check($checks, "Archivo {$file}", $ok, $ok ? 'ok' : 'no existe');
}

// ---------------------------------------------------------------------
// Variables de entorno
// ---------------------------------------------------------------------

$env = read_env($root . DIRECTORY_SEPARATOR . '.env');

add_check($checks, '.env define app.baseURL', ($env['app.baseURL'] ?? '') !== '', $env['app.baseURL'] ?? 'no definido');
add_check($checks, '.env define database.default.database', ($env['database.default.database'] ?? '') !== '', $env['database.default.database'] ?? 'no definido');
add_check($checks, '.env define database.tests.database', ($env['database.tests.database'] ?? '') !== '', $env['database.tests.database'] ?? 'no definido');
add_check($checks, '.env define SEED_ADMIN_EMAIL', ($env['SEED_ADMIN_EMAIL'] ?? '') !== '', $env['SEED_ADMIN_EMAIL'] ?? 'no definido');

// ---------------------------------------------------------------------
// MariaDB
// ---------------------------------------------------------------------

if (! extension_loaded('mysqli')) {
    add_check($checks, 'Conexión a MariaDB', false, 'mysqli no disponible');
} else {
    mysqli_report(MYSQLI_REPORT_OFF);

    $host   = $env['database.default.hostname'] ?? 'localhost';
    $user   = $env['database.default.username'] ?? 'root';
    $pass   = $env['database.default.password'] ?? '';
    $port   = (int) ($env['database.default.port'] ?? 3306);
    $mainDb = $env['database.default.database'] ?? 'serviinmueble';
    $testDb = $env['database.tests.database'] ?? 'serviinmueble_test';

    $conn = @new mysqli($host, $user, $pass, '', $port);

    if ($conn->connect_errno) {
        add_check($checks, 'Conexión a MariaDB', false, $conn->connect_error ?? 'error desconocido');
    } else {
        add_check($checks, 'Conexión a MariaDB', true, "{$host}:{$port}");

        foreach ([$mainDb => 'desarrollo', $testDb => 'tests'] as $db => $tipo) {
            $sql    = "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '" . $conn->real_escape_string($db) . "'";
            $result = $conn->query($sql);
            $exists = $result !== false && $result->num_rows > 0;

            add_check($checks, "Base {$db} ({$tipo})", $exists, $exists ? 'existe' : 'no existe');
        }

        $sql    = "SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($mainDb) . "' AND TABLE_TYPE = 'BASE TABLE'";
        $result = $conn->query($sql);
        $tables = $result !== false ? (int) $result->fetch_assoc()['total'] : 0;

        add_check($checks, 'Tablas en ' . $mainDb, $tables >= 11, $tables . ' tablas');

        $sql    = "SELECT COUNT(*) AS total FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = '" . $conn->real_escape_string($mainDb) . "'";
        $result = $conn->query($sql);
        $triggers = $result !== false ? (int) $result->fetch_assoc()['total'] : 0;

        add_check($checks, 'Triggers en ' . $mainDb, $triggers >= 2, $triggers . ' triggers');

        $conn->close();
    }

    // Mailpit es opcional: solo se necesita para probar el envío de emails.
    $mailpit = @fsockopen('127.0.0.1', 1025, $errno, $errstr, 0.5);

    if (is_resource($mailpit)) {
        fclose($mailpit);
        add_check($checks, 'Mailpit (SMTP 1025)', true, 'disponible');
    } else {
        add_check($checks, 'Mailpit (SMTP 1025)', false, 'no disponible (solo necesario para probar emails)', true);
    }
}

// ---------------------------------------------------------------------
// Salida
// ---------------------------------------------------------------------

$ok   = 0;
$fail = 0;
$warn = 0;

echo PHP_EOL;
echo 'ServiInmueble 2026 · Verificación de entorno' . PHP_EOL;
echo str_repeat('=', 46) . PHP_EOL;

foreach ($checks as $check) {
    if ($check['ok']) {
        $tag = '[OK]  ';
        $ok++;
    } elseif ($check['warning']) {
        $tag = '[WARN]';
        $warn++;
    } else {
        $tag = '[FAIL]';
        $fail++;
    }

    $detail = $check['detail'] !== '' ? ' — ' . $check['detail'] : '';
    echo $tag . ' ' . $check['label'] . $detail . PHP_EOL;
}

echo str_repeat('-', 46) . PHP_EOL;
echo sprintf('Resultado: %d OK, %d fallas, %d advertencias%s', $ok, $fail, $warn, PHP_EOL);

exit($fail === 0 ? 0 : 1);
