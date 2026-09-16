<?php

declare(strict_types=1);

use CurlySanders\JobApplicationTracker\Application\Authentication\Command\RegisterUserHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('CurlySanders\\JobApplicationTracker\\', __DIR__.'/../src/');

    $services->set(RegisterUserHandler::class)
        ->tag('messenger.message_handler', ['bus' => 'command.bus']);
};
