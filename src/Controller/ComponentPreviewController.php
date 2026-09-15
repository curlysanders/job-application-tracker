<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ComponentPreviewController extends AbstractController
{
    #[Route('/_components', name: 'app_component_preview', methods: ['GET'], env: ['dev', 'test'])]
    public function __invoke(): Response
    {
        foreach (['success', 'error', 'warning', 'info'] as $type) {
            $this->addFlash($type, ucfirst($type).' notification example.');
        }

        return $this->render('preview/components.html.twig');
    }
}
