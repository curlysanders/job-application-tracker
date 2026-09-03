<?php

/**
 * Patch ParaTest PhpStorm wrapper for IDE validation checks.
 *
 * PhpStorm validates the ParaTest binary by executing it without passing any parameters.
 * However, ParaTest's `PhpstormHelper` expects a parameter pointing to the `phpunit` binary.
 * Without it, an uncaught RuntimeException is thrown, causing PhpStorm to report:
 * "Cannot find ParaTest at /app/vendor/bin/paratest_for_phpstorm".
 *
 * This script renames the original Composer wrapper to `paratest_for_phpstorm_orig`
 * and creates a proxy that injects a dummy `/phpunit` argument when executed by PhpStorm
 * during validation checks.
 */
$binDir = __DIR__.'/../../vendor/bin';
$wrapperPath = $binDir.'/paratest_for_phpstorm';
$origWrapperPath = $binDir.'/paratest_for_phpstorm_orig';

if (!file_exists($wrapperPath) || file_exists($origWrapperPath)) {
    return;
}

// 1. Rename original Composer binary
rename($wrapperPath, $origWrapperPath);

// 2. Write patch wrapper
$patchCode = <<<'PHP'
#!/usr/bin/env php
<?php
$hasPhpunit = false;
foreach ($_SERVER['argv'] as $arg) {
    if (str_contains($arg, 'phpunit')) {
        $hasPhpunit = true;
        break;
    }
}

if (!$hasPhpunit) {
    $_SERVER['argv'][] = '/phpunit';
    $GLOBALS['argv'][] = '/phpunit';
    if (isset($argv)) {
        $argv[] = '/phpunit';
    }
}

require __DIR__ . '/paratest_for_phpstorm_orig';
PHP;

file_put_contents($wrapperPath, $patchCode);

// 3. Grant execute permissions
chmod($wrapperPath, 0755);
chmod($origWrapperPath, 0755);

echo "[Patch] Applied PhpStorm validation patch to vendor/bin/paratest_for_phpstorm\n";
