<?php

declare(strict_types=1);

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Infrastructure\Console\BenchmarkAiVacancyCorpusCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->instanceof(CommandHandler::class)
        ->tag('messenger.message_handler', ['bus' => 'command.bus']);

    $services->set('app.ai.ollama_http_client', HttpClientInterface::class)
        ->factory(HttpClient::create(...))
        ->args([['timeout' => '%env(float:AI_LOCAL_TIMEOUT_SECONDS)%']]);

    $services->load('CurlySanders\\JobApplicationTracker\\', __DIR__.'/../src/');

    $services->set(BenchmarkAiVacancyCorpusCommand::class)
        ->arg('$projectDir', '%kernel.project_dir%')
        ->tag('console.command');
};
