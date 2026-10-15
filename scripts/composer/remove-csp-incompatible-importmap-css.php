<?php

/**
 * Keep Tom Select's styles as same-origin stylesheet links.
 *
 * Symfony Flex adds these CSS modules from UX Autocomplete's package metadata
 * during Composer installs. AssetMapper would then expose them as data URLs in
 * the import map, which conflicts with this application's strict CSP. The
 * active stylesheet is linked from templates/base.html.twig instead.
 */
$path = __DIR__.'/../../importmap.php';
$contents = file_get_contents($path);

if (false === $contents) {
    throw new RuntimeException(sprintf('Unable to read "%s".', $path));
}

foreach ([
    'tom-select/dist/css/tom-select.default.css',
    'tom-select/dist/css/tom-select.bootstrap4.css',
    'tom-select/dist/css/tom-select.bootstrap5.css',
] as $package) {
    $pattern = sprintf("/^    '%s' => \\[[^\\n]+\\],\\R/m", preg_quote($package, '/'));
    $updatedContents = preg_replace($pattern, '', $contents);

    if (null === $updatedContents) {
        throw new RuntimeException(sprintf('Unable to remove "%s" from the import map.', $package));
    }

    $contents = $updatedContents;
}

if (false === file_put_contents($path, $contents)) {
    throw new RuntimeException(sprintf('Unable to write "%s".', $path));
}
