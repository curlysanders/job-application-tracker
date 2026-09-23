<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\UI\Autocomplete;

use CurlySanders\JobApplicationTracker\Application\TechStack\PaginatedResults;
use CurlySanders\JobApplicationTracker\UI\Autocomplete\PaginatedAutocompleteResponder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PaginatedAutocompleteResponderTest extends TestCase
{
    public function testBuildsNextPageResponseWithNormalisedQueryAndPage(): void
    {
        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects(self::once())
            ->method('generate')
            ->with('app_tech_stack_autocomplete', ['query' => 'PHP', 'page' => 2])
            ->willReturn('/app/tech-stacks/autocomplete?query=PHP&page=2');
        $responder = new PaginatedAutocompleteResponder($urls);

        $requestedQuery = null;
        $requestedPage = null;
        $response = $responder->respond(
            Request::create('/app/tech-stacks/autocomplete?query=%20PHP%20&page=0'),
            'app_tech_stack_autocomplete',
            static function (string $query, int $page) use (&$requestedQuery, &$requestedPage): PaginatedResults {
                $requestedQuery = $query;
                $requestedPage = $page;

                return new PaginatedResults(['php'], true);
            },
            static fn (string $technology): array => ['value' => $technology, 'text' => strtoupper($technology)],
        );

        self::assertSame('PHP', $requestedQuery);
        self::assertSame(1, $requestedPage);
        self::assertJsonStringEqualsJsonString(
            '{"results":[{"value":"php","text":"PHP"}],"next_page":"/app/tech-stacks/autocomplete?query=PHP&page=2"}',
            (string) $response->getContent(),
        );
    }

    public function testOmitsNextPageWhenResultsAreExhausted(): void
    {
        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects(self::never())->method('generate');
        $responder = new PaginatedAutocompleteResponder($urls);

        $response = $responder->respond(
            Request::create('/app/tech-stack-categories/autocomplete?query=back&page=2'),
            'app_tech_stack_category_autocomplete',
            static fn (string $query, int $page): PaginatedResults => new PaginatedResults([$query.$page], false),
            static fn (string $category): array => ['value' => $category, 'text' => $category],
        );

        self::assertJsonStringEqualsJsonString('{"results":[{"value":"back2","text":"back2"}]}', (string) $response->getContent());
    }
}
