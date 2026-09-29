<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class VacancyDetailTest extends WebTestCase
{
    public function testOwnerCanViewAndAutosaveScratchpadButAnotherUserCannot(): void
    {
        $client = self::createClient();
        $owner = $this->createUser('detail-owner@example.com');
        $company = new Company('Acme BV', 'https://acme.example', 'Software');
        $vacancy = new Vacancy($owner, 'Senior PHP Developer');
        $vacancy->updateAuthoringDetails($company, null, null, 'Build APIs.', null, null, 'Build products.', null, null, ['https://jobs.example/vacancy'], 'Apply online.', 'Rotterdam', null, null, null, null, null, null, null);
        $vacancy->updateScratchpadNotes('# Initial preparation');
        $this->entityManager()->persist($company);
        $this->entityManager()->persist($vacancy);
        $this->entityManager()->flush();
        $client->loginUser($owner);

        $crawler = $client->request('GET', sprintf('/vacancies/%d', $vacancy->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.vacancy-overview', 'Acme BV');
        self::assertSelectorTextContains('body', 'Build APIs.');
        self::assertSelectorExists('[data-controller="vacancy-scratchpad"]');
        self::assertSelectorCount(6, '.markdown-toolbar [data-action="vacancy-scratchpad#format"]');
        self::assertSelectorExists('.markdown-toolbar [data-markdown-format="bold"]');
        self::assertSelectorExists('.markdown-toolbar [data-markdown-format="link"]');
        self::assertSelectorTextContains('.markdown-preview', 'Initial preparation');
        $token = $crawler->filter('[data-vacancy-scratchpad-target="token"]')->attr('value');
        self::assertIsString($token);

        $client->request('POST', sprintf('/vacancies/%d/scratchpad', $vacancy->getId()), ['_token' => $token, 'notes' => "# Interview\n\nAsk about **ownership**. <script>alert(1)</script>"]);
        self::assertResponseIsSuccessful();
        $response = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($response);
        $preview = $response['preview'] ?? null;
        self::assertIsString($preview);
        self::assertStringContainsString('<strong>ownership</strong>', $preview);
        self::assertStringNotContainsString('<script>', $preview);
        $this->entityManager()->clear();
        $saved = $this->entityManager()->find(Vacancy::class, $vacancy->getId());
        self::assertInstanceOf(Vacancy::class, $saved);
        self::assertStringContainsString('Ask about', (string) $saved->getScratchpadNotes());

        $client->request('POST', sprintf('/vacancies/%d/scratchpad', $vacancy->getId()), ['_token' => 'invalid', 'notes' => 'Denied']);
        self::assertResponseStatusCodeSame(403);

        $client->loginUser($this->createUser('detail-other@example.com'));
        $client->request('GET', sprintf('/vacancies/%d', $vacancy->getId()));
        self::assertResponseStatusCodeSame(404);
    }

    public function testOwnerCanSetAndClearNextAction(): void
    {
        $client = self::createClient();
        $user = $this->createUser('reminder-owner@example.com');
        $vacancy = new Vacancy($user, 'Reminder vacancy');
        $this->entityManager()->persist($vacancy);
        $this->entityManager()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', sprintf('/vacancies/%d', $vacancy->getId()));
        $token = $crawler->filter('form[action$="/next-action"] input[name="_token"]')->attr('value');
        self::assertIsString($token);
        $client->request('POST', sprintf('/vacancies/%d/next-action', $vacancy->getId()), ['_token' => $token, 'title' => 'Prepare screening', 'at' => '2026-10-01T09:30']);
        self::assertResponseRedirects(sprintf('/vacancies/%d', $vacancy->getId()));
        $this->entityManager()->clear();
        $updated = $this->entityManager()->find(Vacancy::class, $vacancy->getId());
        self::assertInstanceOf(Vacancy::class, $updated);
        self::assertSame('Prepare screening', $updated->getNextActionTitle());
        self::assertSame('2026-10-01 09:30', $updated->getNextActionAt()?->format('Y-m-d H:i'));

        $crawler = $client->request('GET', sprintf('/vacancies/%d', $vacancy->getId()));
        $token = $crawler->filter('form[action$="/next-action"] input[name="_token"]')->attr('value');
        self::assertIsString($token);
        $client->request('POST', sprintf('/vacancies/%d/next-action', $vacancy->getId()), ['_token' => $token, 'clear' => '1']);
        $this->entityManager()->clear();
        $cleared = $this->entityManager()->find(Vacancy::class, $vacancy->getId());
        self::assertInstanceOf(Vacancy::class, $cleared);
        self::assertNull($cleared->getNextActionAt());
        self::assertNull($cleared->getNextActionTitle());
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
