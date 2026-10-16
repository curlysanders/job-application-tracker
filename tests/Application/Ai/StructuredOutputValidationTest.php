<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldProposal;
use PHPUnit\Framework\TestCase;

final class StructuredOutputValidationTest extends TestCase
{
    public function testAnUnsupportedFieldAndMissingEvidenceAreRejected(): void
    {
        $proposal = new VacancyFieldProposal('status', 'APPLIED', '', 1.2);

        $this->expectException(\InvalidArgumentException::class);

        $proposal->assertValid();
    }
}
