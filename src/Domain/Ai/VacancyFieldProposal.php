<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

use Symfony\Component\Validator\Constraints as Assert;

final class VacancyFieldProposal
{
    /** @param scalar|list<scalar> $value */
    public function __construct(
        #[Assert\Choice(choices: ['title', 'companyName', 'location', 'contractType', 'datePosted', 'deadline', 'minimumSalary', 'maximumSalary', 'currencyCode', 'workMode', 'hybridDetails', 'requirements', 'responsibilities', 'preferredQualifications', 'aboutJob', 'aboutCompany', 'compensationBenefits'])]
        public string $field = '',
        #[Assert\NotBlank]
        public string|int|float|bool|array $value = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 1_000)]
        public string $excerpt = '',
        #[Assert\Range(min: 0, max: 1)]
        public float $confidence = 0.0,
    ) {
    }
}
