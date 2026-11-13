<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldProposal;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\CurrentIsoCurrencies;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;
use Symfony\AI\Platform\StructuredOutput\ResponseFormatFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator('ai.platform.response_format_factory')]
final readonly class VacancyExtractionResponseFormatFactory implements ResponseFormatFactoryInterface
{
    public function __construct(
        #[AutowireDecorated]
        private ResponseFormatFactoryInterface $inner,
    ) {
    }

    public function create(string $responseClass): array
    {
        if (VacancyExtractionResult::class !== $responseClass) {
            return $this->inner->create($responseClass);
        }

        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'VacancyExtractionResult',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'proposals' => [
                            'type' => 'array',
                            'maxItems' => VacancyExtractionResult::MAX_PROPOSALS,
                            'uniqueItems' => true,
                            'items' => ['oneOf' => array_map(
                                fn (string $field): array => $this->proposal($field, $this->valueSchema($field)),
                                VacancyFieldProposal::ALLOWED_FIELDS,
                            )],
                        ],
                        'techStacks' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 120],
                                    'excerpt' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 1000],
                                    'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                                ],
                                'required' => ['name', 'excerpt', 'confidence'],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                    'required' => ['proposals', 'techStacks'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ],
        ];
    }

    /** @param array<string, mixed> $valueSchema
     * @return array<string, mixed>
     */
    private function proposal(
        string $field,
        array $valueSchema,
    ): array {
        return [
            'type' => 'object',
            'properties' => [
                'field' => ['const' => $field],
                'value' => $valueSchema,
                'excerpt' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 1000],
                'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
            ],
            'required' => ['field', 'value', 'excerpt', 'confidence'],
            'additionalProperties' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function valueSchema(string $field): array
    {
        return match ($field) {
            'contractType' => ['type' => 'string', 'enum' => array_map(static fn (ContractType $type): string => $type->value, ContractType::cases())],
            'workMode' => ['type' => 'string', 'enum' => array_map(static fn (WorkMode $mode): string => $mode->value, WorkMode::cases())],
            'currencyCode' => ['type' => 'string', 'enum' => array_map(static fn ($currency): string => $currency->code, CurrentIsoCurrencies::all())],
            'datePosted', 'deadline' => ['type' => 'string', 'pattern' => '^\\d{4}-\\d{2}-\\d{2}$'],
            'minimumSalary', 'maximumSalary' => ['type' => 'string', 'pattern' => '^(?!0+(?:\\.0+)?$)\\d+(?:\\.\\d{1,2})?$'],
            'title', 'companyName', 'recruiterAgencyName', 'location' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
            default => ['type' => 'string', 'minLength' => 1, 'maxLength' => 10000],
        };
    }
}
