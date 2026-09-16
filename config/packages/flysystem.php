<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('flysystem', [
        'storages' => [
            'default.storage' => [
                'local' => [
                    'directory' => '%kernel.project_dir%/var/storage/default',
                ],
            ],
        ],
    ]);
};
