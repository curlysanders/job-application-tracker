<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Company;

use CurlySanders\JobApplicationTracker\Application\Company\Command\UpdateCompany;
use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\UI\Form\Company\CompanyType;
use CurlySanders\JobApplicationTracker\UI\Form\DirectContactInputFactory;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Company\CompanyData;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Contact\DirectContactData;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class EditCompanyController
{
    public function __construct(private CompanyRepository $companies, private CommandBus $commandBus, private FormFactoryInterface $forms, private Environment $twig, private UrlGeneratorInterface $urls)
    {
    }

    #[Route('/app/companies/{id}/edit', name: 'app_company_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function __invoke(int $id, Request $request): Response
    {
        $company = $this->companies->find($id) ?? throw new NotFoundHttpException('Company not found.');
        $data = $this->dataFrom($company);
        $form = $this->forms->create(CompanyType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new UpdateCompany($id, $data->name ?? '', $data->website, $data->industry, DirectContactInputFactory::fromForm($data->directContacts)));

            return $this->redirect($request);
        }

        return new Response($this->twig->render('company/form.html.twig', ['form' => $form->createView(), 'pageTitle' => 'Edit company', 'submitLabel' => 'Save company']), $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }

    private function dataFrom(Company $company): CompanyData
    {
        $data = new CompanyData();
        $data->name = $company->getName();
        $data->website = $company->getWebsite();
        $data->industry = $company->getIndustry();
        foreach ($company->getDirectContacts() as $contact) {
            $row = new DirectContactData();
            $row->name = $contact->getName();
            $row->email = $contact->getEmail();
            $row->phone = $contact->getPhone();
            $row->linkedinUrl = $contact->getLinkedinUrl();
            $data->directContacts[] = $row;
        }

        return $data;
    }

    private function redirect(Request $request): RedirectResponse
    {
        $session = $request->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            throw new \LogicException('Company management requires a flash-aware session.');
        } $session->getFlashBag()->add('success', 'Company updated.');

        return new RedirectResponse($this->urls->generate('app_company_list'));
    }
}
