<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Http;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final readonly class CrawlerProtectionSubscriber implements EventSubscriberInterface
{
    private const string ROBOTS_DIRECTIVE = 'noindex, nofollow, noarchive, nosnippet';

    /** @var list<string> */
    private const array PUBLIC_NAVIGATION_ROUTES = [
        'app_dashboard',
        'app_login',
        'app_register',
    ];

    public function __construct(
        private Security $security,

        #[Target('public_navigation')]
        private RateLimiterFactoryInterface $publicNavigationLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['limitAnonymousPublicNavigation', 0],
            KernelEvents::RESPONSE => 'addRobotsDirective',
        ];
    }

    public function limitAnonymousPublicNavigation(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || Request::METHOD_GET !== $event->getRequest()->getMethod() || null !== $this->security->getUser()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        if (!is_string($route) || !in_array($route, self::PUBLIC_NAVIGATION_ROUTES, true)) {
            return;
        }

        $limit = $this->publicNavigationLimiter->create($route.':'.($request->getClientIp() ?? 'unknown'))->consume();
        if ($limit->isAccepted()) {
            return;
        }

        throw new TooManyRequestsHttpException(max(1, $limit->getRetryAfter()->getTimestamp() - time()), 'Too many requests. Please try again shortly.');
    }

    public function addRobotsDirective(ResponseEvent $event): void
    {
        if ($event->isMainRequest()) {
            $event->getResponse()->headers->set('X-Robots-Tag', self::ROBOTS_DIRECTIVE);
        }
    }
}
