---
name: Custom Http/Kernel.php lacks stock Laravel middleware aliases
description: This app's app/Http/Kernel.php defines $routeMiddleware manually and does NOT include Laravel's default aliases (throttle, cache.headers, signed, etc). Using them by name causes a silent "Target class [x] does not exist" 500.
---

Using `throttle:6,1` (or other stock Laravel middleware aliases) directly in `routes/web.php` on this app fails at runtime with `BindingResolutionException: Target class [throttle] does not exist` — it does NOT fail at route registration or `php -l`, only when the route is actually hit.

**Why:** `app/Http/Kernel.php` extends `Illuminate\Foundation\Http\Kernel` but manually defines `$routeMiddleware` with only a curated list of custom aliases (auth, role, permission, active.company, company.setup, user.active). Laravel's usual auto-registered aliases (throttle, signed, cache.headers, etc.) are not present unless added explicitly.

**How to apply:** Before using any stock Laravel middleware alias (`throttle`, `signed`, `cache.headers`, `verified`, etc.) in routes, grep `app/Http/Kernel.php`'s `$routeMiddleware` array first. If missing, add it explicitly, e.g. `'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,` — then verify with an actual HTTP request (not just `php -l` / `route:list`), since the class-not-found error only surfaces at request time.
