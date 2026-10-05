<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller;

use CurlySanders\JobApplicationTracker\Application\Health\ApplicationHealthCheck;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HealthController
{
    public function __construct(private ApplicationHealthCheck $healthCheck)
    {
    }

    #[Route('/healthz', name: 'app_health', methods: ['GET'])]
    public function __invoke(): Response
    {
        if (!$this->healthCheck->isReady()) {
            return new JsonResponse(['status' => 'unavailable'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse(['status' => 'ok']);
    }
}
