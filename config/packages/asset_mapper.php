<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'asset_mapper' => [
            'paths' => [
                'assets/',
            ],
            'missing_import_mode' => 'strict',
        ],
    ]);
    if ('prod' === $containerConfigurator->env()) {
        $containerConfigurator->extension('framework', [
            'asset_mapper' => [
                'missing_import_mode' => 'warn',
            ],
        ]);
    }
};
