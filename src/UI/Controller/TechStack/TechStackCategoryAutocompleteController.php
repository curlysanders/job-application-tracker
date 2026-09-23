<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\TechStack;

use CurlySanders\JobApplicationTracker\Application\TechStack\TechStackRepository;
use CurlySanders\JobApplicationTracker\UI\Autocomplete\PaginatedAutocompleteResponder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class TechStackCategoryAutocompleteController
{
    public function __construct(
        private TechStackRepository $techStacks,
        private PaginatedAutocompleteResponder $responder,
    ) {
    }

    #[Route('/app/tech-stack-categories/autocomplete', name: 'app_tech_stack_category_autocomplete', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        return $this->responder->respond(
            $request,
            'app_tech_stack_category_autocomplete',
            $this->techStacks->categories(...),
            static fn (string $category): array => ['value' => $category, 'text' => $category],
        );
    }
}
