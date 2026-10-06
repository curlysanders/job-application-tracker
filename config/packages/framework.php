<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'secret' => '%env(APP_SECRET)%',
        'session' => true,
        'trusted_proxies' => '%env(default::SYMFONY_TRUSTED_PROXIES)%',
        'trusted_headers' => [
            'x-forwarded-for',
            'x-forwarded-host',
            'x-forwarded-proto',
            'x-forwarded-port',
            'x-forwarded-prefix',
        ],
    ]);
    if ('test' === $containerConfigurator->env()) {
        $containerConfigurator->extension('framework', [
            'test' => true,
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
        ]);
    }

    if ('prod' === $containerConfigurator->env()) {
        $containerConfigurator->extension('framework', [
            'session' => [
                'handler_id' => '%env(resolve:DATABASE_URL)%',
            ],
        ]);
    }
};
