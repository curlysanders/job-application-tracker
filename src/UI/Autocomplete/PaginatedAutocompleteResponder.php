<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Autocomplete;

use CurlySanders\JobApplicationTracker\Application\TechStack\PaginatedResults;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PaginatedAutocompleteResponder
{
    public function __construct(private UrlGeneratorInterface $urls)
    {
    }

    /**
     * @template T
     *
     * @param callable(string, int): PaginatedResults<T>      $fetchResults
     * @param callable(T): array{value: string, text: string} $mapResult
     */
    public function respond(Request $request, string $routeName, callable $fetchResults, callable $mapResult): JsonResponse
    {
        $query = trim($request->query->getString('query'));
        $page = max(1, $request->query->getInt('page', 1));
        $results = $fetchResults($query, $page);

        $response = ['results' => array_map($mapResult, $results->items)];

        if ($results->hasNextPage) {
            $response['next_page'] = $this->urls->generate($routeName, [
                'query' => $query,
                'page' => $page + 1,
            ]);
        }

        return new JsonResponse($response);
    }
}
