<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use PHPUnit\Framework\TestCase;

final class AiDtoValidationTest extends TestCase
{
    public function testInputRejectsBlankAndOversizedText(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CandidateFitInput('', 'Resume');
    }

    public function testResultRejectsInvalidNestedObjectTypes(): void
    {
        $result = new VacancyExtractionResult();
        /* @phpstan-ignore-next-line Deliberately malformed deserialization payload. */
        $result->proposals = ['not-a-proposal'];

        $this->expectException(\InvalidArgumentException::class);

        $result->assertValid();
    }
}
