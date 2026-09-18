<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Domain\User\PreferredTransportMode;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProfileSettingsTest extends WebTestCase
{
    public function testAnonymousUsersAreRedirectedToLogin(): void
    {
        $client = self::createClient();
        $client->request('GET', '/app/profile');

        self::assertResponseRedirects('/login');
    }

    public function testUserCanUpdateTheirProfileSettings(): void
    {
        $client = self::createClient();
        $user = $this->createUser('sander@example.com');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/app/profile');
        $client->submit($crawler->selectButton('Save settings')->form([
            'profile_settings[minimumPreferredSalary]' => '4500.00',
            'profile_settings[maximumCommuteMinutes]' => 45,
            'profile_settings[preferredTransportMode]' => PreferredTransportMode::PublicTransport->value,
        ]));

        self::assertResponseRedirects('/app/profile');
        $this->entityManager()->clear();
        $savedUser = $this->entityManager()->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $savedUser);
        self::assertSame('4500.00', $savedUser->getMinimumPreferredSalary()?->toDecimal());
        self::assertSame(45, $savedUser->getMaximumCommuteMinutes());
        self::assertSame(PreferredTransportMode::PublicTransport, $savedUser->getPreferredTransportMode());
    }

    public function testProfileRejectsNonPositivePreferences(): void
    {
        $client = self::createClient();
        $client->loginUser($this->createUser('sander@example.com'));
        $crawler = $client->request('GET', '/app/profile');
        $client->submit($crawler->selectButton('Save settings')->form([
            'profile_settings[minimumPreferredSalary]' => '0.00',
            'profile_settings[maximumCommuteMinutes]' => 0,
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-panel', 'Enter a positive amount with at most two decimal places.');
        self::assertSelectorTextContains('.form-panel', 'The commute time must be positive.');
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
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $passwordHasher);

        return $passwordHasher;
    }
}
