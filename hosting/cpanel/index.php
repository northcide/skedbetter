<?php
/**
 * SkedBetter — cPanel public_html shim
 *
 * Place this file in /home/username/public_html/index.php
 * It boots Laravel from the repo at /home/username/skedbetter
 *
 * Instructions:
 *   1. Upload the full repo to /home/username/skedbetter (via File Manager or FTP)
 *   2. Run 'composer install --no-dev' locally and upload the vendor/ folder
 *   3. Run 'npm run build' locally — the built assets are in public/build/
 *   4. Copy this index.php and .htaccess to /home/username/public_html/
 *   5. Copy the public/build/ folder to /home/username/public_html/build/
 *   6. Copy public/.htaccess to /home/username/public_html/.htaccess
 *   7. Visit https://yourdomain.com/setup.php to configure
 *
 * Adjust $repoPath below if your repo is in a different location.
 */

// Path to the repo (one level up from public_html, in a folder called 'skedbetter')
$repoPath = dirname(__DIR__) . '/skedbetter';

// If the repo path doesn't exist, try to show a helpful error
if (!file_exists($repoPath . '/vendor/autoload.php')) {
    http_response_code(500);
    echo "<h2>SkedBetter Setup Error</h2>";
    echo "<p>Could not find the application at: <code>" . htmlspecialchars($repoPath) . "</code></p>";
    echo "<p>Make sure:</p>";
    echo "<ul>";
    echo "<li>The repo is uploaded to <code>" . htmlspecialchars($repoPath) . "</code></li>";
    echo "<li>The <code>vendor/</code> folder is uploaded (run <code>composer install --no-dev</code> locally first)</li>";
    echo "<li>The <code>public/build/</code> folder is copied to <code>public_html/build/</code></li>";
    echo "</ul>";
    exit;
}

// Tell Laravel where things are
define('LARAVEL_START', microtime(true));

// Use the real paths
require $repoPath . '/vendor/autoload.php';

// Bootstrap the application
$app = require_once $repoPath . '/bootstrap/app.php';

// Override the public path to point to THIS directory (public_html)
$app->usePublicPath(__DIR__);

// Run the application
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
