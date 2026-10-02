<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('lingoda_domain_events', [
        'message_bus_name' => 'event.bus',
        'enable_event_publisher' => false,
    ]);
};
