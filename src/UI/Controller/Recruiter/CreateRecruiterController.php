<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Recruiter;

use CurlySanders\JobApplicationTracker\Application\Recruiter\Command\CreateRecruiter;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\UI\Controller\Management\AbstractManagementFormController;
use CurlySanders\JobApplicationTracker\UI\Form\DirectContactInputFactory;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Recruiter\RecruiterData;
use CurlySanders\JobApplicationTracker\UI\Form\Recruiter\RecruiterType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class CreateRecruiterController extends AbstractManagementFormController
{
    public function __construct(private CommandBus $commandBus, FormFactoryInterface $forms, Environment $twig, UrlGeneratorInterface $urls)
    {
        parent::__construct($forms, $twig, $urls);
    }

    #[Route('/recruiters/new', name: 'app_recruiter_create', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $data = new RecruiterData();
        $form = $this->forms->create(RecruiterType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new CreateRecruiter($data->agencyName ?? '', $data->website, DirectContactInputFactory::fromForm($data->directContacts)));

            return $this->redirectWithSuccess($request, 'app_recruiter_list', 'Recruiter created.', 'Recruiter');
        }

        return $this->formResponse($form, 'recruiter/form.html.twig', 'Add recruiter', 'Create recruiter');
    }
}
