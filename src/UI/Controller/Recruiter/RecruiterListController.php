<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Recruiter;

use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\UI\Controller\AuthenticatedUserTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class RecruiterListController
{
    use AuthenticatedUserTrait;

    public function __construct(private Security $security, private RecruiterRepository $recruiters, private Environment $twig)
    {
    }

    #[Route('/recruiters', name: 'app_recruiter_list', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $query = trim($request->query->getString('q'));

        $user = $this->requireAuthenticatedUser('Recruiter management requires an authenticated user.');

        return new Response($this->twig->render('recruiter/list.html.twig', ['recruiters' => $this->recruiters->searchOwnedBy($user->getId()->toRfc4122(), $query), 'query' => $query]));
    }
}
