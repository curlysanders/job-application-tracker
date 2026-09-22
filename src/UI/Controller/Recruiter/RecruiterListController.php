<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Recruiter;

use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class RecruiterListController
{
    public function __construct(private RecruiterRepository $recruiters, private Environment $twig)
    {
    }

    #[Route('/app/recruiters', name: 'app_recruiter_list', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $query = trim($request->query->getString('q'));

        return new Response($this->twig->render('recruiter/list.html.twig', ['recruiters' => $this->recruiters->search($query), 'query' => $query]));
    }
}
