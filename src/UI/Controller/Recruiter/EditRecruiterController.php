<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Recruiter;

use CurlySanders\JobApplicationTracker\Application\Recruiter\Command\UpdateRecruiter;
use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\UI\Form\DirectContactInputFactory;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Contact\DirectContactData;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Recruiter\RecruiterData;
use CurlySanders\JobApplicationTracker\UI\Form\Recruiter\RecruiterType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class EditRecruiterController
{
    public function __construct(private RecruiterRepository $recruiters, private CommandBus $commandBus, private FormFactoryInterface $forms, private Environment $twig, private UrlGeneratorInterface $urls)
    {
    }

    #[Route('/app/recruiters/{id}/edit', name: 'app_recruiter_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function __invoke(int $id, Request $request): Response
    {
        $recruiter = $this->recruiters->find($id) ?? throw new NotFoundHttpException('Recruiter not found.');
        $data = $this->dataFrom($recruiter);
        $form = $this->forms->create(RecruiterType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new UpdateRecruiter($id, $data->agencyName ?? '', $data->website, DirectContactInputFactory::fromForm($data->directContacts)));

            return $this->redirect($request);
        }

        return new Response($this->twig->render('recruiter/form.html.twig', ['form' => $form->createView(), 'pageTitle' => 'Edit recruiter', 'submitLabel' => 'Save recruiter']), $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }

    private function dataFrom(Recruiter $recruiter): RecruiterData
    {
        $data = new RecruiterData();
        $data->agencyName = $recruiter->getAgencyName();
        $data->website = $recruiter->getWebsite();
        foreach ($recruiter->getDirectContacts() as $contact) {
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
            throw new \LogicException('Recruiter management requires a flash-aware session.');
        } $session->getFlashBag()->add('success', 'Recruiter updated.');

        return new RedirectResponse($this->urls->generate('app_recruiter_list'));
    }
}
