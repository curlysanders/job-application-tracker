<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Workflow;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Exception\VacancyStatusTransitionNotAllowed;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Infrastructure\Workflow\SymfonyVacancyStatusWorkflow;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;
use Symfony\Component\Workflow\TransitionBlockerList;
use Symfony\Component\Workflow\WorkflowInterface;

final class SymfonyVacancyStatusWorkflowTest extends TestCase
{
    public function testAppliesTheTransitionWithItsNote(): void
    {
        $vacancy = new Vacancy(new User(), 'Backend engineer');
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects(self::once())->method('apply')->with($vacancy, 'start_applying', ['note' => 'Ready']);

        new SymfonyVacancyStatusWorkflow($workflow)->apply($vacancy, 'start_applying', 'Ready');
    }

    public function testTranslatesAWorkflowTransitionFailure(): void
    {
        $vacancy = new Vacancy(new User(), 'Backend engineer');
        $workflow = self::createStub(WorkflowInterface::class);
        $workflow->method('getName')->willReturn('vacancy_status');
        $workflow->method('apply')->willThrowException(new NotEnabledTransitionException($vacancy, 'accept', $workflow, new TransitionBlockerList()));

        $this->expectException(VacancyStatusTransitionNotAllowed::class);
        new SymfonyVacancyStatusWorkflow($workflow)->apply($vacancy, 'accept');
    }
}
