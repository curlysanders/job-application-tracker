<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\OrganizationName;
use CurlySanders\JobApplicationTracker\Domain\Ai\TechStackSuggestion;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldProposal;

final class Gliner2VacancyResponseMapper
{
    private const float MINIMUM_CONFIDENCE = 0.80;
    private const int MAX_TECHNOLOGIES = 12;

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

        Gliner2RuleBasedProposals::addTo($result, $vacancyText);

        $this->removeDuplicateRecruiterAgency($result);
        $result->assertValid();

        return $result;
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
                $company = OrganizationName::normalize($proposal->value);
            }
        }
        if (null !== $company) {
            $result->proposals = array_values(array_filter($result->proposals, fn (VacancyFieldProposal $proposal): bool => 'recruiterAgencyName' !== $proposal->field || !is_string($proposal->value) || $company !== OrganizationName::normalize($proposal->value)));
        }
    }
}
