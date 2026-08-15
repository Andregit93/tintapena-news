# TINTAPENA — Hostinger Deployment Rule

Apply only to production, hosting, release, environment, cron, storage, or migration tasks.

Read:

- `docs/10-DEPLOYMENT.md`
- `docs/09-SECURITY.md`

Production target:

```text
Hostinger Premium Shared Hosting
```

## Production constraints

Do not make production depend on:

- Redis;
- Elasticsearch;
- Docker;
- permanent queue workers;
- Node.js backend runtime.

## Never do automatically

Never:

- commit `.env`;
- guess production credentials;
- guess absolute server paths;
- run `php artisan migrate:fresh` in production;
- run `php artisan db:wipe` in production;
- regenerate APP_KEY casually;
- leave `APP_DEBUG=true`;
- use project-wide `777` permissions;
- run `composer update` as a normal release step.

## Scheduled publishing

Laravel Scheduler / cron is required for scheduled articles.

When deployment is performed, verify the actual:

- project path;
- PHP binary path;
- cron command.

A release with Scheduled Article enabled is not complete until scheduled publishing has been tested end-to-end.
