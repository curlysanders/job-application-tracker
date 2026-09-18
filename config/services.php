<?php

declare(strict_types=1);

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->instanceof(CommandHandler::class)
        ->tag('messenger.message_handler', ['bus' => 'command.bus']);

    $services->load('CurlySanders\\JobApplicationTracker\\', __DIR__.'/../src/');
};
