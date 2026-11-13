<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldProposal;
use CurlySanders\JobApplicationTracker\Kernel;
use Symfony\AI\Platform\StructuredOutput\ResponseFormatFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StructuredOutputValidationTest extends KernelTestCase
{
    public function testProposalsRejectInvalidCanonicalValuesAndSourceSpans(): void
    {
        foreach ([
            new VacancyFieldProposal('title', '', 'x', 1.0),
            new VacancyFieldProposal('contractType', 'UNKNOWN', 'x', 1.0),
            new VacancyFieldProposal('workMode', 'UNKNOWN', 'x', 1.0),
            new VacancyFieldProposal('currencyCode', 'ZZZ', 'x', 1.0),
            new VacancyFieldProposal('deadline', '2026-02-30', 'x', 1.0),
            new VacancyFieldProposal('minimumSalary', '0', 'x', 1.0),
            new VacancyFieldProposal('title', 'x', 'x', 1.0, 5, 2),
        ] as $proposal) {
            try {
                $proposal->assertValid();
                self::fail('An invalid extraction proposal was accepted.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testAnUnsupportedFieldAndMissingEvidenceAreRejected(): void
    {
        $proposal = new VacancyFieldProposal('status', 'APPLIED', '', 1.2);

        $this->expectException(\InvalidArgumentException::class);

        $proposal->assertValid();
    }

    public function testTheStructuredOutputSchemaConstrainsProposalFields(): void
    {
        self::bootKernel();
        $factory = self::getContainer()->get(ResponseFormatFactoryInterface::class);
        self::assertInstanceOf(ResponseFormatFactoryInterface::class, $factory);
        $format = $factory->create(VacancyExtractionResult::class);
        $properties = $format['json_schema']['schema']['properties'] ?? null;
        self::assertIsArray($properties);
        $proposals = $properties['proposals'] ?? null;
        self::assertIsArray($proposals);
        $items = $proposals['items'] ?? null;
        self::assertIsArray($items);
        $branches = $items['oneOf'] ?? null;
        self::assertIsArray($branches);
        $byField = [];
        foreach ($branches as $branch) {
            self::assertIsArray($branch);
            $branchProperties = $branch['properties'] ?? null;
            self::assertIsArray($branchProperties);
            $field = $branchProperties['field'] ?? null;
            self::assertIsArray($field);
            $fieldName = $field['const'] ?? null;
            self::assertIsString($fieldName);
            $byField[$fieldName] = $branch;
        }

        self::assertSame(
            VacancyExtractionResult::MAX_PROPOSALS,
            $proposals['maxItems'],
        );
        self::assertCount(count(VacancyFieldProposal::ALLOWED_FIELDS), $branches);
        self::assertSame(['PERMANENT', 'FIXED_TERM', 'CONTRACT', 'FREELANCE', 'INTERNSHIP', 'PART_TIME'], $this->enum($byField['contractType']));
        self::assertSame(['REMOTE', 'HYBRID', 'ONSITE'], $this->enum($byField['workMode']));
    }

    public function testTooManyProposalsAreRejected(): void
    {
        $result = new VacancyExtractionResult();
        $result->proposals = array_fill(
            0,
            VacancyExtractionResult::MAX_PROPOSALS + 1,
            new VacancyFieldProposal('title', 'PHP developer', 'PHP developer', 1.0),
        );

        $this->expectException(\InvalidArgumentException::class);

        $result->assertValid();
    }

    public function testDuplicateProposalFieldsAndOrganizationsAreRejected(): void
    {
        $result = new VacancyExtractionResult();
        $result->proposals = [
            new VacancyFieldProposal('companyName', 'Acme BV', 'Acme BV', 1.0),
            new VacancyFieldProposal('recruiterAgencyName', ' acme  bv ', 'acme  bv', 1.0),
        ];

        $this->expectException(\InvalidArgumentException::class);

        $result->assertValid();
    }

    public function testNonCanonicalEnumValuesAreRejected(): void
    {
        $proposal = new VacancyFieldProposal('workMode', 'hybride', 'hybride', 1.0);

        $this->expectException(\InvalidArgumentException::class);

        $proposal->assertValid();
    }

    /** @param array<mixed> $branch
     * @return list<string>
     */
    private function enum(array $branch): array
    {
        $properties = $branch['properties'] ?? null;
        self::assertIsArray($properties);
        $value = $properties['value'] ?? null;
        self::assertIsArray($value);
        $enum = $value['enum'] ?? null;
        self::assertIsArray($enum);
        $values = [];
        foreach ($enum as $item) {
            self::assertIsString($item);
            $values[] = $item;
        }

        return $values;
    }
}
