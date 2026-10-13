<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'rate_limiter' => [
            'registration' => [
                'policy' => 'fixed_window',
                'limit' => 5,
                'interval' => '1 hour',
                'cache_pool' => 'cache.registration_rate_limiter',
            ],
            'public_navigation' => [
                'policy' => 'fixed_window',
                'limit' => 10,
                'interval' => '1 minute',
                'cache_pool' => 'cache.public_navigation_rate_limiter',
            ],
        ],
    ]);
};
