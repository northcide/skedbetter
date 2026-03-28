<?php
// Show errors during setup
error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * SkedBetter Web Setup Wizard
 *
 * Access: https://yourdomain.com/setup.php
 *
 * This wizard handles:
 *   - Database connection configuration
 *   - Running migrations
 *   - Creating the superadmin account
 *   - Setting the app URL and key
 *   - Basic environment configuration
 *
 * After setup, this file should be deleted or will auto-lock once a superadmin exists.
 *
 * Last updated: 2026-03-28
 * Stack: Laravel 13 + Vue 3 + Inertia.js + Tailwind CSS + MySQL 8 + FullCalendar 6
 */

$basePath = dirname(__DIR__);
$envFile = $basePath . '/.env';
$envExample = $basePath . '/.env.example';
$step = $_POST['step'] ?? $_GET['step'] ?? 'check';
$errors = [];
$success = '';

// ── Check if already set up ─────────────────────────────────────────────
if ($step !== 'lock' && file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    // If we can connect to DB and a superadmin exists, lock setup
    if (preg_match('/DB_DATABASE=(.+)/', $envContent, $m)) {
        try {
            $env = parseEnv($envFile);
            $pdo = getDbConnection($env);
            if ($pdo) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_superadmin = 1");
                if ($stmt && $stmt->fetchColumn() > 0 && !isset($_GET['force'])) {
                    $step = 'locked';
                }
            }
        } catch (Exception $e) {
            // DB not configured yet, continue with setup
        }
    }
}

// ── Helper functions ────────────────────────────────────────────────────

function parseEnv($file) {
    $env = [];
    if (!file_exists($file)) return $env;
    foreach (file($file) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            $env[trim($key)] = trim(trim($val), '"\'');
        }
    }
    return $env;
}

function getDbConnection($env) {
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $db = $env['DB_DATABASE'] ?? '';
    $user = $env['DB_USERNAME'] ?? '';
    $pass = $env['DB_PASSWORD'] ?? '';
    if (!$db || !$user) return null;
    return new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

function generateKey() {
    return 'base64:' . base64_encode(random_bytes(32));
}

function writeEnv($file, $values) {
    $lines = [];
    foreach ($values as $key => $val) {
        if (strpos($val, ' ') !== false || strpos($val, '#') !== false) {
            $val = "\"$val\"";
        }
        $lines[] = "$key=$val";
    }
    file_put_contents($file, implode("\n", $lines) . "\n");
}

// ── Process form submissions ────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step === 'database') {
        $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        $appUrl = trim($_POST['app_url'] ?? '');
        $appName = trim($_POST['app_name'] ?? 'SkedBetter');

        if (!$dbName) $errors[] = 'Database name is required';
        if (!$dbUser) $errors[] = 'Database username is required';
        if (!$appUrl) $errors[] = 'App URL is required';

        if (empty($errors)) {
            // Test connection
            try {
                $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $pdo->query("SELECT 1");
            } catch (Exception $e) {
                $errors[] = "Database connection failed: " . $e->getMessage();
            }
        }

        if (empty($errors)) {
            // Read existing key or generate new one
            $existingEnv = file_exists($envFile) ? parseEnv($envFile) : [];
            $appKey = $existingEnv['APP_KEY'] ?? generateKey();

            $envValues = [
                'APP_NAME' => $appName,
                'APP_ENV' => 'production',
                'APP_KEY' => $appKey,
                'APP_DEBUG' => 'false',
                'APP_URL' => rtrim($appUrl, '/'),
                'APP_LOCALE' => 'en',
                'APP_FALLBACK_LOCALE' => 'en',
                'APP_FAKER_LOCALE' => 'en_US',
                'APP_MAINTENANCE_DRIVER' => 'file',
                'BCRYPT_ROUNDS' => '12',
                'LOG_CHANNEL' => 'stack',
                'LOG_STACK' => 'single',
                'LOG_DEPRECATIONS_CHANNEL' => 'null',
                'LOG_LEVEL' => 'error',
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $dbHost,
                'DB_PORT' => $dbPort,
                'DB_DATABASE' => $dbName,
                'DB_USERNAME' => $dbUser,
                'DB_PASSWORD' => $dbPass,
                'SESSION_DRIVER' => 'database',
                'SESSION_LIFETIME' => '120',
                'QUEUE_CONNECTION' => 'database',
                'MAIL_MAILER' => 'log',
                'MAIL_FROM_ADDRESS' => "noreply@" . parse_url($appUrl, PHP_URL_HOST),
                'MAIL_FROM_NAME' => $appName,
            ];

            writeEnv($envFile, $envValues);
            $step = 'migrate';
        }
    }

    if ($step === 'migrate') {
        // Try artisan first, fall back to bootstrapping Laravel
        $migrated = false;
        $output = '';

        // Method 1: shell_exec (works on most hosts)
        if (function_exists('shell_exec')) {
            $phpBin = PHP_BINARY ?: 'php';
            $output = @shell_exec("cd " . escapeshellarg($basePath) . " && $phpBin artisan migrate --force 2>&1");
            if ($output !== null && strpos($output, 'DONE') !== false) {
                $migrated = true;
            }
        }

        // Method 2: Bootstrap Laravel and run migrate programmatically
        if (!$migrated) {
            try {
                // Require composer autoloader and bootstrap the app
                require $basePath . '/vendor/autoload.php';
                $app = require_once $basePath . '/bootstrap/app.php';
                $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
                $kernel->bootstrap();

                $exitCode = \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $output = \Illuminate\Support\Facades\Artisan::output();
                $migrated = ($exitCode === 0);
            } catch (Exception $e) {
                $output = $e->getMessage();
            }
        }

        if (!$migrated) {
            $errors[] = "Migration failed: " . ($output ?: 'Unknown error. Check file permissions and PHP version.');
            $step = 'database';
        } else {
            $success = 'Migrations complete';
            $step = 'admin';
        }
    }

    if ($step === 'admin') {
        $name = trim($_POST['admin_name'] ?? '');
        $email = trim($_POST['admin_email'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $passwordConfirm = $_POST['admin_password_confirm'] ?? '';

        if (!$name) $errors[] = 'Name is required';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
        if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match';

        if (empty($errors)) {
            try {
                $env = parseEnv($envFile);
                $pdo = getDbConnection($env);

                // Check if user already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([strtolower($email)]);
                $existing = $stmt->fetch();

                if ($existing) {
                    // Update existing user to superadmin
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ?, is_superadmin = 1, email_verified_at = NOW() WHERE email = ?");
                    $stmt->execute([$name, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), strtolower($email)]);
                } else {
                    // Create new superadmin
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, is_superadmin, email_verified_at, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW(), NOW())");
                    $stmt->execute([$name, strtolower($email), password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])]);
                }

                $step = 'done';
            } catch (Exception $e) {
                $errors[] = "Failed to create admin: " . $e->getMessage();
            }
        }
    }
}

// ── Render page ─────────────────────────────────────────────────────────
$pageTitle = match($step) {
    'check' => 'Welcome',
    'database' => 'Database & App Configuration',
    'migrate' => 'Running Migrations...',
    'admin' => 'Create Superadmin',
    'done' => 'Setup Complete!',
    'locked' => 'Setup Locked',
    default => 'Setup',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="google" content="notranslate">
    <title>SkedBetter Setup — <?= htmlspecialchars($pageTitle) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #142a57, #164cb6, #18428f); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 16px; padding: 32px; max-width: 520px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo h1 { font-size: 24px; font-weight: 800; color: #142a57; }
        .logo p { font-size: 12px; color: #9ca3af; margin-top: 4px; }
        h2 { font-size: 16px; color: #111827; margin-bottom: 4px; }
        .subtitle { font-size: 12px; color: #6b7280; margin-bottom: 16px; }
        label { display: block; font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 4px; margin-top: 12px; }
        input[type=text], input[type=email], input[type=password], input[type=number], input[type=url] {
            width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; outline: none; transition: border 0.2s;
        }
        input:focus { border-color: #1a75f5; box-shadow: 0 0 0 2px rgba(26,117,245,0.15); }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .row3 { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 8px; }
        button[type=submit], .btn { display: block; width: 100%; padding: 10px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; margin-top: 20px; transition: background 0.2s; }
        .btn-primary { background: #1a75f5; color: #fff; }
        .btn-primary:hover { background: #135ee1; }
        .btn-secondary { background: #f3f4f6; color: #374151; }
        .errors { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
        .errors p { font-size: 12px; color: #991b1b; margin: 2px 0; }
        .success-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
        .success-box p { font-size: 12px; color: #166534; }
        .info { font-size: 11px; color: #9ca3af; margin-top: 8px; }
        .steps { display: flex; gap: 4px; margin-bottom: 20px; }
        .step { flex: 1; height: 4px; border-radius: 2px; background: #e5e7eb; }
        .step.active { background: #1a75f5; }
        .step.done { background: #22c55e; }
        .check-list { list-style: none; margin: 12px 0; }
        .check-list li { font-size: 12px; padding: 4px 0; display: flex; align-items: center; gap: 6px; }
        .check-list .ok { color: #16a34a; }
        .check-list .fail { color: #dc2626; }
        .check-list .warn { color: #d97706; }
        a { color: #1a75f5; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <h1>SkedBetter</h1>
        <p>Field scheduling made simple</p>
    </div>

    <?php if ($step === 'locked'): ?>
        <h2>Setup Complete</h2>
        <p class="subtitle">A superadmin account already exists. Setup is locked.</p>
        <p class="info">To re-run setup, add <code>?force=1</code> to the URL. For security, delete this file after setup.</p>
        <a href="<?= htmlspecialchars(parseEnv($envFile)['APP_URL'] ?? '/') ?>" class="btn btn-primary" style="text-align:center; text-decoration:none; color:#fff; display:block; margin-top:16px;">Go to SkedBetter</a>

    <?php elseif ($step === 'check' || $step === 'database'): ?>
        <div class="steps">
            <div class="step active"></div>
            <div class="step"></div>
            <div class="step"></div>
        </div>

        <h2>Database & App Configuration</h2>
        <p class="subtitle">Enter your database credentials and app URL.</p>

        <?php if (!empty($errors)): ?>
            <div class="errors"><?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
        <?php endif; ?>

        <?php
            // Pre-fill from existing .env or POST
            $env = file_exists($envFile) ? parseEnv($envFile) : [];
            $f = fn($key, $default = '') => htmlspecialchars($_POST[$key] ?? $env[strtoupper(str_replace(['db_', 'app_'], ['DB_', 'APP_'], $key))] ?? $default);
        ?>

        <form method="POST">
            <input type="hidden" name="step" value="database">

            <label>App Name</label>
            <input type="text" name="app_name" value="<?= $f('app_name', 'SkedBetter') ?>" required>

            <label>App URL</label>
            <input type="url" name="app_url" value="<?= $f('app_url', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) ?>" required placeholder="https://skedbetter.com">

            <div class="row3" style="margin-top:12px">
                <div><label>Database Host</label><input type="text" name="db_host" value="<?= $f('db_host', '127.0.0.1') ?>" required></div>
                <div><label>Port</label><input type="number" name="db_port" value="<?= $f('db_port', '3306') ?>" required></div>
                <div><label>Database</label><input type="text" name="db_name" value="<?= $f('db_name') ?>" required></div>
            </div>

            <div class="row">
                <div><label>Username</label><input type="text" name="db_user" value="<?= $f('db_user') ?>" required></div>
                <div><label>Password</label><input type="password" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? $env['DB_PASSWORD'] ?? '') ?>"></div>
            </div>

            <p class="info">The database must already exist. On cPanel, create it via MySQL Databases.</p>

            <button type="submit" class="btn btn-primary">Test Connection & Continue</button>
        </form>

    <?php elseif ($step === 'admin'): ?>
        <div class="steps">
            <div class="step done"></div>
            <div class="step active"></div>
            <div class="step"></div>
        </div>

        <h2>Create Superadmin</h2>
        <p class="subtitle">This account manages the platform and creates leagues.</p>

        <?php if ($success): ?>
            <div class="success-box"><p><?= htmlspecialchars($success) ?></p></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="errors"><?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="step" value="admin">

            <label>Full Name</label>
            <input type="text" name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>

            <label>Email</label>
            <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>

            <div class="row">
                <div><label>Password</label><input type="password" name="admin_password" required minlength="8"></div>
                <div><label>Confirm Password</label><input type="password" name="admin_password_confirm" required minlength="8"></div>
            </div>

            <button type="submit" class="btn btn-primary">Create Admin & Finish</button>
        </form>

    <?php elseif ($step === 'done'): ?>
        <div class="steps">
            <div class="step done"></div>
            <div class="step done"></div>
            <div class="step done"></div>
        </div>

        <h2>Setup Complete!</h2>
        <div class="success-box"><p>SkedBetter is ready to use.</p></div>

        <ul class="check-list">
            <li class="ok">&#10003; Database configured and migrated</li>
            <li class="ok">&#10003; Superadmin account created</li>
            <li class="ok">&#10003; Application key generated</li>
        </ul>

        <p class="info"><strong>Next steps:</strong></p>
        <ul class="check-list">
            <li>&#8226; Log in with your superadmin credentials</li>
            <li>&#8226; Configure email via Platform Settings</li>
            <li>&#8226; Create your first league</li>
            <li>&#8226; <strong>Delete this setup.php file for security</strong></li>
        </ul>

        <?php $env = parseEnv($envFile); ?>
        <a href="<?= htmlspecialchars($env['APP_URL'] ?? '/') ?>" class="btn btn-primary" style="text-align:center; text-decoration:none; color:#fff; display:block;">Go to SkedBetter</a>

    <?php endif; ?>

    <p style="text-align:center; margin-top:20px; font-size:10px; color:#d1d5db;">
        SkedBetter v1.0 &mdash; Laravel 13 + Vue 3 + MySQL
    </p>
</div>
</body>
</html>
