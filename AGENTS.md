# AGENTS.md

## How to Set Up and Run

- **Backend**: Laravel 12  
  Run backend dev server:
    ```bash
    php artisan serve
    ```
- **Frontend**: Vue 3 (Vite + Tailwind v4 + Inertia.js v2)  
  Run frontend dev server:
    ```bash
    npm run dev
    ```
- **Build for Production**:
    ```bash
    npm run build
    ```
- **Install dependencies** (required on all branches after dependency changes):
    ```bash
    composer install
    npm install
    ```

## Tests

- **Run all backend tests**:
    ```bash
    php artisan test
    ```
- **Run a specific test file**:
    ```bash
    php artisan test tests/Feature/ConversationSystemTest.php
    ```
- **Run a specific test by name**:
    ```bash
    php artisan test --filter=advisor_can_view_pending_appointments
    ```
- **Tests require up-to-date migrations and seeded data**:
    ```bash
    php artisan migrate
    php artisan db:seed --class=ConversationSystemSeeder
    ```

## Lint, Format, Typecheck

- **Lint and fix frontend**:
    ```bash
    npm run lint
    ```
- **Format frontend**:
    ```bash
    npm run format
    ```
- **Frontend uses Prettier, ESLint, and TypeScript**

## CI and Quality

- **Workflow requires:**
    1. `composer install`
    2. `npm install`
    3. Copy `.env.example` to `.env` and generate key:
        ```bash
        cp .env.example .env
        php artisan key:generate
        ```
    4. `npm run build`
    5. Run tests:  
       `phpunit` (direct, not `php artisan test`) in CI

- **Frontend assets must be rebuilt after significant backend (routes/controllers) or frontend code changes**:
    ```bash
    npm run build
    ```
    or for hot reload during dev:
    ```bash
    npm run dev
    ```

## Code/Architecture Structure

- **Not monorepo** – single app, tight Laravel/Vue integration via Inertia.js.
- **Vue/TS components** live under:  
  `resources/js/Pages/Conversations/`
- **Core Laravel controllers:**  
  `app/Http/Controllers/Conversation*Controller.php`
- **Key backend models/factories/seeders/tests** follow the `Conversation*` naming and logical structure

## Framework or Tooling Conventions

- **Wayfinder:** Used for type-safe backend/frontend route generation (see `vite.config.ts`)
- **Tailwind v4, not v3** – Themes via @theme, no classical tailwind.config.js.
- **Use Prettier and ESLint rules as configured**; do not add rules unilaterally.
- **Policy-based authorization**—adjust both Model and Policy when adding permissions/roles.
- **All frontend, backend, and database changes MUST have corresponding tests.**
- **Check for + follow existing code, naming, and directory conventions before adding new files or code.**
- Use factories for test data; do not manually create test records.

## Troubleshooting & Common Gotchas

- **Frontend changes not reflected?**  
  Ensure `npm run build` or `npm run dev` was run and browser cache cleared.
- **CI or local test failures after branch switches?**  
  Always re-run both `composer install` and `npm install` and migrate/seed the database.
- **New/edited routes:**  
  If using Wayfinder, regenerate TS client if not auto-updated:
    ```bash
    php artisan wayfinder:generate
    ```
- **Policy 403 errors?**  
  Double check user role, department_id, and policy logic—see `Conversation*Policy.php` for details.
- **Adding new DB columns?**  
  Always create a fresh migration and update corresponding models, policies, forms, Vue components, backend tests.

## Further Reference

- Detailed system design: `CONVERSATION_SYSTEM.md`
- Quick verification of commands and workflow: `QUICKSTART.md`
- Copilot/agent coding do's and don'ts: `.github/copilot-instructions.md`
- For any ambiguity, check sibling files and referenced docs for conventions.
