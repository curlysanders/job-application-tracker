<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class AssertDoctrineSchemaCommandTest extends KernelTestCase
{
    public function testItAcceptsTheCurrentDoctrineSchema(): void
    {
        self::bootKernel();
        self::assertNotNull(self::$kernel);
        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find('app:doctrine:schema:assert-synchronized'));

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('Doctrine schema is synchronized', $tester->getDisplay());
    }
}
