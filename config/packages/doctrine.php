<?php

declare(strict_types=1);

use Carbon\Doctrine\CarbonImmutableType;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Doctrine\IdentifierType;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Doctrine\JsonDomainEventType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('doctrine', [
        'dbal' => [
            'url' => '%env(resolve:DATABASE_URL)%',
            'profiling_collect_backtrace' => '%kernel.debug%',
            'types' => [
                'carbon_immutable' => CarbonImmutableType::class,
                'byte_object' => JsonDomainEventType::class,
                IdentifierType::NAME => IdentifierType::class,
            ],
        ],
        'orm' => [
            'validate_xml_mapping' => true,
            'naming_strategy' => 'doctrine.orm.naming_strategy.underscore',
            'auto_mapping' => true,
            'mappings' => [
                'JobApplicationTracker' => [
                    'type' => 'php',
                    'is_bundle' => false,
                    'dir' => '%kernel.project_dir%/src/Infrastructure/Persistence/Doctrine/Mapping',
                    'prefix' => 'CurlySanders\JobApplicationTracker\Domain',
                    'alias' => 'JobApplicationTracker',
                ],
                'LingodaDomainEvents' => [
                    'type' => 'attribute',
                    'is_bundle' => false,
                    'dir' => '%kernel.project_dir%/vendor/lingoda/domain-events/src/Infra/Doctrine/Entity',
                    'prefix' => 'Lingoda\\DomainEventsBundle\\Infra\\Doctrine\\Entity',
                    'alias' => 'LingodaDomainEvents',
                ],
            ],
        ],
    ]);
    if ('test' === $containerConfigurator->env()) {
        $containerConfigurator->extension('doctrine', [
            'dbal' => [
                'dbname_suffix' => '_test%env(default::TEST_TOKEN)%',
            ],
        ]);
    }
    if ('prod' === $containerConfigurator->env()) {
        $containerConfigurator->extension('doctrine', [
            'orm' => [
                'query_cache_driver' => [
                    'type' => 'pool',
                    'pool' => 'doctrine.system_cache_pool',
                ],
                'result_cache_driver' => [
                    'type' => 'pool',
                    'pool' => 'doctrine.result_cache_pool',
                ],
            ],
        ]);
        $containerConfigurator->extension('framework', [
            'cache' => [
                'pools' => [
                    'doctrine.result_cache_pool' => [
                        'adapter' => 'cache.app',
                    ],
                    'doctrine.system_cache_pool' => [
                        'adapter' => 'cache.system',
                    ],
                ],
            ],
        ]);
    }
};
