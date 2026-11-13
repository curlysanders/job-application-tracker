<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class Gliner2Inference
{
    private const array KINDS = ['title', 'company', 'recruiter', 'location', 'technology'];

    public function __construct(
        #[Autowire(service: 'app.ai.gliner_http_client')]
        private HttpClientInterface $client,
        private LockFactory $locks,

        #[Autowire('%env(string:GLINER_URL)%')]
        private string $url,
    ) {
    }

    /** @return list<Gliner2Entity> */
    public function extract(string $vacancyText): array
    {
        $lock = $this->locks->createLock('ai.gliner-inference', 360.0);
        if (!$lock->acquire(true)) {
            throw new \RuntimeException('The GLiNER inference lock could not be acquired.');
        }

        try {
            try {
                $response = $this->client->request('POST', rtrim($this->url, '/').'/v1/vacancy-extractions', [
                    'json' => ['text' => $vacancyText],
                    'headers' => ['Accept' => 'application/json'],
                ]);
                if (200 !== $response->getStatusCode()) {
                    throw new \RuntimeException('The GLiNER sidecar returned an unavailable response.');
                }
                $payload = $response->toArray(false);
            } catch (ExceptionInterface $exception) {
                throw new \RuntimeException('The GLiNER sidecar could not be reached.', previous: $exception);
            }
        } finally {
            $lock->release();
        }

        if (!isset($payload['entities']) || !is_array($payload['entities']) || !array_is_list($payload['entities']) || count($payload['entities']) > 64) {
            throw new \RuntimeException('The GLiNER sidecar returned an invalid extraction response.');
        }

        $entities = [];
        foreach ($payload['entities'] as $entity) {
            if (!is_array($entity) || !is_string($entity['kind'] ?? null) || !in_array($entity['kind'], self::KINDS, true) || !is_string($entity['text'] ?? null) || !is_int($entity['start'] ?? null) || !is_int($entity['end'] ?? null) || (!is_float($entity['confidence'] ?? null) && !is_int($entity['confidence'] ?? null))) {
                throw new \RuntimeException('The GLiNER sidecar returned an invalid extraction entity.');
            }
            $confidence = (float) $entity['confidence'];
            if ($entity['start'] < 0 || $entity['end'] <= $entity['start'] || !is_finite($confidence) || $confidence < 0 || $confidence > 1 || $entity['text'] !== mb_substr($vacancyText, $entity['start'], $entity['end'] - $entity['start'])) {
                throw new \RuntimeException('The GLiNER sidecar returned an ungrounded extraction entity.');
            }
            $entities[] = new Gliner2Entity($entity['kind'], $entity['text'], $entity['start'], $entity['end'], $confidence);
        }

        return $entities;
    }
}
