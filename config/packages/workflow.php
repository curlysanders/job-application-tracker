<?php

declare(strict_types=1);

use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'workflows' => [
            'vacancy_status' => [
                'type' => 'state_machine',
                'supports' => [Vacancy::class],
                'marking_store' => ['type' => 'method', 'property' => 'status'],
                'initial_marking' => VacancyStatus::Bookmarked->value,
                'places' => array_map(static fn (VacancyStatus $status): string => $status->value, VacancyStatus::cases()),
                'transitions' => [
                    'start_applying' => ['from' => VacancyStatus::Bookmarked->value, 'to' => VacancyStatus::Applying->value],
                    'undo_start_applying' => ['from' => VacancyStatus::Applying->value, 'to' => VacancyStatus::Bookmarked->value],
                    'mark_applied' => ['from' => VacancyStatus::Applying->value, 'to' => VacancyStatus::Applied->value],
                    'undo_mark_applied' => ['from' => VacancyStatus::Applied->value, 'to' => VacancyStatus::Applying->value],
                    'start_interviewing' => ['from' => VacancyStatus::Applied->value, 'to' => VacancyStatus::Interviewing->value],
                    'undo_start_interviewing' => ['from' => VacancyStatus::Interviewing->value, 'to' => VacancyStatus::Applied->value],
                    'start_negotiating' => ['from' => VacancyStatus::Interviewing->value, 'to' => VacancyStatus::Negotiating->value],
                    'undo_start_negotiating' => ['from' => VacancyStatus::Negotiating->value, 'to' => VacancyStatus::Interviewing->value],
                    'accept' => ['from' => VacancyStatus::Negotiating->value, 'to' => VacancyStatus::Accepted->value],
                    'withdraw_from_bookmarked' => ['from' => VacancyStatus::Bookmarked->value, 'to' => VacancyStatus::IWithdrew->value],
                    'withdraw_from_applying' => ['from' => VacancyStatus::Applying->value, 'to' => VacancyStatus::IWithdrew->value],
                    'withdraw_from_applied' => ['from' => VacancyStatus::Applied->value, 'to' => VacancyStatus::IWithdrew->value],
                    'withdraw_from_interviewing' => ['from' => VacancyStatus::Interviewing->value, 'to' => VacancyStatus::IWithdrew->value],
                    'withdraw_from_negotiating' => ['from' => VacancyStatus::Negotiating->value, 'to' => VacancyStatus::IWithdrew->value],
                    'mark_not_selected_from_applied' => ['from' => VacancyStatus::Applied->value, 'to' => VacancyStatus::NotSelected->value],
                    'mark_not_selected_from_interviewing' => ['from' => VacancyStatus::Interviewing->value, 'to' => VacancyStatus::NotSelected->value],
                    'mark_not_selected_from_negotiating' => ['from' => VacancyStatus::Negotiating->value, 'to' => VacancyStatus::NotSelected->value],
                    'mark_no_response_from_applied' => ['from' => VacancyStatus::Applied->value, 'to' => VacancyStatus::NoResponse->value],
                    'mark_no_response_from_interviewing' => ['from' => VacancyStatus::Interviewing->value, 'to' => VacancyStatus::NoResponse->value],
                    'mark_no_response_from_negotiating' => ['from' => VacancyStatus::Negotiating->value, 'to' => VacancyStatus::NoResponse->value],
                ],
            ],
        ],
    ]);
};
