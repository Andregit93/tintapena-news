# TINTAPENA — Editorial & Data Rule

Apply when working on articles, taxonomy, media, homepage, breaking news, popularity, pages, settings, migrations, or database queries.

Read relevant sections of:

- `docs/02-FEATURES.md`
- `docs/04-DATABASE.md`
- `docs/database/erd.dbml`
- `docs/database/data-dictionary.md`
- `docs/05-ROUTES.md`

## Article lifecycle

Technical status values are exactly:

```text
draft
scheduled
published
archived
```

Public visibility is:

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
- tag;
- related news;
- sitemap.

## Article relationships

An article:

- belongs to one author;
- belongs to one category;
- may belong to one region;
- may have one featured media;
- belongs to many tags.

Do not merge Category and Region.

## Homepage curation

Do not add these fields to `articles`:

```text
is_headline
is_editor_pick
```

Use:

```text
homepage_slots
```

for homepage curation.

## Popularity

Use:

```text
articles.views_count
```

for lifetime totals.

Use:

```text
article_view_stats
```

for 24-hour and 7-day rankings.

Do not create one database row per individual page view in V1.

## Settings

The `settings` table is for non-sensitive website configuration only.

Secrets such as APP_KEY, database passwords, SMTP passwords, API secrets, and OAuth secrets belong in environment configuration.

## Schema changes

Do not invent fields for UI convenience.

An approved schema change must remain aligned with:

- migration;
- ERD;
- data dictionary;
- foreign keys/indexes.
