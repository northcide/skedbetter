#!/usr/bin/env php
<?php
/**
 * SkedBetter Setup Script
 *
 * Run: php setup.php
 *
 * This script handles initial setup and environment configuration for SkedBetter.
 * It can be run on a fresh install or to reconfigure an existing installation.
 *
 * Requirements:
 *   - PHP >= 8.3 with extensions: mbstring, openssl, pdo, pdo_mysql, tokenizer, xml, ctype, json, bcmath, curl
 *   - MySQL 8.x
 *   - Node.js >= 18 with npm
 *   - Composer 2.x
 *   - Apache with mod_rewrite enabled
 *
 * Last updated: 2026-03-28
 * Stack: Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS + MySQL + FullCalendar 6
 */

// Colors for terminal output
function info($msg)    { echo "\033[34m[INFO]\033[0m $msg\n"; }
function success($msg) { echo "\033[32m[OK]\033[0m $msg\n"; }
function warn($msg)    { echo "\033[33m[WARN]\033[0m $msg\n"; }
function error($msg)   { echo "\033[31m[ERROR]\033[0m $msg\n"; }
function heading($msg) { echo "\n\033[1;36m=== $msg ===\033[0m\n\n"; }
function prompt($msg, $default = '') {
    $d = $default ? " [$default]" : '';
    echo "\033[33m$msg$d:\033[0m ";
    $input = trim(fgets(STDIN));
    return $input !== '' ? $input : $default;
}

heading('SkedBetter Setup');

// ── 1. Check PHP version and extensions ─────────────────────────────────

heading('1. Checking PHP requirements');

$phpVersion = PHP_VERSION;
if (version_compare($phpVersion, '8.3.0', '<')) {
    error("PHP 8.3+ required. Found: $phpVersion");
    exit(1);
}
success("PHP $phpVersion");

$requiredExtensions = ['mbstring', 'openssl', 'pdo', 'pdo_mysql', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'curl'];
$missing = [];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing[] = $ext;
    }
}
if (!empty($missing)) {
    error("Missing PHP extensions: " . implode(', ', $missing));
    echo "Install with: sudo apt install " . implode(' ', array_map(fn($e) => "php-$e", $missing)) . "\n";
    exit(1);
}
success("All required PHP extensions present");

// ── 2. Check external tools ─────────────────────────────────────────────

heading('2. Checking external tools');

$composer = trim(shell_exec('which composer 2>/dev/null') ?? '');
if (!$composer) {
    error("Composer not found. Install: https://getcomposer.org");
    exit(1);
}
success("Composer: " . trim(shell_exec("$composer --version 2>&1")));

$node = trim(shell_exec('which node 2>/dev/null') ?? '');
if (!$node) {
    error("Node.js not found. Install: https://nodejs.org");
    exit(1);
}
$nodeVersion = trim(shell_exec("$node -v 2>&1"));
success("Node.js: $nodeVersion");

$npm = trim(shell_exec('which npm 2>/dev/null') ?? '');
if (!$npm) {
    error("npm not found");
    exit(1);
}
success("npm: " . trim(shell_exec("$npm -v 2>&1")));

$mysql = trim(shell_exec('which mysql 2>/dev/null') ?? '');
if (!$mysql) {
    warn("mysql client not found — you'll need to configure the database manually");
} else {
    success("MySQL client found");
}

// ── 3. Environment configuration ────────────────────────────────────────

heading('3. Environment configuration');

$envFile = __DIR__ . '/.env';
$envExample = __DIR__ . '/.env.example';

if (!file_exists($envFile)) {
    if (file_exists($envExample)) {
        copy($envExample, $envFile);
        info("Created .env from .env.example");
    } else {
        error(".env.example not found");
        exit(1);
    }
}

// Read current env
$env = [];
foreach (file($envFile) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (strpos($line, '=') !== false) {
        [$key, $val] = explode('=', $line, 2);
        $env[trim($key)] = trim($val, '"\'');
    }
}

echo "\nConfigure your environment (press Enter to keep current value):\n\n";

$appName = prompt('App name', $env['APP_NAME'] ?? 'SkedBetter');
$appUrl = prompt('App URL', $env['APP_URL'] ?? 'https://skedbetter.localdev.test');
$appEnv = prompt('Environment (local/production)', $env['APP_ENV'] ?? 'local');

echo "\n";
$dbHost = prompt('Database host', $env['DB_HOST'] ?? '127.0.0.1');
$dbPort = prompt('Database port', $env['DB_PORT'] ?? '3306');
$dbName = prompt('Database name', $env['DB_DATABASE'] ?? 'skedbetter');
$dbUser = prompt('Database username', $env['DB_USERNAME'] ?? 'skedbetter');
$dbPass = prompt('Database password', $env['DB_PASSWORD'] ?? '');

// Write .env
$envContent = <<<ENV
APP_NAME="$appName"
APP_ENV=$appEnv
APP_KEY={$env['APP_KEY']}
APP_DEBUG={$appDebug}
APP_URL=$appUrl

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=$dbHost
DB_PORT=$dbPort
DB_DATABASE=$dbName
DB_USERNAME=$dbUser
DB_PASSWORD=$dbPass

SESSION_DRIVER=database
SESSION_LIFETIME=120

QUEUE_CONNECTION=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="$appName"
ENV;

$appDebug = $appEnv === 'production' ? 'false' : 'true';
$envContent = str_replace('{$appDebug}', $appDebug, $envContent);

file_put_contents($envFile, $envContent);
success("Wrote .env");

// ── 4. Generate app key if missing ──────────────────────────────────────

heading('4. Application key');

if (empty($env['APP_KEY'])) {
    shell_exec('php artisan key:generate --force 2>&1');
    success("Generated new application key");
} else {
    success("Application key exists");
}

// ── 5. Install dependencies ─────────────────────────────────────────────

heading('5. Installing dependencies');

info("Running composer install...");
$output = shell_exec("$composer install --no-interaction --prefer-dist 2>&1");
if (strpos($output, 'Error') !== false && strpos($output, 'Warning') === false) {
    error("Composer install failed");
    echo $output . "\n";
    exit(1);
}
success("PHP dependencies installed");

info("Running npm install...");
$output = shell_exec("$npm install 2>&1");
success("Node dependencies installed");

// ── 6. Database setup ───────────────────────────────────────────────────

heading('6. Database setup');

$createDb = prompt('Create database if it does not exist? (y/n)', 'y');
if (strtolower($createDb) === 'y') {
    $result = shell_exec("sudo mysql -e \"CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\" 2>&1");
    if (strpos($result ?? '', 'ERROR') !== false) {
        warn("Could not auto-create database. Create it manually: CREATE DATABASE $dbName;");
    } else {
        success("Database '$dbName' ready");
    }

    // Create user if needed
    $createUser = prompt('Create/update database user? (y/n)', 'y');
    if (strtolower($createUser) === 'y') {
        shell_exec("sudo mysql -e \"CREATE USER IF NOT EXISTS '$dbUser'@'$dbHost' IDENTIFIED BY '$dbPass'; GRANT ALL PRIVILEGES ON $dbName.* TO '$dbUser'@'$dbHost'; FLUSH PRIVILEGES;\" 2>&1");
        success("Database user '$dbUser' configured");
    }
}

info("Running migrations...");
$output = shell_exec('php artisan migrate --force 2>&1');
echo $output;
success("Migrations complete");

// ── 7. Seed data ────────────────────────────────────────────────────────

heading('7. Seed data');

$seed = prompt('Seed demo data? This will RESET the database (y/n)', 'n');
if (strtolower($seed) === 'y') {
    $output = shell_exec('php artisan migrate:fresh --seed --force 2>&1');
    echo $output;
    success("Database seeded with demo data");
    echo "\n  Demo accounts:\n";
    echo "  admin@skedbetter.com / password (Superadmin)\n";
    echo "  manager@skedbetter.com / password (League Admin)\n";
    echo "  coach@skedbetter.com / password (Coach)\n";
}

// ── 8. Build frontend ───────────────────────────────────────────────────

heading('8. Building frontend assets');

info("Running npm build...");
$output = shell_exec("$npm run build 2>&1");
if (strpos($output ?? '', 'error') !== false && strpos($output ?? '', 'built in') === false) {
    warn("Build may have issues. Check manually with: npm run build");
} else {
    success("Frontend built");
}

// ── 9. Permissions ──────────────────────────────────────────────────────

heading('9. Setting permissions');

shell_exec('chmod -R 775 storage bootstrap/cache 2>&1');
shell_exec('chgrp -R www-data storage bootstrap/cache 2>/dev/null');
success("Storage and cache permissions set");

// ── 10. Cache ───────────────────────────────────────────────────────────

heading('10. Optimizing');

if ($appEnv === 'production') {
    shell_exec('php artisan config:cache 2>&1');
    shell_exec('php artisan route:cache 2>&1');
    shell_exec('php artisan view:cache 2>&1');
    success("Config, routes, and views cached");
} else {
    shell_exec('php artisan config:clear 2>&1');
    shell_exec('php artisan route:clear 2>&1');
    shell_exec('php artisan view:clear 2>&1');
    success("Caches cleared (dev mode)");
}

// ── 11. Apache vhost ────────────────────────────────────────────────────

heading('11. Web server');

$setupVhost = prompt('Configure Apache vhost? (y/n)', 'n');
if (strtolower($setupVhost) === 'y') {
    $serverName = prompt('Server name', str_replace(['https://', 'http://'], '', $appUrl));
    $docRoot = __DIR__ . '/public';
    $sslDir = '/etc/apache2/ssl';

    // Generate self-signed cert
    if (!file_exists("$sslDir/$serverName.crt")) {
        shell_exec("sudo mkdir -p $sslDir");
        shell_exec("sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout $sslDir/$serverName.key -out $sslDir/$serverName.crt -subj '/CN=$serverName' 2>&1");
        success("SSL certificate generated");
    }

    $vhost = <<<VHOST
<VirtualHost *:80>
    ServerName $serverName
    RewriteEngine On
    RewriteRule ^(.*)\$ https://$serverName\$1 [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName $serverName
    DocumentRoot $docRoot

    SSLEngine on
    SSLCertificateFile    $sslDir/$serverName.crt
    SSLCertificateKeyFile $sslDir/$serverName.key

    <Directory $docRoot>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    <DirectoryMatch "$docRoot/../(storage|bootstrap/cache|vendor)">
        Require all denied
    </DirectoryMatch>

    ErrorLog \${APACHE_LOG_DIR}/skedbetter-error.log
    CustomLog \${APACHE_LOG_DIR}/skedbetter-access.log combined
</VirtualHost>
VHOST;

    $vhostFile = "/etc/apache2/sites-available/skedbetter.conf";
    file_put_contents('/tmp/skedbetter-vhost.conf', $vhost);
    shell_exec("sudo mv /tmp/skedbetter-vhost.conf $vhostFile");
    shell_exec("sudo a2ensite skedbetter.conf 2>&1");
    shell_exec("sudo a2enmod ssl rewrite 2>&1");

    // Add hosts entry
    $hostsContent = file_get_contents('/etc/hosts');
    if (strpos($hostsContent, $serverName) === false) {
        shell_exec("sudo bash -c 'echo \"127.0.0.1 $serverName\" >> /etc/hosts'");
        success("Added $serverName to /etc/hosts");
    }

    shell_exec("sudo apache2ctl configtest 2>&1");
    shell_exec("sudo service apache2 restart 2>&1");
    success("Apache configured: https://$serverName");
}

// ── Done ────────────────────────────────────────────────────────────────

heading('Setup Complete!');

echo "  App URL:     $appUrl\n";
echo "  Database:    $dbName @ $dbHost\n";
echo "  Environment: $appEnv\n";
echo "\n";

if ($appEnv === 'local') {
    echo "  For development, run:\n";
    echo "    npm run dev     (Vite dev server with HMR)\n";
    echo "\n";
}

echo "  Email: Configure via Platform Settings (superadmin) after login.\n";
echo "  Mail transport options: SMTP, Microsoft Graph (Office 365), or Log (testing).\n";
echo "\n";

echo "  Stack:\n";
echo "    Backend:  Laravel 13 + PHP 8.3 + MySQL 8\n";
echo "    Frontend: Vue 3 + Inertia.js + Tailwind CSS + FullCalendar 6\n";
echo "    Auth:     Magic link (email) + password fallback\n";
echo "\n";
success("SkedBetter is ready!");
