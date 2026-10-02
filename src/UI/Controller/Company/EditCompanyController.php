<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Company;

use CurlySanders\JobApplicationTracker\Application\Company\Command\UpdateCompany;
use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\UI\Controller\AuthenticatedUserTrait;
use CurlySanders\JobApplicationTracker\UI\Controller\Management\AbstractManagementFormController;
use CurlySanders\JobApplicationTracker\UI\Form\Company\CompanyType;
use CurlySanders\JobApplicationTracker\UI\Form\DirectContactInputFactory;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Company\CompanyData;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class EditCompanyController extends AbstractManagementFormController
{
    use AuthenticatedUserTrait;

    public function __construct(private Security $security, private CompanyRepository $companies, private CommandBus $commandBus, FormFactoryInterface $forms, Environment $twig, UrlGeneratorInterface $urls)
    {
        parent::__construct($forms, $twig, $urls);
    }

    #[Route('/companies/{id}/edit', name: 'app_company_edit', methods: ['GET', 'POST'])]
    public function __invoke(string $id, Request $request): Response
    {
        $userId = $this->userId();
        $company = $this->companies->findOwnedBy($id, $userId) ?? throw new NotFoundHttpException('Company not found.');
        $data = $this->dataFrom($company);
        $form = $this->forms->create(CompanyType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new UpdateCompany($userId, $id, $data->name ?? '', $data->website, $data->industry, DirectContactInputFactory::fromForm($data->directContacts)));

            return $this->redirectWithSuccess($request, 'app_company_list', 'Company updated.', 'Company');
        }

        return $this->formResponse($form, 'company/form.html.twig', 'Edit company', 'Save company');
    }

    private function userId(): string
    {
        return $this->requireAuthenticatedUser('Company management requires an authenticated user.')->getId()->toRfc4122();
    }

    private function dataFrom(Company $company): CompanyData
    {
        $data = new CompanyData();
        $data->name = $company->getName();
        $data->website = $company->getWebsite();
        $data->industry = $company->getIndustry();
        $data->directContacts = $this->directContactData($company->getDirectContacts());

        return $data;
    }
}
