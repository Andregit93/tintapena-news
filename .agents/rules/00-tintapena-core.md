# TINTAPENA — Core Rule

Apply to all work in this repository.

## Project scope

TINTAPENA is a local digital news portal for Bangka Belitung.

V1 has:

- public readers as guests;
- one private Newsroom under `/admin`;
- no public registration/login;
- no comments, subscription, newsletter manager, or multi-level editorial approval.

Approved stack:

- Laravel 13
- PHP 8.4
- MySQL
- Blade
- Tailwind CSS
- Alpine.js
- Livewire when needed
- Filament for Newsroom
- Pest
- Vite

Production target:

- Hostinger Premium Shared Hosting

Do not introduce another application architecture without explicit approval.

## Project source of truth

For project-specific behavior, use this priority:

1. `docs/01-PRD.md`
2. `docs/02-FEATURES.md`
3. `docs/database/erd.dbml`
4. `docs/database/data-dictionary.md`
5. `docs/03-ARCHITECTURE.md`
6. `docs/05-ROUTES.md`
7. `docs/07-ACCEPTANCE-CRITERIA.md`
8. `DESIGN.md`
9. `docs/06-DESIGN-HANDOFF.md`
10. existing implementation

If there is a material conflict, report it instead of guessing.

## Work by Feature ID

Implementation should normally be scoped to one Feature ID at a time.

Before coding:

1. identify the Feature ID;
2. read its feature requirement;
3. read its acceptance criteria;
4. inspect existing code;
5. implement only the requested scope;
6. verify the result.

Do not silently expand scope.

## Project architecture guardrails

Do not add by default:

- React/Vue SPA;
- Next.js/Nuxt;
- Node.js production backend;
- separate API backend;
- microservices;
- Elasticsearch;
- mandatory Redis;
- mandatory long-running queue worker.

Use the installed framework-specific skills for Laravel, Pest, Tailwind, and repository conventions.
