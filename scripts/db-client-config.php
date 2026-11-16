<?php
/**
 * Used by scripts/deploy.sh before migrations: writes a MySQL client option file with the exact
 * connection settings Laravel uses (read through Laravel's own .env parsing, so passwords with
 * #, quotes, $ or backslashes and Windows line endings all work), and prints the database name.
 *
 * Usage (from the application folder): php scripts/db-client-config.php /path/to/option-file
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$c = config('database.connections.'.config('database.default'));
$quote = fn ($v) => '"'.addcslashes((string) $v, "\\\"").'"'; // option-file quoting: escape \ and "

$lines = ['[client]', 'user='.$quote($c['username'] ?? ''), 'password='.$quote($c['password'] ?? '')];
if (!empty($c['unix_socket'])) $lines[] = 'socket='.$quote($c['unix_socket']);
else { $lines[] = 'host='.$quote($c['host'] ?? 'localhost'); $lines[] = 'port='.$quote($c['port'] ?? 3306); }

file_put_contents($argv[1], implode("\n", $lines)."\n");
echo $c['database'];
