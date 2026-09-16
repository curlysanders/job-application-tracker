<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class DashboardController
{
    public function __construct(private Environment $twig)
    {
    }

    #[Route('/app', name: 'app_dashboard', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response($this->twig->render('dashboard/index.html.twig'));
    }
}
