## Quick orientation for an AI coding assistant

This file contains targeted, actionable guidance to help an AI assistant be productive in this repository.

- Project: Laravel 12 PHP application (requires PHP ^8.2).
- Primary purpose: a small dashboard that integrates multiple AI providers (Gemini, OpenAI, Anthropic, Mistral).

### Big picture / architecture

- Framework: Laravel (see `composer.json`). Main entry points: `routes/web.php` and controllers in `app/Http/Controllers/`.
- AI integration is centralized in `App\Repositories\AIRepository` (`app/Repositories/AIRepository.php`). This class:
  - Handles provider detection and configuration (`isProviderConfigured`, `getAvailableModels`).
  - Routes generation calls to provider-specific private methods (`generateWithOpenAI`, `generateWithGemini`, `generateWithAnthropic`, `generateWithMistral`).
  - Supports both streaming and non-streaming responses (`stream*` methods and `streamResponse`).
  - Builds prompts by merging `config/ai.php` prompts, optional profile JSONs, and file context from `storage/app/public`.

- Repository pattern: interfaces in `app/Contracts/` are bound to implementations in `app/Providers/RepositoryServiceProvider.php`. Use these bindings when creating or adjusting code.

- JSON-backed data stores: classes extending `App\Repositories\JsonRepository` read/write JSON in `storage/app/public/json/*.json` and cache results (see `app/Repositories/JsonRepository.php`). Keep changes atomic and preserve JSON formatting used by `save()`.

### Important files and directories to cite in edits

- `app/Repositories/AIRepository.php` — core AI behavior, streaming, prompt construction, and provider client usage.
- `config/ai.php` — default handler, tasks mapping, predefined prompts, default files and profiles.
- `storage/app/public/json/` — primary runtime data (profiles under `json/profiles/*.json`). AI profiles are loaded from here (see `loadProfile`).
- `app/Contracts/` and `app/Providers/RepositoryServiceProvider.php` — dependency injection and service bindings (prefer interfaces in new code).
- `routes/web.php` and `app/Http/Controllers/DashboardController.php` — public entry points for the dashboard.

### Project-specific patterns and conventions

- AI handler format: `provider:model` (e.g., `gemini:gemini-2.5-flash-lite`). `config/ai.php` uses this format for `default_handler` and `tasks.*`.
- Predefined prompts: keys in `config('ai.prompts')` may be used as prompt templates. Some templates expect placeholder tokens like `:input` or `:history` (these are replaced in `buildBasePromptParts`).
- File context: AI file contexts are read from `storage/app/public` only. Paths passed to AI calls must be relative to that disk. `getFileContentForPrompt` sanitizes paths and will log and discard invalid requests.
- Streaming flag: the request payload may include `stream` to request streaming output. Respect streaming code paths in `AIRepository` and ensure client compatibility.
- Error handling: `AIRepository::handleErrorResponse` returns JSON error messages and logs exceptions. For system-level failures (like missing binaries), logs are used (`Log::error`/`Log::critical`).

### Integrations & external dependencies to be aware of

- Providers and SDKs (declared in `composer.json`):
  - `google-gemini-php/laravel` (Gemini)
  - `openai-php/laravel` (OpenAI)
  - `mozex/anthropic-laravel` (Anthropic wrapper)
  - `helgesverre/mistral` (Mistral)
- System dependency: PDF text extraction uses `spatie/pdf-to-text` which requires `pdftotext` (poppler-utils). If PDF extraction fails, logs indicate missing binary.

### Developer workflows (commands you can run)

- Install & setup (from project root): `composer run-script setup` — installs PHP deps, copies `.env`, runs migrations, and builds frontend assets.
- Development (multi-process): `composer run-script dev` — runs `php artisan serve`, queue worker, pail logging, and `npm run dev` concurrently.
- Tests: `composer test` (clears config cache then runs `@php artisan test`). Use `vendor/bin/phpunit` if you need raw PHPUnit.

### Examples and quick rules for code changes

- When adding a new AI-related feature, prefer using the existing `AIRepository` behavior: add task mappings to `config/ai.php` and handle provider/model selection via `getTaskConfig()`.
- For storage-backed content or profiles, add JSON files under `storage/app/public/json/` and ensure `JsonRepository` semantics apply (use `File::put` / `Cache::forget` patterns already present).
- Bind new services via `app/Providers/RepositoryServiceProvider.php` and prefer interfaces under `app/Contracts/` for testability.

### Safety and observability notes

- The app logs warnings and errors via Laravel's logger in many AI code paths. Inspect `storage/logs/laravel.log` when debugging provider failures.
- API keys are read from `config/services.php` (and provider-specific config files). Never hard-code keys — use `.env`.

If anything here is unclear or you want me to include more examples (sample request payload to `AIRepository::generate`, or a short checklist for adding a new provider), tell me which part and I'll iterate.

### Sample request payloads

Non-streaming chat (use `prompt` as key for predefined prompts or a free-text prompt):

```
{
  "prompt": "chat",
  "input": "Explain dependency injection in Laravel",
  "model": "gpt-4o-mini",         // optional: overrides task/model
  "profile": "assistant",         // optional: name of profile in storage/app/public/json/profiles/
  "file_paths": ["docs/design.md"],
  "history": [
    {"role":"user","text":"What is DI?"}
  ]
}
```

Streaming chat (request partial output; back-end will use streaming methods):

```
{
  "prompt": "chat",
  "input": "Give me a code example for a service provider",
  "stream": true,
  "model": "gemini-2.5-flash"
}
```

Prompt suggestions (the UI calls `suggestPrompts`):

```
{
  "prompt": "suggest",
  "history": [ {"role":"user","text":"How do I deploy Laravel?"} ]
}
```

Notes:
- `prompt` may be a key from `config('ai.prompts')` (e.g., `explanation`, `summarize`) or free text.
- File paths must be relative to `storage/app/public` and will be sanitized by `AIRepository::getFileContentForPrompt`.

### Checklist: adding a new AI provider

1. Add the provider SDK to `composer.json` and run `composer require`.
2. Add configuration / env keys (prefer `config/services.php` or `config/<provider>.php`) and document required `.env` variables in `.env.example`.
3. Register any provider client bindings in `App\Providers\RepositoryServiceProvider` or a dedicated service provider so the client can be resolved from the container.
4. Update `AIRepository`:
   - add a private client property and lazy getter (e.g., `getNewProviderClient()`), following existing patterns (`getOpenAIClient`, `getGeminiClient`).
   - implement `isProviderConfigured()` to check for the provider's API key.
   - implement `generateWithNewProvider(array $data)` and `streamNewProviderResponse(...)` mirroring the other providers. Reuse `buildBasePromptParts()` to include system prompt / file context.
   - wire non-streaming output into `generateResponse()` and streaming into `streamResponse()` with a text extractor callback.
   - add provider model discovery to `getAvailableModels()` (if SDK supports it) or use a direct HTTP call like Anthropic does.
   - add provider handling to `getTextResponse()` where appropriate.
5. Add task mapping to `config/ai.php` for the new provider/model (using `provider:model` format) if needed.
6. Add or update tests: mock the provider client and assert `AIRepository::generate` returns expected JSON structure and streaming behavior.
7. Update README / `.github/copilot-instructions.md` to document env keys and any system binaries (e.g., `pdftotext`) required.

This checklist follows the existing patterns in `app/Repositories/AIRepository.php` and `app/Providers/RepositoryServiceProvider.php`.

---

Please review these additions and tell me if you'd like example curl commands for streaming or a tiny test skeleton for `AIRepository` (I can add those next).
