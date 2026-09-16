<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'messenger' => [
            'default_bus' => 'command.bus',
            'buses' => [
                'command.bus' => [
                    'default_middleware' => [
                        'allow_no_handlers' => false,
                    ],
                ],
                'query.bus' => [
                    'default_middleware' => [
                        'allow_no_handlers' => false,
                    ],
                ],
                'event.bus' => [
                    'default_middleware' => [
                        'allow_no_handlers' => true,
                    ],
                ],
            ],
            'transports' => [
                'sync' => 'sync://',
            ],
            'routing' => [],
        ],
    ]);
};
