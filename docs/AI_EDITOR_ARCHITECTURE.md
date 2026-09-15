# FARAST AI Editor Architecture

Last audited/updated: 2026-09-15

This document describes the code that actually exists in `main`. Status labels are literal: **IMPLEMENTED**, **PARTIALLY IMPLEMENTED**, **PLANNED**, **NOT AVAILABLE**.

## Current implementation

### Text/editor AI — IMPLEMENTED

The existing endpoint `POST /editor/ai/assist` remains the compatibility endpoint. `EditorAiAssistController` is thin and delegates to `EditorAiAssistService`, which is now a compatibility facade over `App\Services\AI\AiOrchestrator`.

The orchestration path is:

`request -> operation registry -> quota -> privacy policy -> context engine -> deterministic provider selection -> provider adapter -> normalized result -> safe telemetry`

Core classes:

- `AiOperationRegistry`: canonical operation names, legacy aliases, input scope, per-operation input ceiling and cost class.
- `AiContextEngine`: sends only whitelisted context and refuses oversized documents instead of silently truncating them.
- `AiPrivacyPolicy`: resolves `automatic|local|server|external`. Current server text operations only have an external provider, so explicit `local` or `server` is rejected; it never silently falls back to external.
- `AiQuotaService`: centralizes existing account capability and daily request checks for editor AI and voice.
- `AiProvider`: provider contract.
- `GeminiEditorProvider`: the currently selected external text provider. Gemini keys are read only server-side from `SiteSetting` or `config/services.php`.
- `AiOrchestrator`: request ID, operation validation, quota/privacy/context checks, provider execution, normalized response and privacy-safe telemetry.

### Operation registry — IMPLEMENTED

Legacy names remain accepted: `selection` -> `selection.format_suggest`, `word` -> `selection.spellcheck`, and `punctuation` -> `selection.punctuation`.

Canonical text operations currently executable through Gemini are:

`selection.format_suggest`, `selection.spellcheck`, `selection.punctuation`, `selection.proofread`, `selection.rewrite`, `selection.paraphrase`, `selection.tone`, `selection.shorten`, `selection.expand`, `selection.summarize`, `selection.explain`, `selection.translate`, `text.generate`, `text.continue`, `text.title`, `text.outline`, `text.keywords`, `document.summarize`, `document.improve`, `document.analyze`.

The existing UI currently invokes only format suggestion, spelling and punctuation. The additional operations are backend-capable extension points and are not claimed as finished UI features.

### Normalized response contract — IMPLEMENTED for editor AI and voice

Editor AI returns existing top-level fields for backward compatibility and also an `ai` object containing request id, canonical operation, provider, model, resolved processing mode, status, result, suggestions, warnings, metadata, usage and error fields. Voice exposes the same conceptual metadata while retaining its existing top-level response fields.

### Editor integration and formatting preservation — PARTIALLY IMPLEMENTED

The real `contenteditable` editor is used; no demo/parallel editor was introduced. The existing selection mini-toolbar still uses a cloned DOM Range for formatting suggestions.

A destructive bug in the previous voice-punctuation flow was removed. Previously punctuation correction replaced the entire editor `innerHTML` with plain paragraphs, destroying rich formatting. The current flow stores the newly inserted voice text node, sends only that new text for punctuation correction, and updates only that text node. Existing bold/italic/links/tables/lists and unrelated document structure are therefore not rebuilt by this operation.

Structured per-suggestion proofread data is available from the backend, but a complete accept-one/reject-one/accept-all rich-text suggestion UI is **PLANNED**.

### Context minimization — IMPLEMENTED for current editor API

The context engine accepts only explicitly whitelisted small context fields: sentence context, before/after cursor windows, target language, tone and short instruction. Unknown fields such as an accidentally supplied whole document are not forwarded to the provider.

Document operations require the caller to explicitly choose a `document.*` operation. Documents over the current single-call limit are rejected rather than truncated. Hierarchical chunking/map-reduce/retrieval for large documents is **PLANNED**. No vector database or embedding infrastructure has been added because the current application does not yet require it.

## Privacy modes

| Mode | Current behavior |
|---|---|
| `local` | **NOT AVAILABLE** for model inference; explicit request is rejected by server endpoints rather than uploaded externally. |
| `server` | **NOT AVAILABLE** for self-hosted inference; explicit request is rejected rather than routed to Gemini. |
| `external` | **IMPLEMENTED** for Gemini editor/OCR/voice and Google Cloud Speech when configured. |
| `automatic` | **IMPLEMENTED** as deterministic policy resolution. It selects only among modes declared available for that operation and never sends the same content to multiple external providers. |

There is no automatic cross-provider retry. Voice selects Google Cloud Speech only when explicitly configured and compatible with the MIME type; otherwise Gemini is selected before the request. A failure on the selected provider is not silently retried at another company.

## Voice — IMPLEMENTED / PARTIALLY IMPLEMENTED

The real editor records with `MediaRecorder`, uses supported WebM/OGG choices, uploads to `POST /editor/voice/transcribe`, and inserts plain transcript text at the caret.

Backend validation enforces the account voice capability, daily AI quota, maximum upload size, MIME allowlist and locale allowlist (`fa-IR`, `en-US`, `ar-SA`). Processing mode is explicit/resolved. Engines are Google Cloud Speech when configured for a compatible format, otherwise Gemini.

Browser-native/local speech recognition is **NOT AVAILABLE** in the current code. Voice commands controlling editor actions are **PLANNED**.

## OCR / document image processing — IMPLEMENTED / PARTIALLY IMPLEMENTED

`GeminiService` remains the existing OCR implementation and was hardened rather than duplicated. It supports images, PDFs and ZIPs containing allowlisted image extensions. Large image/PDF input can use Gemini Files API. The OCR response is schema-constrained into blocks and uncertain words.

Security hardening now treats document content as untrusted data, validates the returned structural shape, stores only normalized error codes rather than raw provider bodies, and removes temporary ZIP files in `finally`.

Specialized local OCR, dedicated handwriting OCR, preprocessing pipelines and post-OCR grammar correction as a separate optional stage are **PLANNED**. OCR and grammar correction remain separate capabilities.

## Security and telemetry

### IMPLEMENTED

- Gemini/provider credentials stay server-side; no API key is returned to browser code.
- Existing authenticated web routes, CSRF protection and route throttles are preserved.
- Capability and quota checks are centralized for editor AI and voice.
- Provider HTTP bodies are not persisted in the new editor AI, voice or OCR failure paths; normalized error codes are logged instead.
- Editor/OCR prompts explicitly treat user document content as untrusted data to reduce prompt-injection impact.
- AI text is returned as JSON. Formatting suggestions use an allowlisted action vocabulary, not provider-generated HTML.
- Voice punctuation updates a text node, not `innerHTML`.
- Telemetry stores hashes, byte counts, operation/provider/model/status/latency and bounded metadata; it does not intentionally store full editor prompt/output text.

### Remaining risks / limitations

- `ai_feedback` intentionally supports optional original/corrected text supplied by users; this is product data, not background telemetry, and needs a retention policy.
- Existing OCR Files API uploads have no implemented provider-side deletion/retention cleanup in this repository.
- The daily request quota is count-based and has a small concurrent-request race because there is no atomic daily usage counter. Route throttling limits abuse but does not make quota accounting transactionally exact.
- Full rich-text AI replacement/diff application is not implemented; only safe current operations are wired into the UI.
- No URL-fetching AI tool exists, so SSRF is not introduced by this AI layer. Future URL-capable vision/web tools require strict allowlists and egress controls.

## Browser-side AI evaluation

No large browser AI dependency was added in this change. This is intentional.

- WebGPU can provide strong local inference performance, but support is not uniform across FARAST browser targets and requires HTTPS plus capable hardware/drivers.
- ONNX Runtime Web has a portable WASM CPU path and a WebGPU execution provider, but GPU support varies materially by browser/platform; WASM can be too slow or memory-heavy for substantial language models.
- Transformers.js can run supported transformer models through browser/WASM/WebGPU, but model download size, cache storage, first-load time, mobile memory and Persian model quality must be evaluated per model before shipping.
- Browser-native AI APIs are not used because availability/model behavior is browser-specific and cannot be assumed for FARAST's Firefox-oriented users.

A future local adapter must lazy-load only after explicit user action, show model download size before downloading, cache by immutable model/version, run capability and memory checks, support cancellation, and declare its license. A local failure must never silently upload the same text.

Browser-side model inference status: **NOT AVAILABLE**.

## Capability matrix

| Capability | Local | FARAST server/self-hosted | External | Current status |
|---|---|---|---|---|
| Formatting suggestion | rule-based editor actions only | no model | Gemini | **IMPLEMENTED** |
| Spelling check | no model | no model | Gemini | **IMPLEMENTED** |
| Punctuation | no model | no model | Gemini | **IMPLEMENTED**; voice flow preserves surrounding formatting |
| Proofreading suggestions | no | no | Gemini | **IMPLEMENTED backend**, UI **PLANNED** |
| Rewrite/paraphrase/tone/shorten/expand | no | no | Gemini | **IMPLEMENTED backend**, UI **PLANNED** |
| Summarization/explanation/translation | no | no | Gemini | **IMPLEMENTED backend**, UI **PLANNED** |
| Generate/continue/title/outline/keywords | no | no | Gemini | **IMPLEMENTED backend**, UI **PLANNED** |
| Document summarize/improve/analyze | no | no | Gemini up to single-call limit | **PARTIALLY IMPLEMENTED**; large-doc chunking planned |
| Speech-to-text | browser recording only | no engine | Google Speech or Gemini | **IMPLEMENTED** |
| Printed image/PDF OCR | no | no engine | Gemini | **IMPLEMENTED** |
| Handwriting OCR | no | no | Gemini may process images but no dedicated validated handwriting path | **PARTIALLY IMPLEMENTED / not guaranteed** |
| Vision understanding | no | no | no normalized vision operation | **PLANNED** |
| Image generation | no | no | no provider | **NOT AVAILABLE / PLANNED** |
| Sketch/shape recognition | geometry adapter not implemented | no | no | **NOT AVAILABLE / PLANNED** |
| Embeddings/RAG | no | no | no | **NOT AVAILABLE; intentionally deferred** |

## Large documents

Current behavior is fail-closed: `document.*` requests beyond the registry single-call ceiling are rejected with validation rather than truncated. Planned extension is section-aware chunking followed by hierarchical aggregation, preserving heading/table/list boundaries when the editor exposes sufficient structure. Retrieval/embeddings should only be introduced if repeated document-wide querying makes it necessary.

## Quota and cost control

`AiQuotaService` preserves the existing `daily_ai_requests` entitlement. Registry operations carry `low|medium|high` cost classes so future weighted accounting can be introduced centrally without scattering constants through controllers or JavaScript. Weighted billing is **NOT IMPLEMENTED**; cost classes currently appear only in metadata/telemetry.

## Tests and CI

`tests/Unit/AiCorePolicyTest.php` covers legacy operation mapping, deterministic privacy behavior, context allowlisting and large-document rejection.

`tests/Feature/EditorAiAssistTest.php` covers unauthenticated access, normalized/backward-compatible responses, API-key non-disclosure, local-mode no-fallback behavior, quota enforcement before provider calls and safe provider error persistence.

`.github/workflows/ci.yml` runs PHP syntax, JavaScript syntax, MariaDB and SQLite migrations, route listing, Blade compilation and `php artisan test`.

## Future extension points

### Vision

Add a distinct capability/provider method and normalized image-analysis result. Do not overload OCR with free-form vision behavior.

### Image generation

Add only when a provider and product policy are selected. Required flow: prompt -> policy/quota -> provider -> MIME/dimension validation -> private storage owned by user -> explicit editor insertion -> cleanup/retention policy. Provider credentials remain server-only.

### Drawing recognition

Keep separate from image generation. Prefer local geometry analysis over sending raw strokes externally: strokes -> feature/geometry extraction -> recognized primitive (`line`, `arrow`, `rectangle`, `ellipse`, etc.) -> editable editor object. External vision should be opt-in only for ambiguous drawings.

### Provider expansion

A future OpenAI-compatible/self-hosted/local adapter should implement `AiProvider` (or a capability-specific sibling interface when non-text media is introduced), advertise supported operations/modes, and return provider-neutral results. Provider selection must stay deterministic and inspectable; no fan-out of user content is permitted.
