<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldProposal;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class StructuredOutputValidationTest extends TestCase
{
    public function testAnUnsupportedFieldAndMissingEvidenceAreRejected(): void
    {
        $violations = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate(new VacancyFieldProposal('status', 'APPLIED', '', 1.2));

        self::assertGreaterThanOrEqual(3, $violations->count());
    }
}
