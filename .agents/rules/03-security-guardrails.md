# TINTAPENA — Security Guardrails

Apply to all behavior that can affect authentication, public visibility, user input, uploads, or production data.

Read relevant sections of:

- `docs/07-ACCEPTANCE-CRITERIA.md`
- `docs/09-SECURITY.md`

## Non-negotiable rules

- `/admin` requires authentication.
- No public registration in V1.
- Draft content is never public.
- Future Scheduled content is never public.
- Preview requires authenticated Newsroom access.
- CSRF protection stays enabled.
- Mutating input is validated server-side.
- Public contact submission is rate-limited.
- Upload type/MIME/size is validated.
- Secrets are not stored in source code or the `settings` table.
- Production uses `APP_DEBUG=false`.

## Rich content

Do not render arbitrary untrusted HTML or scripts without an approved sanitization strategy.

Article and static-page content must not become a path to stored XSS.

Advertisement script handling is separate from article rich text and is admin-only.

## Test expectation

When a change affects a critical security/business rule, ensure the relevant behavior is covered by tests.

Especially preserve tests for:

- guest denied from admin;
- Draft not public;
- future Scheduled article not public;
- Preview does not publish;
- invalid upload rejected;
- contact validation/rate limiting.

Do not weaken tests to force a PASS.
