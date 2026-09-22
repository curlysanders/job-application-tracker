<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Persistence;

use CurlySanders\JobApplicationTracker\Application\Authentication\Exception\DuplicateUserEmail;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\DoctrineUserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineUserRepositoryTest extends KernelTestCase
{
    public function testTranslatesDatabaseUniqueConstraintViolations(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(DoctrineUserRepository::class);
        self::assertInstanceOf(DoctrineUserRepository::class, $repository);

        $firstUser = new User()->setEmail('sander@example.com')->setPassword('hashed-password');
        $repository->save($firstUser);

        $this->expectException(DuplicateUserEmail::class);
        $repository->save(new User()->setEmail('sander@example.com')->setPassword('hashed-password'));
    }
}
