<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TechStackAutocompleteTest extends WebTestCase
{
    public function testAnonymousUsersCannotAccessAutocompleteEndpoints(): void
    {
        $client = self::createClient();

        $client->request('GET', '/app/tech-stacks/autocomplete?query=php');
        self::assertResponseRedirects('/login');
        $client->request('GET', '/app/tech-stack-categories/autocomplete?query=back');
        self::assertResponseRedirects('/login');
    }

    public function testAutocompleteReturnsMatchingTagsAndCategories(): void
    {
        $client = self::createClient();
        $client->loginUser($this->createUser('tech-stack@example.com'));
        $this->entityManager()->persist(new TechStack('PHP', 'Backend'));
        $this->entityManager()->persist(new TechStack('PHPUnit', 'Testing'));
        $this->entityManager()->flush();

        $client->request('GET', '/app/tech-stacks/autocomplete?query=php');
        self::assertResponseIsSuccessful();
        $technologyContent = $client->getResponse()->getContent();
        self::assertIsString($technologyContent);
        self::assertJsonStringEqualsJsonString('{"results":[{"value":"php","text":"PHP (Backend)"},{"value":"phpunit","text":"PHPUnit (Testing)"}]}', $technologyContent);

        $client->request('GET', '/app/tech-stack-categories/autocomplete?query=back');
        self::assertResponseIsSuccessful();
        $categoryContent = $client->getResponse()->getContent();
        self::assertIsString($categoryContent);
        self::assertJsonStringEqualsJsonString('{"results":[{"value":"Backend","text":"Backend"}]}', $categoryContent);
    }

    public function testAutocompletePaginatesTechnologiesAndCategories(): void
    {
        $client = self::createClient();
        $client->loginUser($this->createUser('paginated-tech-stack@example.com'));

        for ($number = 1; $number <= 21; ++$number) {
            $this->entityManager()->persist(new TechStack(
                sprintf('Technology %02d', $number),
                sprintf('Category %02d', $number),
            ));
        }
        $this->entityManager()->flush();

        $client->request('GET', '/app/tech-stacks/autocomplete?query=technology');
        self::assertResponseIsSuccessful();
        $firstTechnologyPage = $this->responseData($client);
        self::assertCount(20, $firstTechnologyPage['results']);
        self::assertSame('technology-01', $firstTechnologyPage['results'][0]['value']);
        self::assertIsString($firstTechnologyPage['next_page'] ?? null);
        self::assertStringContainsString('page=2', $firstTechnologyPage['next_page']);

        $client->request('GET', '/app/tech-stacks/autocomplete?query=technology&page=2');
        self::assertResponseIsSuccessful();
        $secondTechnologyPage = $this->responseData($client);
        self::assertSame([['value' => 'technology-21', 'text' => 'Technology 21 (Category 21)']], $secondTechnologyPage['results']);
        self::assertArrayNotHasKey('next_page', $secondTechnologyPage);

        $client->request('GET', '/app/tech-stack-categories/autocomplete?query=category');
        self::assertResponseIsSuccessful();
        $firstCategoryPage = $this->responseData($client);
        self::assertCount(20, $firstCategoryPage['results']);
        self::assertSame('Category 01', $firstCategoryPage['results'][0]['value']);
        self::assertIsString($firstCategoryPage['next_page'] ?? null);
        self::assertStringContainsString('page=2', $firstCategoryPage['next_page']);

        $client->request('GET', '/app/tech-stack-categories/autocomplete?query=category&page=2');
        self::assertResponseIsSuccessful();
        $secondCategoryPage = $this->responseData($client);
        self::assertSame([['value' => 'Category 21', 'text' => 'Category 21']], $secondCategoryPage['results']);
        self::assertArrayNotHasKey('next_page', $secondCategoryPage);
    }

    /** @return array{results: list<array{value: string, text: string}>, next_page?: string} */
    private function responseData(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);

        /** @var array{results: list<array{value: string, text: string}>, next_page?: string} $data */
        $data = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);

        return $data;
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
