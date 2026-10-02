<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Recruiter;

use CurlySanders\JobApplicationTracker\Application\Recruiter\Command\UpdateRecruiter;
use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\UI\Controller\AuthenticatedUserTrait;
use CurlySanders\JobApplicationTracker\UI\Controller\Management\AbstractManagementFormController;
use CurlySanders\JobApplicationTracker\UI\Form\DirectContactInputFactory;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Recruiter\RecruiterData;
use CurlySanders\JobApplicationTracker\UI\Form\Recruiter\RecruiterType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class EditRecruiterController extends AbstractManagementFormController
{
    use AuthenticatedUserTrait;

    public function __construct(private Security $security, private RecruiterRepository $recruiters, private CommandBus $commandBus, FormFactoryInterface $forms, Environment $twig, UrlGeneratorInterface $urls)
    {
        parent::__construct($forms, $twig, $urls);
    }

    #[Route('/recruiters/{id}/edit', name: 'app_recruiter_edit', methods: ['GET', 'POST'])]
    public function __invoke(string $id, Request $request): Response
    {
        $userId = $this->userId();
        $recruiter = $this->recruiters->findOwnedBy($id, $userId) ?? throw new NotFoundHttpException('Recruiter not found.');
        $data = $this->dataFrom($recruiter);
        $form = $this->forms->create(RecruiterType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new UpdateRecruiter($userId, $id, $data->agencyName ?? '', $data->website, DirectContactInputFactory::fromForm($data->directContacts)));

            return $this->redirectWithSuccess($request, 'app_recruiter_list', 'Recruiter updated.', 'Recruiter');
        }

        return $this->formResponse($form, 'recruiter/form.html.twig', 'Edit recruiter', 'Save recruiter');
    }

    private function userId(): string
    {
        return $this->requireAuthenticatedUser('Recruiter management requires an authenticated user.')->getId()->toRfc4122();
    }

    private function dataFrom(Recruiter $recruiter): RecruiterData
    {
        $data = new RecruiterData();
        $data->agencyName = $recruiter->getAgencyName();
        $data->website = $recruiter->getWebsite();
        $data->directContacts = $this->directContactData($recruiter->getDirectContacts());

        return $data;
    }
}
