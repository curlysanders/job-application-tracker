<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

final class VacancyExtractionResult
{
    public const MAX_PROPOSALS = 9;

    /** @var list<VacancyFieldProposal> */
    public array $proposals = [];

    /** @var list<TechStackSuggestion> */
    public array $techStacks = [];

    public function assertValid(): void
    {
        AiValidation::listOf($this->proposals, VacancyFieldProposal::class, 'Proposals');
        AiValidation::listOf($this->techStacks, TechStackSuggestion::class, 'Technology suggestions');
        if (self::MAX_PROPOSALS < count($this->proposals)) {
            throw new \InvalidArgumentException(sprintf('Proposals must contain no more than %d entries.', self::MAX_PROPOSALS));
        }
        $seenFields = [];
        $organizationNames = [];
        foreach ($this->proposals as $proposal) {
            $proposal->assertValid();
            if (isset($seenFields[$proposal->field])) {
                throw new \InvalidArgumentException(sprintf('Only one proposal for "%s" is allowed.', $proposal->field));
            }
            $seenFields[$proposal->field] = true;
            if (in_array($proposal->field, ['companyName', 'recruiterAgencyName'], true)) {
                if (!is_string($proposal->value)) {
                    throw new \LogicException('Organization proposal values must be strings.');
                }
                $organizationNames[$proposal->field] = self::normalizeOrganizationName($proposal->value);
            }
        }
        if (isset($organizationNames['companyName'], $organizationNames['recruiterAgencyName']) && $organizationNames['companyName'] === $organizationNames['recruiterAgencyName']) {
            throw new \InvalidArgumentException('Company and recruiter agency proposals must not duplicate the same organization.');
        }
        foreach ($this->techStacks as $suggestion) {
            $suggestion->assertValid();
        }
    }

    private static function normalizeOrganizationName(string $name): string
    {
        return mb_strtolower(preg_replace('/\\s+/', ' ', trim($name)) ?? '');
    }
}
