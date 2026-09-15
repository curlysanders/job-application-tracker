<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class RequestTimingSubscriber implements EventSubscriberInterface
{
    private const string START_ATTRIBUTE = '_app_request_started_at';

    public function __construct(
        #[Autowire(env: 'bool:APP_REQUEST_TIMING')]
        private bool $enabled,
        #[Autowire(service: 'monolog.logger.performance')]
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 4096],
            KernelEvents::RESPONSE => ['onResponse', -4096],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if ($this->enabled && $event->isMainRequest()) {
            $event->getRequest()->attributes->set(self::START_ATTRIBUTE, hrtime(true));
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $start = $request->attributes->get(self::START_ATTRIBUTE);
        if (!is_int($start)) {
            return;
        }

        $duration = (hrtime(true) - $start) / 1_000_000;
        $request->attributes->remove(self::START_ATTRIBUTE);
        $event->getResponse()->headers->set('Server-Timing', sprintf('app;dur=%.3f', $duration), false);
        $this->logger->info('Application request completed', [
            'route' => $request->attributes->get('_route'),
            'status' => $event->getResponse()->getStatusCode(),
            'duration_ms' => $duration,
        ]);
    }
}
