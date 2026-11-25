<?php

/**
 * Keep Flowbite and Tom Select styles as same-origin stylesheet links.
 *
 * Package metadata may add these CSS modules during dependency updates.
 * AssetMapper would then expose them as data URLs in
 * the import map, which conflicts with this application's strict CSP. The
 * stylesheets are linked from templates/base.html.twig instead.
 */
$path = __DIR__.'/../../importmap.php';
$contents = file_get_contents($path);

if (false === $contents) {
    throw new RuntimeException(sprintf('Unable to read "%s".', $path));
}

foreach ([
    'flowbite/dist/flowbite.min.css',
    'tom-select/dist/css/tom-select.default.css',
    'tom-select/dist/css/tom-select.default.min.css',
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
