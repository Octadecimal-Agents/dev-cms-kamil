<?php
/**
 * Undo the file move — put everything back in www/ and fix motorcycle pages.
 * Upload to /home/wheelse/www/ (recreate it first via SCP), then run.
 */
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') { http_response_code(403); die(); }

set_time_limit(120);
header('Content-Type: text/plain; charset=utf-8');
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

function out($msg) { echo "$msg\n"; flush(); }

$homeDir = '/home/wheelse';
$wwwDir  = "$homeDir/www";
$appDir  = "$homeDir/2wheels";

out("Restoring files to www/...\n");

// List of web files/dirs that were moved to home and should go back to www/
$webItems = [
    '.htaccess', '404.html', '_next', '_not-found', '_not-found.html', '_not-found.txt',
    'hero-bike.png', 'img', 'img-from-prd', 'index.html', 'index.php', 'index.txt',
    'motocykle', 'polityka-prywatnosci', 'polityka-prywatnosci.html', 'polityka-prywatnosci.txt',
    'regulamin', 'regulamin.html', 'regulamin.txt', 'storage',
    // Next.js metadata
    '__next.__PAGE__.txt', '__next._full.txt', '__next._head.txt',
    '__next._index.txt', '__next._tree.txt',
];

// Also move any PHP deploy files
foreach (glob("$homeDir/*.php") as $f) {
    $name = basename($f);
    if ($name !== 'undo-fix.php') { // Don't move ourselves (we're in www/)
        $webItems[] = $name;
    }
}

foreach ($webItems as $item) {
    $src = "$homeDir/$item";
    $dst = "$wwwDir/$item";

    if (!file_exists($src) && !is_link($src)) continue;

    // Remove destination if exists
    if (is_link($dst)) unlink($dst);
    elseif (is_dir($dst)) exec("rm -rf " . escapeshellarg($dst));
    elseif (file_exists($dst)) unlink($dst);

    if ($item === 'storage') {
        // Recreate symlink for www/ context
        symlink("../2wheels/storage/app/public", $dst);
        // Remove old one
        if (is_link($src)) unlink($src);
        out("  symlink: storage → ../2wheels/storage/app/public");
        continue;
    }

    rename($src, $dst);
    out("  moved back: $item");
}

// ── Fix index.php paths (back to ../2wheels/) ───────────────────
out("\n▸ Fixing index.php paths...");
copy("$appDir/deploy/index.php", "$wwwDir/index.php");
out("✓ index.php restored (../2wheels/ paths)");

// ── Fix .htaccess ───────────────────────────────────────────────
out("▸ Fixing .htaccess...");
copy("$appDir/deploy/.htaccess", "$wwwDir/.htaccess");
out("✓ .htaccess restored");

// ── Remove root .htaccess that causes issues ────────────────────
if (file_exists("$homeDir/.htaccess")) {
    unlink("$homeDir/.htaccess");
    out("✓ Removed root .htaccess");
}

// ── Fix motorcycle pages: remove conflicting metadata directories ─
out("\n▸ Removing Next.js metadata directories that conflict with clean URLs...");
$dirsToRemove = [];

// Find all directories that have a matching .html file
foreach (glob("$wwwDir/motocykle/*", GLOB_ONLYDIR) as $dir) {
    $htmlFile = $dir . '.html';
    if (file_exists($htmlFile)) {
        exec("rm -rf " . escapeshellarg($dir));
        out("  removed dir: motocykle/" . basename($dir) . "/");
    }
}

// Also clean up root-level metadata dirs
foreach (['_not-found', 'polityka-prywatnosci', 'regulamin'] as $name) {
    $dir = "$wwwDir/$name";
    if (is_dir($dir) && file_exists("$dir.html")) {
        // Keep only the .html file, remove the directory
        // But check if the dir has useful content (like an index.html)
        if (!file_exists("$dir/index.html")) {
            exec("rm -rf " . escapeshellarg($dir));
            out("  removed dir: $name/");
        }
    }
}

// Also remove Next.js root metadata files that aren't needed
foreach (glob("$wwwDir/__next.*.txt") as $f) {
    unlink($f);
}

// Remove root-level metadata files
foreach (glob("$wwwDir/*.txt") as $f) {
    $basename = basename($f);
    // Keep only .html counterparts, remove .txt metadata
    if (preg_match('/^(index|regulamin|polityka-prywatnosci|_not-found|404)\.txt$/', $basename)) {
        // These are Next.js metadata, not needed for serving
    }
}

// ── Install proper root .htaccess (just rewrite to www/) ────────
$rootHtaccess = <<<'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/www/
    RewriteRule ^(.*)$ /www/$1 [L]
</IfModule>
HTACCESS;
file_put_contents("$homeDir/.htaccess", $rootHtaccess);
out("\n✓ Root .htaccess installed (rewrite to www/)");

// Cleanup deploy scripts
foreach (['test123.php', 'fix-deploy.php', 'finish-deploy.php', 'find-php.php', 'debug-rewrite.php', 'debug-root.php', 'debug-moto.php'] as $f) {
    @unlink("$wwwDir/$f");
    @unlink("$homeDir/$f");
}

@unlink(__FILE__);

out("\n✓ UNDO COMPLETE — files back in www/");
