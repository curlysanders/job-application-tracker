<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SecurityTest extends WebTestCase
{
    public function testAnonymousUserIsRedirectedToLoginForDashboard(): void
    {
        $client = self::createClient();
        $client->request('GET', '/app');

        self::assertResponseRedirects('/login');
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

        self::assertResponseRedirects('/app');
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Dashboard');

        $user = $this->entityManager()->getRepository(User::class)->findOneBy([
            'email' => 'sander@example.com',
        ]);
        self::assertInstanceOf(User::class, $user);
        self::assertSame('sander@example.com', $user->getEmail());
        self::assertNotSame('SecurePassword1!', $user->getPassword());
        self::assertTrue($this->passwordHasher()->isPasswordValid($user, 'SecurePassword1!'));
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
        self::assertSelectorTextContains('.form-panel', 'An account already exists for this email address.');
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

        self::assertResponseRedirects('/app');
        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Dashboard');

        $client->submit($crawler->selectButton('Sign out')->form());
        self::assertResponseRedirects('/');
    }

    private function createUser(string $email): User
    {
        $user = new User()->setEmail($email);
        $passwordHasher = $this->passwordHasher();
        $user->setPassword($passwordHasher->hashPassword($user, 'SecurePassword1!'));

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
