<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\UI\Form;

use CurlySanders\JobApplicationTracker\UI\Form\Model\VacancyData;
use CurlySanders\JobApplicationTracker\UI\Form\VacancyFormDataFactory;
use PHPUnit\Framework\TestCase;

final class VacancyFormDataFactoryTest extends TestCase
{
    public function testSkipsAnEmptyNewTechnologyPlaceholder(): void
    {
        $data = new VacancyData();
        $data->title = 'Backend developer';
        $data->techStacks->newTags = [null];

        $command = new VacancyFormDataFactory()->command('01a0fbfe-5d28-7789-af51-45b5780aa1f3', null, $data);

        self::assertSame([], $command->newTechStacks);
        self::assertNull($command->companyId);
        self::assertNull($command->recruiterId);
    }
}
