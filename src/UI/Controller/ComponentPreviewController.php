<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller;

use CurlySanders\JobApplicationTracker\UI\Form\Model\TechStack\TechStackTagsData;
use CurlySanders\JobApplicationTracker\UI\Form\TechStack\TechStackTagsType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class ComponentPreviewController
{
    public function __construct(
        private RequestStack $requestStack,
        private Environment $twig,
        private FormFactoryInterface $forms,
    ) {
    }

    #[Route('/_components', name: 'app_component_preview', methods: ['GET'], env: ['dev', 'test'])]
    public function __invoke(): Response
    {
        $session = $this->requestStack->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            throw new \LogicException('Component previews require a flash-aware session.');
        }

        foreach (['success', 'error', 'warning', 'info'] as $type) {
            $session->getFlashBag()->add($type, ucfirst($type).' notification example.');
        }

        return new Response($this->twig->render('preview/components.html.twig', [
            'techStackForm' => $this->forms->create(TechStackTagsType::class, new TechStackTagsData())->createView(),
        ]));
    }
}
