<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\ConfiguredAnalysisProviderMetadata;
use PHPUnit\Framework\TestCase;

final class ConfiguredAnalysisProviderMetadataTest extends TestCase
{
    public function testReportsIndependentProviderMetadata(): void
    {
        $metadata = new ConfiguredAnalysisProviderMetadata('gliner', 'local', ' qwen3:0.6b ');

        self::assertSame('gliner', $metadata->provider(AnalysisKind::VacancyExtraction));
        self::assertSame('fastino/gliner2.5-multi-v1', $metadata->model(AnalysisKind::VacancyExtraction));
        self::assertSame('local', $metadata->provider(AnalysisKind::CandidateFit));
        self::assertSame('qwen3:0.6b', $metadata->model(AnalysisKind::CandidateFit));
    }

    public function testRejectsGlinerForCandidateFit(): void
    {
        $metadata = new ConfiguredAnalysisProviderMetadata('gliner', 'gliner', '');

        $this->expectException(\LogicException::class);
        $metadata->provider(AnalysisKind::CandidateFit);
    }

    public function testFakeDoesNotRequireALocalModel(): void
    {
        $metadata = new ConfiguredAnalysisProviderMetadata('gliner', 'fake', '');

        self::assertSame('fake', $metadata->model(AnalysisKind::CandidateFit));
    }
}
