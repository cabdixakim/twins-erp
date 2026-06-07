---
name: Tailwind 4 arbitrary var() classes broken
description: text-[color:var(--tw-*)] and bg-[color:var(--tw-*)] produce zero compiled CSS rules in this project's Tailwind 4 + Vite build. Use hand-written CSS classes instead.
---

## Rule
Never use `text-[color:var(--tw-*)]`, `bg-[color:var(--tw-*)]`, or `border-[color:var(--tw-*)]` Tailwind arbitrary value classes. They compile to zero CSS rules in this project.

## Confirmed broken patterns
```
text-[color:var(--tw-fg)]         → 0 rules compiled
bg-[color:var(--tw-surface)]      → 0 rules compiled
border-[color:var(--tw-border)]   → 0 rules compiled
hover:bg-[color:var(--tw-surface-2)] → 0 rules compiled
```

The build log warning `.\[color\:var\(\)\] { color: var(); }` is the tell.

**Why:** Tailwind 4 JIT scanner does not expand PHP string interpolation in `@php` blocks at build time, and the `[color:var()]` type-hint syntax may not be fully supported for CSS variable references in this build setup.

## How to apply
For any token-based color that needs to be reusable:
1. Add a CSS utility class to `resources/css/app.css` using the token directly:
   ```css
   .tw-fg   { color: var(--tw-fg); }
   .tw-muted { color: var(--tw-muted); }
   .tw-nav-item { background: var(--tw-surface); ... }
   ```
2. For one-off token colors in templates: use `style="color:var(--tw-fg)"` inline.
3. For accent colors (emerald, rose, sky, amber, purple) that don't change with theme: use hex values directly in `style=` attributes — these are intentional, not token-dependent.

## What IS safe to use
- Standard Tailwind layout/spacing classes: `flex`, `gap-4`, `p-5`, `rounded-2xl`, etc.
- Custom CSS classes from `app.css`: `.tw-card`, `.tw-fg`, `.tw-muted`, `.tw-nav-item`, `.tw-nav-icon`, `.tw-nav-pip`, `.tw-surface`, `.tw-surface-2`, `.tw-icon-btn`, `.tw-pill`, `.tw-field`
- Inline `style=` with `var(--tw-*)` for anything not covered by the above
