<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Dashboard;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Overview\SalaryFit;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Overview\VacancyOverviewFilter;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class VacancyOverviewRequest
{
    /** @param array<string, string> $query */
    private function __construct(public VacancyOverviewFilter $filter, public array $query)
    {
    }

    public static function fromRequest(Request $request): self
    {
        $query = self::optionalString($request, 'q');
        $status = self::enum($request, 'status', VacancyStatus::class);
        $excitementValue = self::optionalString($request, 'excitement');
        if (null !== $excitementValue && (!ctype_digit($excitementValue) || 5 < (int) $excitementValue)) {
            throw new NotFoundHttpException('Excitement rating not found.');
        }
        $workMode = self::enum($request, 'work_mode', WorkMode::class);
        $salaryFit = self::enum($request, 'salary_fit', SalaryFit::class);
        $archivedValue = self::optionalString($request, 'archived');
        if (null !== $archivedValue && '1' !== $archivedValue) {
            throw new NotFoundHttpException('Archive filter not found.');
        }
        $pageValue = self::optionalString($request, 'page');
        if (null !== $pageValue && (!ctype_digit($pageValue) || 1 > (int) $pageValue)) {
            throw new NotFoundHttpException('Page not found.');
        }

        $filter = new VacancyOverviewFilter(
            $query,
            $status,
            null === $excitementValue ? null : (int) $excitementValue,
            $workMode,
            $salaryFit,
            '1' === $archivedValue,
            null === $pageValue ? 1 : (int) $pageValue,
        );
        $parameters = array_filter([
            'q' => $query,
            'status' => $status?->value,
            'excitement' => $excitementValue,
            'work_mode' => $workMode?->value,
            'salary_fit' => $salaryFit?->value,
            'archived' => $archivedValue,
        ], static fn (?string $value): bool => null !== $value);

        return new self($filter, $parameters);
    }

    private static function optionalString(Request $request, string $name): ?string
    {
        $value = trim($request->query->getString($name));

        return '' === $value ? null : $value;
    }

    /** @template T of \BackedEnum
     * @param class-string<T> $enum
     *
     * @return ?T
     */
    private static function enum(Request $request, string $name, string $enum): ?\BackedEnum
    {
        $value = self::optionalString($request, $name);
        if (null === $value) {
            return null;
        }
        $case = $enum::tryFrom($value);
        if (null === $case) {
            throw new NotFoundHttpException(sprintf('%s filter not found.', ucfirst(str_replace('_', ' ', $name))));
        }

        return $case;
    }
}
