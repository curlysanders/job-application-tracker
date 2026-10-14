# TICK-501 validation

## Provider boundary

`VacancyExtractionProvider` and `CandidateFitProvider` are the only AI-facing
application ports. Their inputs and validated result DTOs contain no Symfony AI,
Ollama, HTTP, or model details. The default `AI_PROVIDER=fake` resolves both ports
to deterministic in-process fakes; CI therefore has no route to a real provider.

Set `AI_PROVIDER=local` and `AI_LOCAL_PROVIDER_ENABLED=1` only after the private
runtime admission benchmark has passed. The local adapter serializes both port
operations through one `ai.local-inference` lock and invokes `qwen3:0.6b` with
`num_ctx=2048`.

## Corpus gate

The public `resources/ai/vacancy-corpus.json` contains 24 sanitized, labelled
vacancies: 12 Dutch and 12 English. Every response must validate against the
structured-output DTOs and the corpus evaluator must reach at least 90% F1 across
the labelled critical fields. Run it against the explicitly enabled local provider:

```bash
AI_PROVIDER=local AI_LOCAL_PROVIDER_ENABLED=1 docker compose exec -T app \
  php bin/console app:ai:benchmark-vacancy-corpus --env=prod --no-debug
```

The command reports case count, valid-response count, precision, recall, F1, and
the final pass state. The private deployment checkout owns NAS resource capture,
swap/OOM checks, worker and health checks, latency recording, and retained evidence.

## Automated checks

```bash
docker compose exec -T app php vendor/bin/paratest tests/Application/Ai tests/Infrastructure/Ai
docker compose exec -T app php bin/console lint:container --env=test --no-debug
docker compose exec -T app composer ci:doctrine-schema
docker compose exec -T app composer tests
docker compose exec -T app composer analyse
docker compose exec -T app composer ci:php-cs-fixer
```
