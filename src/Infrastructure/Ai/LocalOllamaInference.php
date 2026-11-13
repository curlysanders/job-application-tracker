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

    /** @return non-empty-string */
    public function model(): string
    {
        $model = trim($this->model);
        if ('' === $model) {
            throw new \LogicException('The local AI model must be configured.');
        }

        return $model;
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

        $model = $this->model();
        /* @var non-empty-string $model */

        try {
            $result = $this->platform->invoke($model, new MessageBag(
                Message::forSystem($systemPrompt),
                Message::ofUser($input),
            ), [
                'response_format' => $responseFormat,
            ] + $this->options($model))->asObject();
        } finally {
            $lock->release();
        }

        if (!$result instanceof $responseFormat) {
            throw new \RuntimeException('The local AI provider returned an unexpected structured response.');
        }

        return $result;
    }

    public function invokeText(string $input): string
    {
        $lock = $this->locks->createLock('ai.local-inference', 180.0);
        if (!$lock->acquire(true)) {
            throw new \RuntimeException('The local AI inference lock could not be acquired.');
        }

        $model = $this->model();
        /* @var non-empty-string $model */

        try {
            $result = $this->platform->invoke($model, new MessageBag(Message::ofUser($input)), $this->options($model));

            try {
                return $result->asText();
            } catch (\Throwable $exception) {
                $response = $result->getRawResult()->getData();
                if (isset($response['error']) && is_string($response['error'])) {
                    throw new \RuntimeException(sprintf('Ollama rejected the local inference request: %s', $response['error']), previous: $exception);
                }

                throw $exception;
            }
        } finally {
            $lock->release();
        }
    }

    /** @return array<string, int|bool> */
    private function options(string $model): array
    {
        $options = [
            // Symfony's Ollama bridge places provider-specific options in
            // Ollama's `options` object. Passing a nested `options` array
            // would produce `options.options.num_ctx`, which Ollama ignores.
            'num_ctx' => 2048,
            // Bound generation so an invalid or repetitive response cannot
            // consume the CPU budget intended for one vacancy extraction.
            'num_predict' => 512,
            // Stable decoding makes benchmark runs comparable and avoids
            // sampling variation in canonical enum choices.
            'temperature' => 0,
        ];
        if (str_starts_with($model, 'granite4.2:')) {
            // Granite 4.2 calls its template setting `enable_thinking`, but
            // Ollama exposes the supported runtime control as this top-level
            // API field. NuExtract rejects this option, so do not apply it to
            // models that do not implement the thinking control.
            $options['think'] = false;
        }

        return $options;
    }
}
