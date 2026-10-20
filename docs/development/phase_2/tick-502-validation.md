# TICK-502 validation

## Encrypted analysis storage

Set `AI_ANALYSIS_ENCRYPTION_KEY` to a distinct, randomly generated 32-byte key encoded with base64. It is required in every environment that creates or processes analysis requests and must be supplied through the deployment secret store. It encrypts request inputs and provider results with authenticated encryption, while keyed SHA-256 fingerprints identify the current request version without exposing its contents.

The durable outbox event carries only the request aggregate ID and input fingerprint. It never contains vacancy text, resume text, address data, result content, or credentials. Provider failures are stored as the generic `provider_failed` state and logged with the request ID and fingerprint only.

## Expiry sweep

Run the following command at least daily from the private deployment scheduler:

```bash
docker compose exec -T app php bin/console app:ai:purge-expired-analysis --env=prod --no-debug
```

It deletes request/result pairs whose 24-hour expiry has passed. The command is idempotent and safe to run more often.

## Automated checks

```bash
docker compose exec -T app php vendor/bin/paratest tests/Domain/Ai tests/Application/Ai tests/Infrastructure/Ai tests/Infrastructure/Messaging tests/Infrastructure/Persistence
docker compose exec -T app php bin/console lint:container --env=test --no-debug
docker compose exec -T app composer ci:doctrine-schema
docker compose exec -T app composer tests
docker compose exec -T app composer analyse
docker compose exec -T app composer ci:php-cs-fixer
```
