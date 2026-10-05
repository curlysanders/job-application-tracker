<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()
        ->set('app.default_storage_directory', '%kernel.project_dir%/var/storage/default')
        ->set('app.storage_directory', '%env(default:app.default_storage_directory:APP_STORAGE_DIR)%');

    $containerConfigurator->extension('flysystem', [
        'storages' => [
            'default.storage' => [
                'local' => [
                    'directory' => '%app.storage_directory%',
                ],
            ],
        ],
    ]);
};
