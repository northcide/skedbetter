<?php
/**
 * SkedBetter — cPanel Setup Shim
 *
 * This just loads the real setup.php from the repo's public/ directory.
 * Place in /home/username/public_html/setup.php alongside index.php
 */

$repoPath = dirname(__DIR__) . '/skedbetter';
$setupFile = $repoPath . '/public/setup.php';

if (file_exists($setupFile)) {
    // Override basePath so setup.php finds the right directories
    // The real setup.php uses dirname(__DIR__) which would point to public_html's parent
    // We need it to point to the repo root instead
    chdir($repoPath . '/public');
    include $setupFile;
} else {
    echo "<h2>Setup Error</h2>";
    echo "<p>Could not find setup file at: <code>" . htmlspecialchars($setupFile) . "</code></p>";
    echo "<p>Make sure the SkedBetter repo is uploaded to <code>" . htmlspecialchars($repoPath) . "</code></p>";
}
