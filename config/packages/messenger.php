<?php

declare(strict_types=1);

use CurlySanders\JobApplicationTracker\Infrastructure\Messaging\OutboxRecordIdContextMiddleware;
use Lingoda\DomainEventsBundle\Infra\Symfony\Messenger\OutboxMessage;
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
                    'middleware' => [
                        OutboxRecordIdContextMiddleware::class,
                    ],
                ],
            ],
            'transports' => [
                'sync' => 'sync://',
                'outbox' => [
                    'dsn' => 'outbox://default?skip_locked=true&lease=300',
                ],
            ],
            'routing' => [
                OutboxMessage::class => 'outbox',
            ],
        ],
    ]);
};
