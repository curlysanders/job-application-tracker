<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\TechStack;

use CurlySanders\JobApplicationTracker\Application\TechStack\TechStackRepository;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use CurlySanders\JobApplicationTracker\UI\Autocomplete\PaginatedAutocompleteResponder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class TechStackAutocompleteController
{
    public function __construct(
        private TechStackRepository $techStacks,
        private PaginatedAutocompleteResponder $responder,
    ) {
    }

    #[Route('/app/tech-stacks/autocomplete', name: 'app_tech_stack_autocomplete', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        return $this->responder->respond(
            $request,
            'app_tech_stack_autocomplete',
            $this->techStacks->search(...),
            static fn (TechStack $techStack): array => [
                'value' => $techStack->getSlug(),
                'text' => sprintf('%s (%s)', $techStack->getName(), $techStack->getCategory()),
            ],
        );
    }
}
