<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Management;

use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Contact\DirectContactData;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

abstract readonly class AbstractManagementFormController
{
    public function __construct(
        protected FormFactoryInterface $forms,
        protected Environment $twig,
        protected UrlGeneratorInterface $urls,
    ) {
    }

    /**
     * @template TData of object|null
     *
     * @param FormInterface<TData> $form
     */
    protected function formResponse(FormInterface $form, string $template, string $pageTitle, string $submitLabel): Response
    {
        return new Response(
            $this->twig->render($template, [
                'form' => $form->createView(),
                'pageTitle' => $pageTitle,
                'submitLabel' => $submitLabel,
            ]),
            $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
    }

    protected function redirectWithSuccess(Request $request, string $route, string $message, string $managementName): RedirectResponse
    {
        $session = $request->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            throw new \LogicException(sprintf('%s management requires a flash-aware session.', $managementName));
        }

        $session->getFlashBag()->add('success', $message);

        return new RedirectResponse($this->urls->generate($route));
    }

    /**
     * @param iterable<DirectContact> $contacts
     *
     * @return list<DirectContactData>
     */
    protected function directContactData(iterable $contacts): array
    {
        $rows = [];
        foreach ($contacts as $contact) {
            $row = new DirectContactData();
            $row->name = $contact->getName();
            $row->email = $contact->getEmail();
            $row->phone = $contact->getPhone();
            $row->linkedinUrl = $contact->getLinkedinUrl();
            $rows[] = $row;
        }

        return $rows;
    }
}
