<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;
use Symfony\Component\Workflow\WorkflowInterface;

final class VacancyStatusWorkflowTest extends WebTestCase
{
    public function testTheConfiguredWorkflowExposesOnlyTheExpectedTransitions(): void
    {
        $workflow = self::getContainer()->get('state_machine.vacancy_status');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);

        foreach ($this->expectedTransitions() as $status => $expectedTransitions) {
            $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
            $vacancy->setStatus(VacancyStatus::from($status));

            self::assertSame($expectedTransitions, array_map(static fn ($transition): string => $transition->getName(), $workflow->getEnabledTransitions($vacancy)));
        }
    }

    public function testTheWorkflowBlocksIllegalJumpsAndLeavesTerminalStatusesFinal(): void
    {
        $workflow = self::getContainer()->get('state_machine.vacancy_status');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');

        $this->expectException(NotEnabledTransitionException::class);
        $workflow->apply($vacancy, 'accept');
    }

    public function testEditPageRendersAvailableButtonsAndTransitionsAnOwnedVacancy(): void
    {
        $client = self::createClient();
        $user = $this->createUser('workflow@example.com');
        $vacancy = new Vacancy($user, 'Senior PHP Developer');
        $this->entityManager()->persist($vacancy);
        $this->entityManager()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', sprintf('/app/vacancies/%d/edit', $vacancy->getId()));
        self::assertSelectorTextContains('.vacancy-status-panel', 'Current status: Bookmarked');
        self::assertSelectorExists('input[name="transition"][value="start_applying"]');
        self::assertSelectorNotExists('input[name="transition"][value="accept"]');
        $client->request('POST', sprintf('/app/vacancies/%d/status', $vacancy->getId()), [
            '_token' => $this->statusCsrfToken($crawler, 'start_applying'),
            'transition' => 'start_applying',
        ]);
        self::assertResponseRedirects(sprintf('/app/vacancies/%d/edit', $vacancy->getId()));

        $this->entityManager()->clear();
        $updated = $this->entityManager()->find(Vacancy::class, $vacancy->getId());
        self::assertInstanceOf(Vacancy::class, $updated);
        self::assertSame(VacancyStatus::Applying, $updated->getStatus());
    }

    public function testStatusTransitionRejectsInvalidCsrfAndAnotherUsersVacancy(): void
    {
        $client = self::createClient();
        $user = $this->createUser('workflow-owner@example.com');
        $otherUser = $this->createUser('workflow-other@example.com');
        $ownedVacancy = new Vacancy($user, 'Owned vacancy');
        $vacancy = new Vacancy($otherUser, 'Private vacancy');
        $this->entityManager()->persist($ownedVacancy);
        $this->entityManager()->persist($vacancy);
        $this->entityManager()->flush();
        $client->loginUser($user);

        $client->request('POST', sprintf('/app/vacancies/%d/status', $ownedVacancy->getId()), ['_token' => 'invalid', 'transition' => 'start_applying']);
        self::assertResponseStatusCodeSame(403);
        $client->request('POST', sprintf('/app/vacancies/%d/status', $vacancy->getId()), ['_token' => 'invalid', 'transition' => 'start_applying']);
        self::assertResponseStatusCodeSame(404);
    }

    /** @return array<string, list<string>> */
    private function expectedTransitions(): array
    {
        return [
            VacancyStatus::Bookmarked->value => ['start_applying', 'withdraw_from_bookmarked'],
            VacancyStatus::Applying->value => ['undo_start_applying', 'mark_applied', 'withdraw_from_applying'],
            VacancyStatus::Applied->value => ['undo_mark_applied', 'start_interviewing', 'withdraw_from_applied', 'mark_not_selected_from_applied', 'mark_no_response_from_applied'],
            VacancyStatus::Interviewing->value => ['undo_start_interviewing', 'start_negotiating', 'withdraw_from_interviewing', 'mark_not_selected_from_interviewing', 'mark_no_response_from_interviewing'],
            VacancyStatus::Negotiating->value => ['undo_start_negotiating', 'accept', 'withdraw_from_negotiating', 'mark_not_selected_from_negotiating', 'mark_no_response_from_negotiating'],
            VacancyStatus::Accepted->value => [],
            VacancyStatus::IWithdrew->value => [],
            VacancyStatus::NotSelected->value => [],
            VacancyStatus::NoResponse->value => [],
        ];
    }

    private function statusCsrfToken(Crawler $crawler, string $transition): string
    {
        $transitionInput = $crawler->filter(sprintf('input[name="transition"][value="%s"]', $transition))->getNode(0);
        self::assertInstanceOf(\DOMElement::class, $transitionInput);
        self::assertInstanceOf(\DOMElement::class, $transitionInput->parentNode);
        $token = new Crawler($transitionInput->parentNode)->filter('input[name="_token"]')->attr('value');
        self::assertIsString($token);

        return $token;
    }

    private function createUser(string $email): User
    {
        $user = new User()->setEmail($email);
        $user->setPassword($this->passwordHasher()->hashPassword($user, 'SecurePassword1!'));
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }

    private function passwordHasher(): UserPasswordHasherInterface
    {
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);

        return $hasher;
    }
}
