<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldProposal;

final readonly class LocalOllamaVacancyExtractionProvider implements VacancyExtractionProvider
{
    public function __construct(
        private LocalOllamaInference $inference,
        private ?NuExtractVacancyExtractionProvider $nuExtract = null,
    ) {
    }

    public function extract(VacancyExtractionInput $input): VacancyExtractionResult
    {
        if (NuExtractVacancyExtractionProvider::supports($this->inference->model())) {
            return ($this->nuExtract ?? throw new \LogicException('The NuExtract provider is unavailable.'))->extract($input);
        }

        /** @var VacancyExtractionResult $result */
        $result = $this->inference->invoke(
            sprintf(
                'Extract only evidence stated in the vacancy. Return at most %d field proposals with exact source excerpts. Omit every field without explicit evidence; never emit blank values or excerpts as placeholders, never repeat a field, and never invent values. Use only these normalized field names: %s. contractType must be one of %s; normalize permanent employment (including Dutch vast dienstverband) to PERMANENT. workMode must be one of %s; normalize hybrid or partly home-based work to HYBRID, and never use working hours as a work mode. Emit recruiterAgencyName only for an explicitly named intermediary; when it is the same organization as companyName, emit only companyName. Emit date fields only when the text gives a date, and use YYYY-MM-DD.',
                VacancyExtractionResult::MAX_PROPOSALS,
                implode(', ', VacancyFieldProposal::ALLOWED_FIELDS),
                implode(', ', array_map(static fn (\CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType $type): string => $type->value, \CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType::cases())),
                implode(', ', array_map(static fn (\CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode $mode): string => $mode->value, \CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode::cases())),
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
