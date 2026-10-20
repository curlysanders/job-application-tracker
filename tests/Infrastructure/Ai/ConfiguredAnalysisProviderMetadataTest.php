<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Infrastructure\Ai\ConfiguredAnalysisProviderMetadata;
use PHPUnit\Framework\TestCase;

final class ConfiguredAnalysisProviderMetadataTest extends TestCase
{
    public function testReportsFakeMetadataWithoutRequiringALocalModel(): void
    {
        $metadata = new ConfiguredAnalysisProviderMetadata('fake', '');

        self::assertSame('fake', $metadata->provider());
        self::assertSame('fake', $metadata->model());
    }

    public function testValidatesTheLocalModelAndProvider(): void
    {
        self::assertSame('qwen3:0.6b', new ConfiguredAnalysisProviderMetadata('local', ' qwen3:0.6b ')->model());

        $this->expectException(\LogicException::class);
        new ConfiguredAnalysisProviderMetadata('unknown', 'qwen3:0.6b')->provider();
    }
}
