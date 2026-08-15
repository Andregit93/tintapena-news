---
name: tintapena-development
description: Project-specific orchestration for implementing, reviewing, debugging, and testing TINTAPENA V1. Use this skill to preserve TINTAPENA product scope, Feature IDs, editorial workflow, database contract, routes, design references, acceptance criteria, and security rules while relying on the installed Laravel, Pest, Tailwind, and convention skills for framework-level guidance.
---

# TINTAPENA Development

This skill is the **project-specific orchestration layer** for TINTAPENA.

Do not duplicate generic Laravel, Pest, Tailwind, or repository-convention guidance already provided by installed workspace skills.

## Existing workspace skills

When relevant, follow these installed skills together with this skill:

- `infer-conventions`
  - inspect and preserve existing project conventions before adding new patterns;
- `laravel-best-practices`
  - use for Laravel implementation details and framework conventions;
- `pest-testing`
  - use for Pest structure, assertions, test organization, and Laravel test practices;
- `tailwindcss-development`
  - use for Tailwind implementation and responsive styling.

This TINTAPENA skill has authority over **project-specific business and product decisions**.

If generic framework guidance conflicts with an approved TINTAPENA requirement, preserve the TINTAPENA requirement and report the conflict instead of silently changing product behavior.

---

# 1. Project Identity

Product:

```text
TINTAPENA
```

Tagline:

```text
Menulis Berdasarkan Fakta
```

Product:

```text
Local digital news portal for Bangka Belitung
```

V1 users:

```text
Public reader = guest
Admin/editorial owner = authenticated Newsroom user
```

Do not introduce public reader accounts or public registration in V1.

---

# 2. Approved Stack

Use the existing project stack:

```text
Laravel 13
PHP 8.4
MySQL
Blade
Tailwind CSS
Alpine.js
Livewire when needed
Filament for Newsroom
Pest
Vite
```

Production target:

```text
Hostinger Premium Shared Hosting
```

Do not introduce a new frontend/backend architecture without explicit user approval.

---

# 3. Project Sources of Truth

Before meaningful implementation, read only the sections relevant to the task.

Use this order:

1. `docs/01-PRD.md`
2. `docs/02-FEATURES.md`
3. `docs/database/erd.dbml`
4. `docs/database/data-dictionary.md`
5. `docs/03-ARCHITECTURE.md`
6. `docs/05-ROUTES.md`
7. `docs/07-ACCEPTANCE-CRITERIA.md`
8. `DESIGN.md`
9. `docs/06-DESIGN-HANDOFF.md`
10. `docs/08-TEST-PLAN.md`
11. `docs/09-SECURITY.md`
12. `docs/10-DEPLOYMENT.md` only when deployment/release is involved

Do not read every document in full when the task only needs a small subset.

Inspect existing code before editing.

---

# 4. Feature-ID Workflow

TINTAPENA development should normally proceed by Feature ID.

Examples:

```text
AUTH-001
ARTICLE-006
HOME-001
SEARCH-001
```

For each task:

1. identify the exact Feature ID;
2. read its requirement in `docs/02-FEATURES.md`;
3. read matching acceptance criteria;
4. inspect existing implementation;
5. identify affected data, routes, UI, and tests;
6. implement only the required scope;
7. run relevant tests;
8. perform visual QA if UI changed;
9. report status.

Do not silently expand one Feature ID into unrelated features.

---

# 5. TINTAPENA Article Lifecycle

Technical article status values are exactly:

```text
draft
scheduled
published
archived
```

The public visibility rule is critical:

```text
status = published
AND
published_at <= now()
```

This must be enforced server-side.

Never expose Draft or future Scheduled content through:

- article detail;
- homepage;
- latest;
- popular;
- search;
- category;
- region;
- tag/topic;
- related news;
- sitemap.

Do not rely on frontend hiding, robots.txt, or inaccessible navigation as protection.

---

# 6. Article Workflow

Supported editorial actions:

```text
Draft
Preview
Publish
Schedule
Archive
```

Preview:

- requires authenticated admin access;
- must not publish the article;
- must not use the public article route;
- must be protected from indexing.

Scheduling:

- uses Laravel Scheduler;
- must not require a permanent queue worker;
- must not publish before the chosen time.

Prefer focused workflow actions where business behavior warrants them, for example:

```text
PublishArticle
ScheduleArticle
ArchiveArticle
```

Do not create abstractions only for architectural appearance.

---

# 7. Database Contract

Do not invent database fields for UI convenience.

Important model rules:

- Article belongs to one author.
- Article belongs to one category.
- Article may belong to one region.
- Article may have one featured media.
- Article belongs to many tags.

Do not merge category and region.

Do not add these fields to `articles`:

```text
is_headline
is_editor_pick
```

Homepage curation uses:

```text
homepage_slots
```

Popularity uses:

```text
articles.views_count
```

for lifetime totals and:

```text
article_view_stats
```

for time-window rankings such as 24 hours and 7 days.

Do not create one database row per page view in V1.

Any approved schema change requires:

1. migration;
2. ERD alignment;
3. data dictionary alignment;
4. appropriate indexes/foreign keys.

---

# 8. Public Route Contract

Respect `docs/05-ROUTES.md`.

Core public routes include:

```text
/
/berita/{article:slug}
/kategori/{category:slug}
/wilayah/{region:slug}
/topik/{tag:slug}
/terbaru
/terpopuler
/cari
/kontak
/sitemap.xml
/{page:slug}
```

The static-page catch-all route must remain after specific routes.

Do not invent alternative route structures without an approved requirement change.

---

# 9. Newsroom / Filament Contract

Use Filament for the private Newsroom.

Standard data management should generally use Filament Resources.

Approved resource targets include:

```text
ArticleResource
CategoryResource
RegionResource
TagResource
AdvertisementResource
PageResource
```

Workflow-oriented screens may use custom Filament Pages such as:

```text
HomepageManager
BreakingNewsManager
MediaLibrary
WebsiteSettings
```

Do not replace Newsroom with a custom SPA.

Critical mobile editor actions must remain usable:

```text
Draft
Preview
Terbitkan
```

---

# 10. Design Implementation

For UI work:

1. read `DESIGN.md`;
2. read the relevant section of `docs/06-DESIGN-HANDOFF.md`;
3. locate the screen in `design-reference/manifest.json`;
4. inspect both Desktop and Mobile PNG references.

Public references:

```text
design-reference/public/
```

Admin references:

```text
design-reference/admin/
```

Do not use screenshot files as page backgrounds.

Build real responsive UI.

The screenshot content is mock content unless project documentation explicitly says otherwise.

Visual priority:

- layout;
- hierarchy;
- spacing;
- typography;
- image proportions;
- responsive behavior;
- approved color system.

---

# 11. Security Constraints

Never weaken these rules for implementation convenience.

Required:

- `/admin` requires authentication;
- no public registration;
- CSRF remains enabled;
- server-side validation is required;
- Draft remains private;
- future Scheduled content remains private;
- upload MIME/type/size is validated;
- public contact form is rate-limited;
- secrets remain in environment configuration;
- production uses `APP_DEBUG=false`.

Do not store these in the `settings` table:

```text
APP_KEY
DB_PASSWORD
SMTP_PASSWORD
API secrets
OAuth client secrets
private tokens
```

Do not render arbitrary untrusted HTML/scripts without an approved sanitization strategy.

---

# 12. Testing

Use the installed `pest-testing` skill for Pest implementation conventions.

This skill determines **what TINTAPENA behavior must be tested**.

Relevant Feature IDs should have tests covering critical business rules.

P0 examples:

- guest cannot access `/admin`;
- invalid admin login fails;
- Draft article is not public;
- future Scheduled article is not public;
- Published article is public;
- Preview does not publish;
- scheduled publishing occurs only when due;
- invalid media upload is rejected;
- contact validation works;
- contact throttling works.

Do not delete or weaken valid tests merely to make the suite pass.

Important bugs should receive regression tests when practical.

---

# 13. Visual QA

If UI changes, automated tests alone are not enough.

Before marking PASS:

- compare Desktop implementation with the matching reference;
- compare Mobile implementation with the matching reference;
- check overflow;
- check navigation;
- check typography;
- check image ratios;
- check major spacing/hierarchy.

Do not claim visual PASS if the comparison has not been performed.

---

# 14. Change Discipline

Before creating a new pattern:

1. inspect existing code;
2. follow `infer-conventions`;
3. reuse existing components where appropriate.

Do not:

- install packages without a real need;
- create repository/service layers for every CRUD by default;
- add Redis as a mandatory dependency;
- add Elasticsearch;
- add a Node.js production backend;
- create microservices;
- introduce React/Vue SPA architecture;
- create a separate API backend for V1;
- require a permanent queue worker.

Keep the solution appropriate for Hostinger shared hosting.

---

# 15. Debugging Workflow

For a bug:

1. identify the Feature ID and expected behavior;
2. reproduce or locate the failure;
3. inspect existing implementation;
4. fix the smallest root cause;
5. add/update regression test if useful;
6. run relevant tests;
7. verify affected UI if applicable;
8. report root cause and verification.

Do not bypass validation, authentication, or publication rules to make a symptom disappear.

---

# 16. Deployment Tasks

Only read deployment documentation when release/server work is requested.

For production:

- never guess credentials;
- never guess absolute server paths;
- never run `migrate:fresh`;
- never run `db:wipe`;
- never regenerate APP_KEY casually;
- never leave `APP_DEBUG=true`;
- never use 777 permissions as a generic fix.

Scheduled publishing is a release-critical feature and production cron must be verified when that feature is active.

---

# 17. Hard Stops

Stop and report instead of guessing when:

- PRD and Feature specification materially conflict;
- requested schema contradicts the approved ERD;
- a requested public behavior would expose Draft/Scheduled content;
- a production operation may destroy data;
- required production credential/path is unknown;
- a major framework/architecture change is being considered;
- acceptance criteria cannot be satisfied without changing approved requirements.

---

# 18. Completion Report

Use this concise format after implementation:

```text
Feature:
<FEATURE-ID>

Status:
PASS / IN PROGRESS / BLOCKED

Changed:
- ...

Tests:
- ...
- PASS / FAIL

Visual QA:
- Desktop PASS / N/A
- Mobile PASS / N/A

Notes:
- ...

Out-of-scope changes:
None / ...
```

A Feature ID is `PASS` only when its relevant acceptance criteria and required verification are complete.
