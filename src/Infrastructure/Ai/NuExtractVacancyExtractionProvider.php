<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldProposal;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\CurrentIsoCurrencies;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;

final readonly class NuExtractVacancyExtractionProvider
{
    public function __construct(
        private LocalOllamaInference $inference,
        private NuExtractVacancyResponseMapper $mapper,
    ) {
    }

    public static function supports(string $model): bool
    {
        return str_starts_with(trim($model), 'hf.co/numind/NuExtract-');
    }

    public function extract(VacancyExtractionInput $input): \CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult
    {
        return $this->mapper->map($this->inference->invokeText(sprintf(
            "# Template:\n%s\n\n%s",
            json_encode($this->template(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            $input->vacancyText,
        )), $input->vacancyText);
    }

    /** @return array<string, mixed> */
    private function template(): array
    {
        $template = [];
        foreach (VacancyFieldProposal::ALLOWED_FIELDS as $field) {
            $template[$field] = match ($field) {
                'contractType' => array_map(static fn (ContractType $type): string => $type->value, ContractType::cases()),
                'workMode' => array_map(static fn (WorkMode $mode): string => $mode->value, WorkMode::cases()),
                'currencyCode' => array_map(static fn ($currency): string => $currency->code, CurrentIsoCurrencies::all()),
                'datePosted', 'deadline' => 'date-time',
                'minimumSalary', 'maximumSalary' => 'number',
                default => 'verbatim-string',
            };
        }
        $template['techStacks'] = ['verbatim-string'];

        return $template;
    }
}
