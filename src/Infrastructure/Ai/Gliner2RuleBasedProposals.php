<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyCanonicalEvidence;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldProposal;

final class Gliner2RuleBasedProposals
{
    public static function addTo(
        VacancyExtractionResult $result,
        string $vacancyText,
    ): void {
        foreach (VacancyCanonicalEvidence::PHRASES as $field => $values) {
            foreach ($values as $value => $phrases) {
                foreach ($phrases as $phrase) {
                    $start = mb_stripos($vacancyText, $phrase);
                    if (false !== $start) {
                        self::add($result, new VacancyFieldProposal($field, $value, mb_substr($vacancyText, $start, mb_strlen($phrase)), 1.0, $start, $start + mb_strlen($phrase)));
                        continue 3;
                    }
                }
            }
        }

        if (1 !== preg_match('/(€)\s*(\d+(?:[.,]\d{1,2})?)\s*(?:tot|to|-)\s*€?\s*(\d+(?:[.,]\d{1,2})?)/iu', $vacancyText, $salary, PREG_OFFSET_CAPTURE)) {
            return;
        }
        /* @var array<int, array{string, int}> $salary */
        foreach (['minimumSalary' => 2, 'maximumSalary' => 3, 'currencyCode' => 1] as $field => $index) {
            [$excerpt, $byteOffset] = $salary[$index];
            $start = mb_strlen(substr($vacancyText, 0, $byteOffset));
            $value = 'currencyCode' === $field ? 'EUR' : str_replace(',', '.', $excerpt);
            self::add($result, new VacancyFieldProposal($field, $value, $excerpt, 1.0, $start, $start + mb_strlen($excerpt)));
        }
    }

    private static function add(
        VacancyExtractionResult $result,
        VacancyFieldProposal $proposal,
    ): void {
        if (count($result->proposals) < VacancyExtractionResult::MAX_PROPOSALS && !in_array($proposal->field, array_map(static fn (VacancyFieldProposal $item): string => $item->field, $result->proposals), true)) {
            $result->proposals[] = $proposal;
        }
    }
}
