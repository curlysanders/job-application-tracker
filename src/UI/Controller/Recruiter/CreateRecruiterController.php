<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Recruiter;

use CurlySanders\JobApplicationTracker\Application\Recruiter\Command\CreateRecruiter;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\UI\Form\DirectContactInputFactory;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Recruiter\RecruiterData;
use CurlySanders\JobApplicationTracker\UI\Form\Recruiter\RecruiterType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class CreateRecruiterController
{
    public function __construct(private CommandBus $commandBus, private FormFactoryInterface $forms, private Environment $twig, private UrlGeneratorInterface $urls)
    {
    }

    #[Route('/app/recruiters/new', name: 'app_recruiter_create', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $data = new RecruiterData();
        $form = $this->forms->create(RecruiterType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new CreateRecruiter($data->agencyName ?? '', $data->website, DirectContactInputFactory::fromForm($data->directContacts)));

            return $this->redirect($request);
        }

        return new Response($this->twig->render('recruiter/form.html.twig', ['form' => $form->createView(), 'pageTitle' => 'Add recruiter', 'submitLabel' => 'Create recruiter']), $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }

    private function redirect(Request $request): RedirectResponse
    {
        $session = $request->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            throw new \LogicException('Recruiter management requires a flash-aware session.');
        } $session->getFlashBag()->add('success', 'Recruiter created.');

        return new RedirectResponse($this->urls->generate('app_recruiter_list'));
    }
}
