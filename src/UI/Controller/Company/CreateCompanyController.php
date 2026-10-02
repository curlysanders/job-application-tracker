<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Company;

use CurlySanders\JobApplicationTracker\Application\Company\Command\CreateCompany;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\UI\Controller\AuthenticatedUserTrait;
use CurlySanders\JobApplicationTracker\UI\Controller\Management\AbstractManagementFormController;
use CurlySanders\JobApplicationTracker\UI\Form\Company\CompanyType;
use CurlySanders\JobApplicationTracker\UI\Form\DirectContactInputFactory;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Company\CompanyData;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class CreateCompanyController extends AbstractManagementFormController
{
    use AuthenticatedUserTrait;

    public function __construct(private Security $security, private CommandBus $commandBus, FormFactoryInterface $forms, Environment $twig, UrlGeneratorInterface $urls)
    {
        parent::__construct($forms, $twig, $urls);
    }

    #[Route('/companies/new', name: 'app_company_create', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $data = new CompanyData();
        $form = $this->forms->create(CompanyType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new CreateCompany($this->userId(), $data->name ?? '', $data->website, $data->industry, DirectContactInputFactory::fromForm($data->directContacts)));

            return $this->redirectWithSuccess($request, 'app_company_list', 'Company created.', 'Company');
        }

        return $this->formResponse($form, 'company/form.html.twig', 'Add company', 'Create company');
    }

    private function userId(): string
    {
        return $this->requireAuthenticatedUser('Company management requires an authenticated user.')->getId()->toRfc4122();
    }
}
