# TINTAPENA — Design Handoff Rule

Apply to public UI and Newsroom UI work.

Read:

- `DESIGN.md`
- `docs/06-DESIGN-HANDOFF.md`
- `design-reference/README.md`
- `design-reference/manifest.json`

Use the matching local screenshots:

```text
design-reference/public/
design-reference/admin/
```

## Visual implementation rule

The PNGs are visual references only.

Do not:

- use screenshots as page backgrounds;
- recreate screenshot text as hardcoded production data;
- redesign the screen without an approved requirement change.

Build real responsive components.

## Responsive references

Primary references:

```text
Desktop: 1440px
Mobile: 390px
```

Mobile is not merely a scaled-down desktop layout.

## Visual priorities

Preserve:

- content hierarchy;
- layout structure;
- spacing system;
- typography system;
- image proportions;
- navigation behavior;
- approved design tokens;
- responsive behavior.

Avoid adding visual patterns not present in the approved design, such as excessive cards, shadows, gradients, or dashboard styling on the public site.

## Public implementation

Use the project's Blade/Tailwind conventions and reuse existing components when appropriate.

## Newsroom implementation

Use Filament as the admin foundation.

Do not replace Filament with a custom SPA.

Critical mobile article actions must remain usable:

```text
Draft
Preview
Terbitkan
```

## Completion

UI work is not visually complete until both Desktop and Mobile have been compared against their matching references.
