<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'cache' => [
            'default_doctrine_dbal_provider' => 'doctrine.dbal.default_connection',
            'pools' => [
                'cache.registration_rate_limiter' => [
                    'adapter' => 'cache.adapter.doctrine_dbal',
                ],
            ],
        ],
    ]);
};
