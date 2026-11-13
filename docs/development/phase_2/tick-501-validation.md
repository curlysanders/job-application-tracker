# TICK-501 validation

## Provider boundary

`VacancyExtractionProvider` and `CandidateFitProvider` are the only AI-facing
application ports. Their inputs and validated result DTOs contain no Symfony AI,
Ollama, HTTP, or model details. The defaults `AI_PROVIDER=fake` and `AI_CANDIDATE_FIT_PROVIDER=fake` resolve the respective ports
to deterministic in-process fakes; CI therefore has no route to a real provider.

Set `AI_PROVIDER=local` for extraction or `AI_CANDIDATE_FIT_PROVIDER=local` for candidate fit, and set `AI_LOCAL_PROVIDER_ENABLED=1`, only after the private
runtime admission benchmark has passed. The local adapter serializes both port
operations through one `ai.local-inference` lock and invokes the selected model
with `num_ctx=2048` and `num_predict=512`. The output cap prevents an invalid
or repetitive response from consuming the local CPU budget. Granite 4.2 enables its template's thinking mode by
default; the adapter uses Ollama's supported top-level `think=false` control to
disable it, so extraction does not spend CPU time producing a reasoning trace.

## Local Ollama test environment

The optional `ai` Compose profile runs Ollama on the CPU for local development.
It exposes no host port: the app and worker reach it at `http://ollama:11434` on
the Compose network. It permits one loaded model and one request at a time, caps
the context at 2,048 tokens, limits the container to `2500m` (2.441 GiB) RAM
and 1.5 CPUs, and unloads an idle model after 30 seconds. Together with the
application services' idle footprint, this preserves at least 1.4 GiB of a
4 GiB NAS for the operating system; it does not replace the NAS admission
benchmark.

The official Ollama image currently also packages accelerator runtimes and is
large on disk. The profile exposes no GPU devices and runs CPU inference; plan
adequate NAS storage before enabling it there.

```bash
docker compose --profile ai up -d ollama
docker compose exec -T ollama ollama pull granite4.2:3b
```

The following is only the Granite reproduction configuration. Keep the
checked-in default provider as `fake`; do not enable Granite for real extraction
because it failed the corpus gate below.

```dotenv
AI_PROVIDER=local
AI_CANDIDATE_FIT_PROVIDER=local
AI_LOCAL_PROVIDER_ENABLED=1
OLLAMA_HOST_URL=http://ollama:11434
AI_LOCAL_TIMEOUT_SECONDS=300
AI_LOCAL_MODEL=granite4.2:3b
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

The command reports the original corpus score and a critical-field score. The
original score penalizes every unexpected proposal; the critical-field score
measures only labelled fields while still penalizing wrong values for those
fields. Both treat a failed response as missed expected fields. Admission still
requires every response to validate and at least 90% F1. The private deployment
checkout owns NAS resource capture, swap/OOM checks, worker and health checks,
latency recording, and retained evidence.

### Granite 4.2 3B result (rejected)

With `granite4.2:3b`, Ollama `think=false`, a 2,048-token context, 1.5 CPU,
and the `2600m` cap, the 24-case corpus produced 3 structurally valid results,
0.0 precision, 0.0 recall, and 0.0 F1 (`passed: false`). Median latency was
10,914.79 ms and p95 latency was also 10,914.79 ms; the maximum was 26,885.17
ms. Ollama did not restart or OOM, but reached 2.538 GiB of its 2.539 GiB cap.
Together with the application services, this leaves less than the requested
1.4 GiB NAS reserve. Granite 4.2 3B is therefore not approved for local
extraction on this hardware.

### Qwen 2.5 3B result (rejected)

`qwen2.5:3b` was evaluated under the stricter `2500m` (2.441 GiB) limit. Its
peak measured memory was 2.111 GiB, so the host-headroom constraint passed,
and Ollama had no restart or OOM. However, every corpus request generated
hundreds of tokens without completing the structured response: 0 of 24 results
validated, F1 was 0.0, and the evaluator recorded no completed-response
latencies. The final requests emitted 800–878 tokens at roughly 2.7–3.0
tokens/sec before the 300-second client timeout. Qwen 2.5 3B is therefore not
approved for local extraction on this hardware.

### Qwen 3 1.7B result (rejected)

`qwen3:1.7b` was evaluated under the same `2500m` (2.441 GiB) limit with
`num_predict=512`. Its peak observed memory was 1.962 GiB and Ollama had no
restart or OOM, so the host-headroom constraint passed. However, 0 of 24
responses validated and F1 was 0.0. The final requests returned HTTP 200 after
generating 228–307 tokens at roughly 4.4–4.8 tokens/sec; they stopped naturally
instead of reaching the 512-token cap. The output limit therefore prevented
runaway generation without causing this failure. Qwen 3 1.7B is not approved
for local extraction on this hardware.

An enum-constrained retry (the schema and prompt limited `field` to the domain
allowlist) also produced 0 of 24 valid responses and F1 0.0. A raw diagnostic
response then used valid field names but emitted blank proposals for optional
fields until it reached the 512-token cap in the middle of the JSON document.
The extraction schema and prompt now cap the response at eight proposals and
explicitly prohibit blank placeholder proposals. A two-case smoke benchmark
then produced 2 of 2 structurally valid responses, confirming that this fixes
the truncation. Its precision was 0.40, recall 0.67, and F1 0.50, with
85,199.82 ms median and 142,562.53 ms maximum latency. Peak observed Ollama
memory was 1.744 GiB, so the host-headroom constraint still passed. The result
is far below the 0.90 corpus gate; the remaining 22 cases were intentionally
not run, and Qwen 3 1.7B remains unapproved for local extraction on this
hardware.

The latest constrained two-case retry used a field/value-correlated JSON Schema:
`contractType` and `workMode` were restricted to the domain enums, dates,
currencies, salaries, excerpts, and confidence values were constrained, and the
adapter removed an identical company/recruiter proposal before validation. It
produced 1 of 2 valid responses, with 0.70 precision, 0.58 recall, and 0.64 F1;
the critical-field score was 0.875 precision, 0.58 recall, and 0.70 F1. The
single completed response took 130,140.60 ms. Ollama peaked at 1.849 GiB of the
2.441 GiB cap with no OOM or restart.

The raw diagnostics confirm that enum constraints normalized `vast dienstverband`
to `PERMANENT` and `deels thuiswerken` to `HYBRID`. However, Qwen still emitted
the same agency as both company and recruiter, then emitted a second conflicting
`workMode` and an invented ISO date. The adapter safely removes only the first
redundancy; duplicate fields remain invalid and reject the response. The model is
therefore still far below the 90% gate and the remaining 22 cases were not run.

### Qwen 3.5 0.8B result (rejected)

`qwen3.5:0.8b` was selected after comparing current small models that can fit
the 2.441 GiB Ollama allocation. Its official Ollama artifact is 1.3 GB;
Qwen 3.5 2B starts at 2.7 GB and the available Ollama NuExtract package is
2.2 GB, leaving neither a credible runtime margin under this cap. NuExtract 2.0
2B is purpose-built for extraction but is distributed as a separate GGUF and
uses a template-oriented interface, so it requires a dedicated adapter rather
than exercising this provider boundary.

Under the same 2,048-token context, 512-token output limit, `temperature=0`,
and 2.441 GiB cap, the two-case smoke benchmark produced 1 of 2 valid results.
Precision was 1.0, recall was 0.25, and both original and critical-field F1
were 0.40. The completed response took 60,537.10 ms. Peak observed Ollama
memory was 1.636 GiB, the loaded runner reported 567 MB, and no OOM or restart
occurred, so the resource constraint passed.

The valid Dutch response extracted only title, Symfony, and Docker; it omitted
company, location, contract type, and work mode. The other response emitted
only title and Java, with `confidence: 100` rather than the required 0–1 range,
so runtime validation rejected it. Qwen 3.5 0.8B is therefore not approved and
the remaining 22 corpus cases were not run.

### NuExtract 2.0 2B Q4_K_M result (rejected)

NuExtract 2.0 2B was the next evidence-led candidate: it is an MIT-licensed,
multilingual model trained specifically for template-guided extraction, and its
official `Q4_K_M` GGUF is supported by Ollama. NuExtract3 was excluded because
its current 4B vision-language model cannot fit this NAS envelope. Schemer was
also excluded: although its typed-extraction design is compelling, it remains
closed beta and requires a separate source-available native SDK rather than the
self-hosted Ollama stack.

The experimental adapter submitted NuExtract's JSON template instead of the generic
review DTO schema, maps only values that can be grounded in the pasted text,
derives canonical contract/work-mode values from that evidence, and retains the
existing duplicate-organization and result-validation guards. `think=false` is
was restricted to Granite, because NuExtract rejects that Ollama option. The rejected NuExtract adapter has since been removed.

The text-only model load reached 2.332 GiB of the 2.441 GiB Ollama cap. With
the app, worker, and database observed at 106.4 MiB, 54.66 MiB, and 9.168 MiB,
respectively, this preserves roughly 1.5 GiB of a 4 GiB NAS for the host. The
model did not OOM or restart, but the 1.3 GB multimodal projector leaves little
container margin.

The corrected two-case Dutch smoke benchmark yielded 2/2 valid responses,
0.769 precision, 0.833 recall, and 0.80 raw F1. Its critical-field precision
was 1.0, recall 0.833, and F1 0.909; reported warm-case median latency was
29,309.50 ms and maximum latency 30,787.76 ms. It therefore demonstrates that
the template adapter is operational, but does not meet the required 0.90 raw
F1 admission gate. The remaining 22 cases were intentionally not run, and
NuExtract 2.0 2B is not approved for local extraction on this hardware.

### GLiNER2.5 Multi result (candidate; integration required)

The next candidate was `fastino/gliner2.5-multi-v1`: an Apache-2.0,
multilingual, 287M-parameter encoder designed for boundary extraction. It was
selected over Liquid AI's similarly small LFM2 Extract because the latter's
official supported-language list does not include Dutch. Unlike the preceding
candidates, GLiNER2 is not an Ollama generative model: it needs a separate
CPU-only Python/PyTorch runtime and a provider adapter, neither of which has
been added to the application.

The complete 24-case Dutch/English corpus was run in a disposable Python 3.12
container constrained to 2.441 GiB and 1.5 CPUs. It used GLiNER2's typed entity
schema for title, company, location, and technology. Existing evidence-anchored
normalizers supplied contract type, work mode, and literal euro salary ranges;
the model's free classification head was deliberately not trusted for those
enums. The run exited successfully with 0.883 precision, 0.919 recall, and
0.901 raw F1 (critical-field F1 0.913). Median latency was 1,492.49 ms, p95
was 1,703.79 ms, and maximum latency was 2,148.56 ms.

The process reported a 2.320 GiB maximum RSS, while live Docker measurement
was 2.357 GiB of the 2.441 GiB cap. At that point the app, worker, and database
used 78.3 MiB, 74.0 MiB, and 7.8 MiB respectively. This totals about 2.51 GiB,
leaving approximately 1.49 GiB of a 4 GiB NAS for the host: the requested
1.4 GiB reserve passes, but only narrowly. Keeping the otherwise unused Ollama
container running consumes another 27.5 MiB and still leaves about 1.46 GiB.
The corpus score also only clears the 0.90 gate by 0.0007. Therefore GLiNER2.5 Multi is the first viable
technical candidate, not a production approval: any adoption must add a
supervised, bounded Python sidecar and repeat the corpus evaluation through the
real provider, retaining source spans/confidence for review.

### GLiNER2.5 Multi real-sidecar admission procedure

The `gliner` Compose profile provides the bounded CPU-only sidecar for
`fastino/gliner2.5-multi-v1`. It exposes no host port, downloads no model at
runtime, and is disabled by default. The image pins the model revision
`7b005274e82da37dcf1f5fd9d6a2ef413c6a004c`; update the Dockerfile and repeat
the admission benchmark together when changing it.

```bash
docker compose --profile gliner up -d --build gliner
AI_PROVIDER=gliner AI_GLINER_PROVIDER_ENABLED=1 \
  docker compose exec -T app php bin/console app:ai:benchmark-vacancy-corpus --env=prod --no-debug
```

The provider accepts only verified half-open Unicode character spans. It keeps
the model confidence and source range in the encrypted result, derives enums
and EUR salary fields only from literal source evidence, and renders the
verified range as escaped highlighted context during review. The app rejects
malformed sidecar responses without placing vacancy text in an exception.

Admission requires all 24 responses to validate, raw F1 of at least 0.90, no
container restart or OOM, GLiNER memory at or below 2.441 GiB, and enough
combined host headroom to retain at least 1.4 GiB on the NAS. Capture the
sidecar and app/worker/database Docker memory samples for the complete real
provider run; leave `AI_GLINER_PROVIDER_ENABLED=0` if any condition fails.

The implemented real-provider development-host run validated all 24 cases with
0.9189 precision, 0.9189 recall, and 0.9189 raw F1; critical-field F1 was
0.9252. The two complete runs reported 1,099.79–1,195.11 ms median latency,
1,299.42–1,351.97 ms p95 latency, and 1,307.72–4,961.03 ms maximum latency.
During active inference, Docker measured GLiNER at 1.733 GiB of the 2.441 GiB
cap; app, worker, database, and idle Ollama used 108.1 MiB, 90.4 MiB, 50.8 MiB,
and 18.3 MiB. This leaves roughly 2.0 GiB of a 4 GiB host in this measured
stack. This is a real adapter/corpus validation result, not a substitute for
the separate private NAS health, swap, and OOM admission evidence.

## Automated checks

```bash
docker compose exec -T app php vendor/bin/paratest tests/Application/Ai tests/Infrastructure/Ai
docker compose exec -T app php bin/console lint:container --env=test --no-debug
docker compose exec -T app composer ci:doctrine-schema
docker compose exec -T app composer tests
docker compose exec -T app composer analyse
docker compose exec -T app composer ci:php-cs-fixer
```

Provider selection is independent: `AI_PROVIDER` selects vacancy extraction, while `AI_CANDIDATE_FIT_PROVIDER` selects candidate fit (`fake` or `local`). Existing installations that previously used `AI_PROVIDER=local` for candidate fit must explicitly set `AI_CANDIDATE_FIT_PROVIDER=local`; the default is `fake`. The local admission gate remains required.
