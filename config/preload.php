<?php

// @phpstan-ignore ternary.shortNotAllowed
foreach (glob(dirname(__DIR__).'/var/cache/prod/*.preload.php') ?: [] as $file) {
    require $file;
}
