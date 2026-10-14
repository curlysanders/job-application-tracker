<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('ai', [
        'platform' => [
            'ollama' => [
                'endpoint' => '%env(OLLAMA_HOST_URL)%',
                'http_client' => 'app.ai.ollama_http_client',
            ],
        ],
    ]);
};
