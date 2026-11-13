<?php

declare(strict_types=1);

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\ConfiguredVacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\Gliner2VacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\LocalOllamaVacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\NuExtractVacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\VacancyExtractionResponseFormatFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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

    $services->set('app.ai.gliner_http_client', HttpClientInterface::class)
        ->factory(HttpClient::create(...))
        ->args([['timeout' => '%env(float:AI_GLINER_TIMEOUT_SECONDS)%']]);

    $services->set('app.ai.vacancy_extraction_response_format_factory', VacancyExtractionResponseFormatFactory::class)
        ->autowire()
        ->decorate('ai.platform.response_format_factory', 'app.ai.vacancy_extraction_response_format_factory.inner')
        ->args([service('app.ai.vacancy_extraction_response_format_factory.inner')]);

    $services->load('CurlySanders\\JobApplicationTracker\\', __DIR__.'/../src/')
        ->exclude('../src/Infrastructure/Ai/VacancyExtractionResponseFormatFactory.php');

    $services->set(LocalOllamaVacancyExtractionProvider::class)
        ->autowire()
        ->autoconfigure()
        ->arg('$nuExtract', service(NuExtractVacancyExtractionProvider::class));

    $services->set(ConfiguredVacancyExtractionProvider::class)
        ->autowire()
        ->autoconfigure()
        ->arg('$gliner', service(Gliner2VacancyExtractionProvider::class))
        ->arg('$glinerEnabled', '%env(bool:AI_GLINER_PROVIDER_ENABLED)%');
};
