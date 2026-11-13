<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\TechStackSuggestion;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldProposal;

final class Gliner2VacancyResponseMapper
{
    private const float MINIMUM_CONFIDENCE = 0.80;
    private const int MAX_TECHNOLOGIES = 12;

    /** @var array<string, array<string, list<string>>> */
    private const array CANONICAL_EVIDENCE = [
        'contractType' => [
            'PERMANENT' => ['permanent', 'vast', 'onbepaalde tijd'],
            'FIXED_TERM' => ['fixed term', 'tijdelijk', 'bepaalde tijd'],
            'CONTRACT' => ['contract', 'overeenkomst'],
            'FREELANCE' => ['freelance', 'zzp'],
            'INTERNSHIP' => ['internship', 'stage'],
            'PART_TIME' => ['part-time', 'parttime', 'deeltijd'],
        ],
        'workMode' => [
            'REMOTE' => ['remote', 'op afstand'],
            'HYBRID' => ['hybrid', 'hybride', 'partly home', 'deels thuis', 'remote days'],
            'ONSITE' => ['onsite', 'on-site', 'op locatie', 'op kantoor', 'office'],
        ],
    ];

    /** @param list<Gliner2Entity> $entities */
    public function map(
        array $entities,
        string $vacancyText,
    ): VacancyExtractionResult {
        $result = new VacancyExtractionResult();
        $byKind = [];
        foreach ($entities as $entity) {
            if ($entity->confidence >= self::MINIMUM_CONFIDENCE) {
                $byKind[$entity->kind][] = $entity;
            }
        }
        foreach ($byKind as &$candidates) {
            usort($candidates, static function (
                Gliner2Entity $left,
                Gliner2Entity $right,
            ): int {
                $confidence = $right->confidence <=> $left->confidence;

                return 0 === $confidence ? $left->start <=> $right->start : $confidence;
            });
        }
        unset($candidates);

        foreach (['title' => 'title', 'company' => 'companyName', 'recruiter' => 'recruiterAgencyName', 'location' => 'location'] as $kind => $field) {
            $entity = $byKind[$kind][0] ?? null;
            if (!$entity instanceof Gliner2Entity || ('recruiter' === $kind && $this->isGenericRecruiter($entity->text))) {
                continue;
            }
            $this->addProposal($result, new VacancyFieldProposal($field, $entity->text, $entity->text, $entity->confidence, $entity->start, $entity->end));
        }

        foreach ($byKind['technology'] ?? [] as $entity) {
            if (self::MAX_TECHNOLOGIES <= count($result->techStacks)) {
                break;
            }
            if (!in_array(mb_strtolower($entity->text), array_map(static fn (TechStackSuggestion $suggestion): string => mb_strtolower($suggestion->name), $result->techStacks), true)) {
                $result->techStacks[] = new TechStackSuggestion($entity->text, $entity->text, $entity->confidence, $entity->start, $entity->end);
            }
        }

        foreach (self::CANONICAL_EVIDENCE as $field => $values) {
            foreach ($values as $value => $phrases) {
                foreach ($phrases as $phrase) {
                    $start = mb_stripos($vacancyText, $phrase);
                    if (false !== $start) {
                        $this->addProposal($result, new VacancyFieldProposal($field, $value, mb_substr($vacancyText, $start, mb_strlen($phrase)), 1.0, $start, $start + mb_strlen($phrase)));
                        continue 3;
                    }
                }
            }
        }

        if (1 === preg_match('/€\s*(\d+(?:[.,]\d{1,2})?)\s*(?:tot|to|-)\s*€?\s*(\d+(?:[.,]\d{1,2})?)/iu', $vacancyText, $salary)) {
            $this->addSalaryProposals($result, $vacancyText, $salary[1], $salary[2]);
        }

        $this->removeDuplicateRecruiterAgency($result);
        $result->assertValid();

        return $result;
    }

    private function addSalaryProposals(
        VacancyExtractionResult $result,
        string $vacancyText,
        string $minimum,
        string $maximum,
    ): void {
        foreach (['minimumSalary' => $minimum, 'maximumSalary' => $maximum] as $field => $rawValue) {
            $start = mb_stripos($vacancyText, $rawValue);
            if (false !== $start) {
                $this->addProposal($result, new VacancyFieldProposal($field, str_replace(',', '.', $rawValue), mb_substr($vacancyText, $start, mb_strlen($rawValue)), 1.0, $start, $start + mb_strlen($rawValue)));
            }
        }
        $currencyStart = mb_strpos($vacancyText, '€');
        if (false !== $currencyStart) {
            $this->addProposal($result, new VacancyFieldProposal('currencyCode', 'EUR', '€', 1.0, $currencyStart, $currencyStart + 1));
        }
    }

    private function addProposal(
        VacancyExtractionResult $result,
        VacancyFieldProposal $proposal,
    ): void {
        if (count($result->proposals) < VacancyExtractionResult::MAX_PROPOSALS && !in_array($proposal->field, array_map(static fn (VacancyFieldProposal $item): string => $item->field, $result->proposals), true)) {
            $result->proposals[] = $proposal;
        }
    }

    private function isGenericRecruiter(string $value): bool
    {
        return in_array(mb_strtolower(trim($value)), ['recruiter', 'recruitment agency', 'werving en selectie'], true);
    }

    private function removeDuplicateRecruiterAgency(VacancyExtractionResult $result): void
    {
        $company = null;
        foreach ($result->proposals as $proposal) {
            if ('companyName' === $proposal->field && is_string($proposal->value)) {
                $company = $this->normalizeOrganizationName($proposal->value);
            }
        }
        if (null !== $company) {
            $result->proposals = array_values(array_filter($result->proposals, fn (VacancyFieldProposal $proposal): bool => 'recruiterAgencyName' !== $proposal->field || !is_string($proposal->value) || $company !== $this->normalizeOrganizationName($proposal->value)));
        }
    }

    private function normalizeOrganizationName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');
    }
}
