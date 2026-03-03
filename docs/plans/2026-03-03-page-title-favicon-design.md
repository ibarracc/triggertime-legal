# Design: Page Title & Favicon

**Date:** 2026-03-03

## Overview

Update the browser tab title from the default `client` placeholder to `TriggerTime`, add dynamic per-page titles via Vue Router meta, and replace the default Vite SVG favicon with the TriggerTime logo.

## Approach

**Router meta + PNG favicon** — no new dependencies.

- Add a `title` string to each route's `meta` in `router/index.js`
- Add a `router.afterEach` guard that sets `document.title`
- Update `client/index.html` to use `triggertime.png` as favicon and `TriggerTime` as the static fallback title

## Title Format

```
TriggerTime          ← home / landing (no suffix)
TriggerTime | Login  ← all other pages
```

## Route Title Map

| Route | `meta.title` |
|---|---|
| `/` | *(none — defaults to "TriggerTime")* |
| `/login` | `Login` |
| `/register` | `Register` |
| `/forgot-password` | `Forgot Password` |
| `/reset-password/:token` | `Reset Password` |
| `/privacy` | `Privacy Policy` |
| `/terms` | `Terms of Service` |
| `/checkout/:token` | `Checkout` |
| `/checkout-success` | `Checkout Complete` |
| `/dashboard` | `Dashboard` |
| `/dashboard/subscription` | `Subscription` |
| `/dashboard/devices` | `My Devices` |
| `/dashboard/profile` | `Profile` |
| `/admin/dashboard` | `Admin \| Dashboard` |
| `/admin/users` | `Admin \| Users` |
| `/admin/licenses` | `Admin \| Licenses` |
| `/admin/licenses/import` | `Admin \| Import Licenses` |
| `/admin/devices` | `Admin \| Devices` |
| `/admin/instances` | `Admin \| Instances` |
| `/admin/versions` | `Admin \| Versions` |
| `/admin/subscriptions` | `Admin \| Subscriptions` |
| `/admin/remote-configs` | `Admin \| Remote Configs` |
| `/admin/remote-configs/:id` | `Admin \| Remote Config` |
| `/:pathMatch(.*)` | `Not Found` |

## Files Changed

- `client/index.html` — favicon link + static title
- `client/src/router/index.js` — route meta titles + afterEach guard
