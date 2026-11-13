<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Console;

use CurlySanders\JobApplicationTracker\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class BenchmarkAiVacancyCorpusCommandTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testItCanLimitTheCorpusForAnEarlyBenchmarkCheck(): void
    {
        self::bootKernel();
        self::assertNotNull(self::$kernel);
        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find('app:ai:benchmark-vacancy-corpus'));

        $tester->execute(['--limit' => '2']);

        self::assertStringContainsString('"cases":2', $tester->getDisplay());
    }
}
