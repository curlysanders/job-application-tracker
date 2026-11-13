<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Domain\Ai\OrganizationName;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldProposal;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;

final readonly class LocalOllamaVacancyExtractionProvider implements VacancyExtractionProvider
{
    public function __construct(private LocalOllamaInference $inference)
    {
    }

    public function extract(VacancyExtractionInput $input): VacancyExtractionResult
    {
        /** @var VacancyExtractionResult $result */
        $result = $this->inference->invoke(
            sprintf(
                'Extract only evidence stated in the vacancy. Return at most %d field proposals with exact source excerpts. Omit every field without explicit evidence; never emit blank values or excerpts as placeholders, never repeat a field, and never invent values. Use only these normalized field names: %s. contractType must be one of %s; normalize permanent employment (including Dutch vast dienstverband) to PERMANENT. workMode must be one of %s; normalize hybrid or partly home-based work to HYBRID, and never use working hours as a work mode. Emit recruiterAgencyName only for an explicitly named intermediary; when it is the same organization as companyName, emit only companyName. Emit date fields only when the text gives a date, and use YYYY-MM-DD.',
                VacancyExtractionResult::MAX_PROPOSALS,
                implode(', ', VacancyFieldProposal::ALLOWED_FIELDS),
                implode(', ', array_map(static fn (ContractType $type): string => $type->value, ContractType::cases())),
                implode(', ', array_map(static fn (WorkMode $mode): string => $mode->value, WorkMode::cases())),
            ),
            $input->vacancyText,
            VacancyExtractionResult::class,
        );

        $this->removeDuplicateRecruiterAgency($result);
        $result->assertValid();

        return $result;
    }

    private function removeDuplicateRecruiterAgency(VacancyExtractionResult $result): void
    {
        $companyName = null;
        foreach ($result->proposals as $proposal) {
            if ('companyName' === $proposal->field && is_string($proposal->value)) {
                $companyName = OrganizationName::normalize($proposal->value);
                break;
            }
        }
        if (null === $companyName) {
            return;
        }

        $result->proposals = array_values(array_filter(
            $result->proposals,
            static fn (VacancyFieldProposal $proposal): bool => 'recruiterAgencyName' !== $proposal->field || !is_string($proposal->value) || OrganizationName::normalize($proposal->value) !== $companyName,
        ));
    }
}
