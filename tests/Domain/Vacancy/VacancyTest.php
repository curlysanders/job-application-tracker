<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\SalaryRange;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

final class VacancyTest extends TestCase
{
    public function testCreatesABookmarkedVacancyWithSafeDefaults(): void
    {
        $user = new User();
        $vacancy = new Vacancy($user, '  Senior PHP Developer  ');

        self::assertSame($user, $vacancy->getUser());
        self::assertSame('Senior PHP Developer', $vacancy->getTitle());
        self::assertSame(VacancyStatus::Bookmarked, $vacancy->getStatus());
        self::assertFalse($vacancy->isArchived());
        self::assertNull($vacancy->getSalaryRange());
        self::assertNull($vacancy->getExcitement());
    }

    public function testReplacesTheSalaryRangeAndTechnologyTags(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $php = new TechStack('PHP', 'Backend');
        $symfony = new TechStack('Symfony', 'Backend');

        $vacancy->replaceSalaryRange(SalaryRange::fromDecimals('4500.00', '6000.00', 'EUR'));
        $vacancy->replaceTechStacks($php, $symfony);
        $vacancy->replaceTechStacks($symfony);

        self::assertSame('4500.00', (string) $vacancy->getSalaryRange()?->getMinimum()?->getAmount());
        self::assertSame([$symfony], $vacancy->getTechStacks()->toArray());
    }

    public function testRejectsBlankTitlesAndOutOfRangeExcitement(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Vacancy(new User(), ' ');
    }

    public function testRejectsExcitementOutsideTheAllowedRange(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');

        $this->expectException(\InvalidArgumentException::class);
        $vacancy->setExcitement(6);
    }

    public function testStateMachineStoresTransitionsThroughTheBackedStatusEnum(): void
    {
        $definition = new DefinitionBuilder()
            ->addPlaces([VacancyStatus::Bookmarked->value, VacancyStatus::Applying->value])
            ->addTransition(new Transition('start_applying', VacancyStatus::Bookmarked->value, VacancyStatus::Applying->value))
            ->build();
        $stateMachine = new StateMachine($definition, new MethodMarkingStore(true, 'status'));
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');

        $stateMachine->apply($vacancy, 'start_applying');

        self::assertSame(VacancyStatus::Applying, $vacancy->getStatus());
    }
}
