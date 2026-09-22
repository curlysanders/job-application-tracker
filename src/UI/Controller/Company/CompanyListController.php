<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Company;

use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class CompanyListController
{
    public function __construct(private CompanyRepository $companies, private Environment $twig)
    {
    }

    #[Route('/app/companies', name: 'app_company_list', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $query = trim($request->query->getString('q'));

        return new Response($this->twig->render('company/list.html.twig', ['companies' => $this->companies->search($query), 'query' => $query]));
    }
}
