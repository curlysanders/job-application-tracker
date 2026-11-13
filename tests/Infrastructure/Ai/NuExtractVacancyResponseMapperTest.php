<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Infrastructure\Ai\NuExtractVacancyResponseMapper;
use PHPUnit\Framework\TestCase;

final class NuExtractVacancyResponseMapperTest extends TestCase
{
    public function testItKeepsOnlyGroundedValuesAndNormalizesCanonicalEnumsFromEvidence(): void
    {
        $result = new NuExtractVacancyResponseMapper()->map(json_encode([
            'title' => 'PHP ontwikkelaar',
            'companyName' => 'Acme BV',
            'recruiterAgencyName' => 'Acme BV',
            'contractType' => 'FIXED_TERM',
            'workMode' => 'REMOTE',
            'location' => 'Utrecht',
            'techStacks' => ['PHP', 'Symfony', 'Invented'],
        ], JSON_THROW_ON_ERROR), 'Acme BV zoekt een PHP ontwikkelaar in Utrecht voor een vast dienstverband met deels thuiswerken. PHP en Symfony ervaring zijn vereist.');

        self::assertSame([
            'title' => 'PHP ontwikkelaar',
            'companyName' => 'Acme BV',
            'location' => 'Utrecht',
            'contractType' => 'PERMANENT',
            'workMode' => 'HYBRID',
        ], array_column($result->proposals, 'value', 'field'));
        self::assertSame(['PHP', 'Symfony'], array_column($result->techStacks, 'name'));
    }
}
