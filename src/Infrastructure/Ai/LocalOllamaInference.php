<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;

final readonly class LocalOllamaInference
{
    public function __construct(
        #[Autowire(service: 'ai.platform.ollama')]
        private PlatformInterface $platform,
        private LockFactory $locks,

        #[Autowire('%env(string:AI_LOCAL_MODEL)%')]
        private string $model,
    ) {
    }

    /** @template T of object
     * @param class-string<T> $responseFormat
     *
     * @return T
     */
    public function invoke(
        string $systemPrompt,
        string $input,
        string $responseFormat,
    ): object {
        $lock = $this->locks->createLock('ai.local-inference', 180.0);
        if (!$lock->acquire(true)) {
            throw new \RuntimeException('The local AI inference lock could not be acquired.');
        }

        $model = trim($this->model);
        if ('' === $model) {
            throw new \LogicException('The local AI model must be configured.');
        }
        /* @var non-empty-string $model */

        try {
            $result = $this->platform->invoke($model, new MessageBag(
                Message::forSystem($systemPrompt),
                Message::ofUser($input),
            ), [
                'response_format' => $responseFormat,
                'options' => ['num_ctx' => 2048],
                'think' => false,
            ])->asObject();
        } finally {
            $lock->release();
        }

        if (!$result instanceof $responseFormat) {
            throw new \RuntimeException('The local AI provider returned an unexpected structured response.');
        }

        return $result;
    }
}
