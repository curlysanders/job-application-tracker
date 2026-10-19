<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SecurityTest extends WebTestCase
{
    public function testAnonymousUserSeesThePublicHomePage(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Your job search, in one place');
        self::assertSelectorExists('a[href="/login"]');
        self::assertSelectorExists('a[href="/register"]');
    }

    public function testApplicationResponsesPreventSearchIndexing(): void
    {
        $client = self::createClient();

        foreach (['/', '/login', '/register', '/healthz', '/missing'] as $path) {
            $client->request('GET', $path);

            self::assertResponseHasHeader('X-Robots-Tag');
            self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        }
    }

    public function testAnonymousPublicNavigationIsRateLimitedPerRouteAndIp(): void
    {
        $client = self::createClient();

        for ($attempt = 1; $attempt <= 10; ++$attempt) {
            $client->request('GET', '/', server: ['REMOTE_ADDR' => '198.51.100.10']);
            self::assertResponseIsSuccessful();
        }

        $client->request('GET', '/', server: ['REMOTE_ADDR' => '198.51.100.10']);

        self::assertResponseStatusCodeSame(429);
        self::assertResponseHasHeader('Retry-After');
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
    }

    public function testFormerApplicationUrlsAreAbsent(): void
    {
        $client = self::createClient();

        $client->request('GET', '/app');
        self::assertResponseStatusCodeSame(404);
        $client->request('GET', '/app/vacancies/new');
        self::assertResponseStatusCodeSame(404);
    }

    public function testUserCanRegisterAndIsAuthenticated(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Create account')->form([
            'registration[email]' => '  Sander@example.com ',
            'registration[plainPassword][first]' => 'SecurePassword1!',
            'registration[plainPassword][second]' => 'SecurePassword1!',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Dashboard');

        $user = $this->entityManager()->getRepository(User::class)->findOneBy([
            'email' => 'sander@example.com',
        ]);
        self::assertInstanceOf(User::class, $user);
        self::assertSame('sander@example.com', $user->email);
        self::assertNotSame('SecurePassword1!', $user->getPassword());
        self::assertTrue($this->passwordHasher()->isPasswordValid(new \CurlySanders\JobApplicationTracker\Infrastructure\Security\SecurityUser($user), 'SecurePassword1!'));
    }

    public function testRegistrationRejectsPasswordThatDoesNotMeetPolicy(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Create account')->form([
            'registration[email]' => 'sander@example.com',
            'registration[plainPassword][first]' => 'onlylowercase',
            'registration[plainPassword][second]' => 'onlylowercase',
        ]);

        $client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-panel', 'Your password must contain an uppercase letter.');
        self::assertSelectorTextContains('.form-panel', 'Your password must contain a base-10 digit.');
        self::assertSelectorTextContains('.form-panel', 'Your password must contain a symbol.');
    }

    public function testRegistrationRejectsDuplicateEmailAddresses(): void
    {
        $client = self::createClient();
        $this->createUser('sander@example.com');
        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Create account')->form([
            'registration[email]' => 'SANDER@example.com',
            'registration[plainPassword][first]' => 'SecurePassword1!',
            'registration[plainPassword][second]' => 'SecurePassword1!',
        ]);

        $client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-panel', 'We could not create an account with those details.');
        self::assertSelectorTextNotContains('.form-panel', 'already exists');
    }

    public function testRegistrationIsLimitedPerClientIp(): void
    {
        $client = self::createClient();

        for ($attempt = 1; $attempt <= 5; ++$attempt) {
            $crawler = $client->request('GET', '/register');
            $client->submit($crawler->selectButton('Create account')->form([
                'registration[email]' => 'limited@example.com',
                'registration[plainPassword][first]' => 'invalid',
                'registration[plainPassword][second]' => 'invalid',
            ]));
            self::assertResponseStatusCodeSame(422);
        }

        $crawler = $client->request('GET', '/register');
        $client->submit($crawler->selectButton('Create account')->form([
            'registration[email]' => 'limited@example.com',
            'registration[plainPassword][first]' => 'invalid',
            'registration[plainPassword][second]' => 'invalid',
        ]));

        self::assertResponseStatusCodeSame(429);
        self::assertResponseHasHeader('Retry-After');
    }

    public function testUserCanLogInAndOut(): void
    {
        $client = self::createClient();
        $this->createUser('sander@example.com');
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('Sign in')->form([
            '_username' => 'sander@example.com',
            '_password' => 'SecurePassword1!',
        ]));

        self::assertResponseRedirects('/');
        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Dashboard');
        self::assertSelectorExists('button[data-controller="mobile-menu"][aria-controls="app-sidebar"][data-collapse-toggle="app-sidebar"]');
        self::assertSelectorExists('#app-sidebar .mobile-sidebar-sign-out');

        $client->submit($crawler->filter('#app-sidebar .mobile-sidebar-sign-out button')->form());
        self::assertResponseRedirects('/');
    }

    public function testAuthenticatedUserIsRedirectedAwayFromLogin(): void
    {
        $client = self::createClient();
        $client->loginUser(new \CurlySanders\JobApplicationTracker\Infrastructure\Security\SecurityUser($this->createUser('already-authenticated@example.com')));

        $client->request('GET', '/login');

        self::assertResponseRedirects('/');
    }

    private function createUser(string $email): User
    {
        $user = new User()->setEmail($email);
        $passwordHasher = $this->passwordHasher();
        $user->setPassword($passwordHasher->hashPassword(new \CurlySanders\JobApplicationTracker\Infrastructure\Security\SecurityUser($user), 'SecurePassword1!'));

        $entityManager = $this->entityManager();
        $entityManager->persist($user);
        $entityManager->flush();

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
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $passwordHasher);

        return $passwordHasher;
    }
}
