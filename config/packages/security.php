<?php

declare(strict_types=1);

use CurlySanders\JobApplicationTracker\Domain\User\User;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('security', [
        'password_hashers' => [
            PasswordAuthenticatedUserInterface::class => 'auto',
        ],
        'providers' => [
            'app_user_provider' => [
                'entity' => [
                    'class' => User::class,
                    'property' => 'email',
                ],
            ],
        ],
        'firewalls' => [
            'dev' => [
                'pattern' => '^/(_profiler|_wdt|assets|build)/',
                'security' => false,
            ],
            'main' => [
                'lazy' => true,
                'provider' => 'app_user_provider',
                'form_login' => [
                    'login_path' => 'app_login',
                    'check_path' => 'app_login',
                    'enable_csrf' => true,
                    'csrf_token_id' => 'authenticate',
                    'default_target_path' => 'app_dashboard',
                ],
                'login_throttling' => [
                    'max_attempts' => 5,
                    'interval' => '15 minutes',
                ],
                'logout' => [
                    'path' => 'app_logout',
                    'target' => 'app_home',
                    'enable_csrf' => true,
                ],
            ],
        ],
        'access_control' => [
            [
                'path' => '^/app',
                'roles' => 'ROLE_USER',
            ],
        ],
    ]);
    if ('test' === $containerConfigurator->env()) {
        $containerConfigurator->extension('security', [
            'password_hashers' => [
                PasswordAuthenticatedUserInterface::class => [
                    'algorithm' => 'auto',
                    'cost' => 4,
                    'time_cost' => 3,
                    'memory_cost' => 10,
                ],
            ],
        ]);
    }
};
