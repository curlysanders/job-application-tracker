<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Company;

use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\UI\Controller\AuthenticatedUserTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class CompanyListController
{
    use AuthenticatedUserTrait;

    public function __construct(private Security $security, private CompanyRepository $companies, private Environment $twig)
    {
    }

    #[Route('/companies', name: 'app_company_list', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $query = trim($request->query->getString('q'));

        $user = $this->requireAuthenticatedUser('Company management requires an authenticated user.');

        return new Response($this->twig->render('company/list.html.twig', ['companies' => $this->companies->searchOwnedBy($user->getId()->toRfc4122(), $query), 'query' => $query]));
    }
}
