<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\TechStackSuggestion;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldProposal;

final class NuExtractVacancyResponseMapper
{
    /** @var array<string, array<string, list<string>>> */
    private const array CANONICAL_EVIDENCE = [
        'contractType' => [
            'PERMANENT' => ['permanent', 'vast', 'onbepaalde tijd'],
            'FIXED_TERM' => ['fixed term', 'tijdelijk', 'bepaalde tijd'],
            'CONTRACT' => ['contract', 'overeenkomst'],
            'FREELANCE' => ['freelance', 'zzp'],
            'INTERNSHIP' => ['internship', 'stage'],
            'PART_TIME' => ['part time', 'deeltijd'],
        ],
        'workMode' => [
            'REMOTE' => ['remote', 'op afstand'],
            'HYBRID' => ['hybrid', 'hybride', 'partly home', 'deels thuis', 'remote days'],
            'ONSITE' => ['onsite', 'on-site', 'op locatie', 'op kantoor', 'office'],
        ],
    ];

    public function map(
        string $response,
        string $vacancyText,
    ): VacancyExtractionResult {
        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('NuExtract returned invalid JSON.', previous: $exception);
        }
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new \RuntimeException('NuExtract returned an invalid extraction object.');
        }

        $result = new VacancyExtractionResult();
        foreach (VacancyFieldProposal::ALLOWED_FIELDS as $field) {
            $value = $decoded[$field] ?? null;
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                continue;
            }
            $value = trim((string) $value);
            if ('' === $value) {
                continue;
            }
            [$value, $excerpt] = $this->groundedValue($field, $value, $vacancyText);
            if (null === $excerpt) {
                continue;
            }
            $result->proposals[] = new VacancyFieldProposal($field, $value, $excerpt, 0.99);
        }

        $techStacks = $decoded['techStacks'] ?? [];
        if (is_array($techStacks)) {
            foreach ($techStacks as $name) {
                if (!is_string($name) || '' === trim($name) || null === $excerpt = $this->verbatimExcerpt($name, $vacancyText)) {
                    continue;
                }
                $result->techStacks[] = new TechStackSuggestion(trim($name), $excerpt, 0.99);
            }
        }

        $this->removeDuplicateRecruiterAgency($result);
        $result->assertValid();

        return $result;
    }

    /** @return array{string, string|null} */
    private function groundedValue(
        string $field,
        string $value,
        string $vacancyText,
    ): array {
        if (isset(self::CANONICAL_EVIDENCE[$field])) {
            foreach (self::CANONICAL_EVIDENCE[$field] as $canonical => $phrases) {
                foreach ($phrases as $phrase) {
                    if (null !== $excerpt = $this->verbatimExcerpt($phrase, $vacancyText)) {
                        return [$canonical, $excerpt];
                    }
                }
            }

            return [$value, null];
        }

        return [$value, $this->verbatimExcerpt($value, $vacancyText)];
    }

    private function verbatimExcerpt(
        string $needle,
        string $haystack,
    ): ?string {
        $offset = mb_stripos($haystack, trim($needle));
        if (false === $offset) {
            return null;
        }

        return mb_substr($haystack, $offset, min(mb_strlen(trim($needle)), 1_000));
    }

    private function removeDuplicateRecruiterAgency(VacancyExtractionResult $result): void
    {
        $companyName = null;
        foreach ($result->proposals as $proposal) {
            if ('companyName' === $proposal->field && is_string($proposal->value)) {
                $companyName = self::normalizeOrganizationName($proposal->value);
                break;
            }
        }
        if (null === $companyName) {
            return;
        }

        $result->proposals = array_values(array_filter(
            $result->proposals,
            static fn (VacancyFieldProposal $proposal): bool => 'recruiterAgencyName' !== $proposal->field || !is_string($proposal->value) || self::normalizeOrganizationName($proposal->value) !== $companyName,
        ));
    }

    private static function normalizeOrganizationName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');
    }
}
