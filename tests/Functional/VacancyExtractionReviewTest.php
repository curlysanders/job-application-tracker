<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisCipher;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyAuthoringDraftRepository;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyAuthoringDraftService;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyDraftData;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisResult;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyAuthoringDraft;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Infrastructure\Security\SecurityUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class VacancyExtractionReviewTest extends WebTestCase
{
    public function testAuthoringDraftsAreOwnerScopedConsumedAndPurgedWhenExpired(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $service = self::getContainer()->get(VacancyAuthoringDraftService::class);
        $repository = self::getContainer()->get(VacancyAuthoringDraftRepository::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        self::assertInstanceOf(VacancyAuthoringDraftService::class, $service);
        self::assertInstanceOf(VacancyAuthoringDraftRepository::class, $repository);
        $owner = new User()->setEmail('draft-owner@example.com')->setPassword('hash');
        $other = new User()->setEmail('draft-other@example.com')->setPassword('hash');
        $em->persist($owner);
        $em->persist($other);
        $em->flush();
        $data = new VacancyDraftData();
        $data->title = 'Engineer';
        $draft = $service->create($owner, $data);
        $id = $draft->id->toRfc4122();

        self::assertNull($service->dataFor('invalid-id', $owner));
        self::assertNull($service->dataFor($id, $other));
        $service->consume($id, $other);
        self::assertSame('Engineer', $service->dataFor($id, $owner)?->title);
        $service->consume($id, $owner);
        self::assertNull($service->dataFor($id, $owner));
        $expired = new VacancyAuthoringDraft($owner, 'ciphertext', new \DateTimeImmutable('-1 day'));
        $repository->save($expired);
        self::assertNull($service->dataFor($expired->id->toRfc4122(), $owner));
        self::assertGreaterThanOrEqual(1, $repository->purgeExpired(new \DateTimeImmutable()));
    }

    public function testAuthenticatedUserCanSubmitVacancyTextForExtraction(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $owner = new User()->setEmail('extraction-submit@example.com')->setPassword('hash');
        $em->persist($owner);
        $em->flush();
        $client->loginUser(new SecurityUser($owner));
        $client->request('GET', '/vacancies/extract');
        self::assertResponseIsSuccessful();
        $client->submitForm('Extract details', ['vacancy_extraction_submission[vacancyText]' => 'Acme seeks a PHP developer in Rotterdam.'], serverParameters: ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testOwnerCanReviewEvidenceAndContinueWithAnEncryptedDraft(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $cipher = self::getContainer()->get(AnalysisCipher::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        self::assertInstanceOf(AnalysisCipher::class, $cipher);
        $owner = new User()->setEmail('extraction-owner@example.com')->setPassword('hash');
        $other = new User()->setEmail('extraction-other@example.com')->setPassword('hash');
        $em->persist($owner);
        $em->persist($other);
        $text = 'Acme seeks a PHP developer in Rotterdam.';
        $input = json_encode(['vacancyText' => $text], JSON_THROW_ON_ERROR);
        $request = new AnalysisRequest($owner, AnalysisKind::VacancyExtraction, $cipher->encrypt($input), $cipher->fingerprint($input), 'fake', 'fixture', new \DateTimeImmutable('+1 day'));
        $output = json_encode(['proposals' => [['field' => 'title', 'value' => 'PHP developer', 'excerpt' => 'PHP developer', 'confidence' => 0.95]], 'techStacks' => []], JSON_THROW_ON_ERROR);
        $request->complete(new AnalysisResult($request, $cipher->encrypt($output), $cipher->fingerprint($output)));
        $em->persist($request);
        $em->flush();
        $id = $request->id->toRfc4122();

        $client->loginUser(new SecurityUser($other));
        $client->request('GET', '/vacancy-extractions/'.$id.'/review');
        self::assertResponseStatusCodeSame(404);
        $client->loginUser(new SecurityUser($owner));
        $client->request('GET', '/vacancy-extractions/'.$id);
        self::assertResponseIsSuccessful();
        $client->request('POST', '/vacancy-extractions/'.$id.'/review', ['_token' => 'invalid'], server: ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseStatusCodeSame(403);
        $crawler = $client->request('GET', '/vacancy-extractions/'.$id.'/review');
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('input[name="field[title]"][checked]');
        $token = $crawler->filter('input[name="_token"]')->attr('value');
        $proposal = $crawler->filter('input[name="field[title]"]')->attr('value');
        self::assertIsString($token);
        self::assertIsString($proposal);
        $client->request('POST', '/vacancy-extractions/'.$id.'/review', ['_token' => $token, 'field' => ['title' => $proposal]], server: ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="vacancy[title]"][value="PHP developer"]');
        self::assertSelectorTextContains('textarea[name="vacancy[fullText]"]', $text);
    }
}
