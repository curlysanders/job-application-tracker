<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('nelmio_security', [
        'clickjacking' => [
            'paths' => ['^/.*' => 'DENY'],
        ],
        'content_type' => ['nosniff' => true],
        'referrer_policy' => [
            'enabled' => true,
            'policies' => ['no-referrer', 'strict-origin-when-cross-origin'],
        ],
        'csp' => [
            'compat_headers' => false,
            'enforce' => [
                'level1_fallback' => false,
                'default-src' => ["'self'"],
                'base-uri' => ["'self'"],
                'connect-src' => ["'self'"],
                'form-action' => ["'self'"],
                'frame-ancestors' => ["'none'"],
                'img-src' => ["'self'", 'data:'],
                'object-src' => ["'none'"],
                'script-src' => ["'self'"],
                'style-src' => ["'self'", "'unsafe-inline'"],
            ],
        ],
    ]);
};
