<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Company;

use CurlySanders\JobApplicationTracker\Application\Company\Command\CreateCompany;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\UI\Form\Company\CompanyType;
use CurlySanders\JobApplicationTracker\UI\Form\DirectContactInputFactory;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Company\CompanyData;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class CreateCompanyController
{
    public function __construct(private CommandBus $commandBus, private FormFactoryInterface $forms, private Environment $twig, private UrlGeneratorInterface $urls)
    {
    }

    #[Route('/app/companies/new', name: 'app_company_create', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $data = new CompanyData();
        $form = $this->forms->create(CompanyType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new CreateCompany($data->name ?? '', $data->website, $data->industry, DirectContactInputFactory::fromForm($data->directContacts)));

            return $this->redirect($request, 'Company created.');
        }

        return new Response($this->twig->render('company/form.html.twig', ['form' => $form->createView(), 'pageTitle' => 'Add company', 'submitLabel' => 'Create company']), $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }

    private function redirect(Request $request, string $message): RedirectResponse
    {
        $session = $request->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            throw new \LogicException('Company management requires a flash-aware session.');
        } $session->getFlashBag()->add('success', $message);

        return new RedirectResponse($this->urls->generate('app_company_list'));
    }
}
