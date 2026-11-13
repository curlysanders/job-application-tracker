<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\Gliner2Inference;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\Gliner2VacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\Gliner2VacancyResponseMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

final class Gliner2VacancyExtractionProviderTest extends TestCase
{
    public function testItMapsVerifiedSpansAndCanonicalEvidence(): void
    {
        $text = 'Acme zoekt een PHP ontwikkelaar in Rotterdam. Vast contract, hybride. Symfony en Docker vereist. Salaris €4500 tot €6000.';
        $provider = new Gliner2VacancyExtractionProvider(new Gliner2Inference(new MockHttpClient(new MockResponse(json_encode([
            'entities' => [
                $this->entity('title', 'PHP ontwikkelaar', $text, 0.98),
                $this->entity('company', 'Acme', $text, 0.99),
                $this->entity('location', 'Rotterdam', $text, 0.97),
                $this->entity('technology', 'Symfony', $text, 0.96),
                $this->entity('technology', 'Docker', $text, 0.95),
            ],
        ], JSON_THROW_ON_ERROR))), new LockFactory(new FlockStore(sys_get_temp_dir())), 'http://gliner:8080'), new Gliner2VacancyResponseMapper());

        $result = $provider->extract(new VacancyExtractionInput($text));

        self::assertSame([
            'title' => 'PHP ontwikkelaar',
            'companyName' => 'Acme',
            'location' => 'Rotterdam',
            'contractType' => 'PERMANENT',
            'workMode' => 'HYBRID',
            'minimumSalary' => '4500',
            'maximumSalary' => '6000',
            'currencyCode' => 'EUR',
        ], array_column($result->proposals, 'value', 'field'));
        self::assertSame(['Symfony', 'Docker'], array_column($result->techStacks, 'name'));
        self::assertSame('PHP ontwikkelaar', $result->proposals[0]->excerpt);
        self::assertNotNull($result->proposals[0]->sourceStart);
    }

    public function testItRejectsForgedUnicodeCharacterOffsets(): void
    {
        $text = 'München zoekt een developer.';
        $inference = new Gliner2Inference(new MockHttpClient(new MockResponse(json_encode([
            'entities' => [['kind' => 'location', 'text' => 'München', 'start' => 1, 'end' => 8, 'confidence' => 0.99]],
        ], JSON_THROW_ON_ERROR))), new LockFactory(new FlockStore(sys_get_temp_dir())), 'http://gliner:8080');

        $this->expectException(\RuntimeException::class);
        $inference->extract($text);
    }

    /** @return array{kind: string, text: string, start: int, end: int, confidence: float} */
    private function entity(
        string $kind,
        string $value,
        string $text,
        float $confidence,
    ): array {
        $start = mb_strpos($text, $value);
        self::assertNotFalse($start);

        return ['kind' => $kind, 'text' => $value, 'start' => $start, 'end' => $start + mb_strlen($value), 'confidence' => $confidence];
    }
}
