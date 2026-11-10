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

## Local Ollama test environment

The optional `ai` Compose profile runs Ollama on the CPU for local development.
It exposes no host port: the app and worker reach it at `http://ollama:11434` on
the Compose network. It permits one loaded model and one request at a time, caps
the context at 2,048 tokens, limits the container to 1.4 GB RAM and 1.5 CPUs,
and unloads an idle model after 30 seconds. These are conservative settings for
a 4 GB NAS; they do not replace the NAS admission benchmark.

The official Ollama image currently also packages accelerator runtimes and is
large on disk. The profile exposes no GPU devices and runs CPU inference; plan
adequate NAS storage before enabling it there.

```bash
docker compose --profile ai up -d ollama
docker compose exec -T ollama ollama pull qwen3:0.6b
```

For real extraction, set the following in `.env.local`, then restart the app and
worker. Keep the checked-in default provider as `fake`.

```dotenv
AI_PROVIDER=local
AI_LOCAL_PROVIDER_ENABLED=1
OLLAMA_HOST_URL=http://ollama:11434
AI_LOCAL_MODEL=qwen3:0.6b
```

Check the service and execute the corpus gate:

```bash
docker compose exec -T ollama ollama ps
docker compose exec -T app php bin/console app:ai:benchmark-vacancy-corpus
```

The local end-to-end check is: sign in, open `/vacancies/extract`, paste a
vacancy, wait for review, select proposals, and submit the existing vacancy
form. Repeat the benchmark with `qwen3.5:0.8b` only as an explicit comparison;
retain the model that passes the gate without NAS memory or swap pressure.
If the command fails, restore `AI_PROVIDER=fake` and
`AI_LOCAL_PROVIDER_ENABLED=0` before using the application normally.

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
