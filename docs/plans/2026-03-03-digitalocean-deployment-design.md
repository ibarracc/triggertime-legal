# DigitalOcean Deployment Design

**Date:** 2026-03-03
**Status:** Approved

## Overview

Deploy TriggerTime Site (CakePHP 5.3 backend + Vue 3 SPA frontend) to a single DigitalOcean Droplet using nginx + PHP-FPM + PostgreSQL, with automated deployments via GitHub Actions.

## Approach

Single Droplet running all services directly on Ubuntu 24.04 (no containers). GitHub Actions builds the Vue SPA on the CI runner and deploys via rsync + SSH.

Rejected alternatives:
- **Docker Compose on Droplet** — adds complexity without benefit at this scale
- **DO App Platform** — requires Docker, more expensive, less control

## Infrastructure

| Component | Choice | Cost |
|---|---|---|
| Compute | DigitalOcean Droplet, 2GB RAM / 1 vCPU, Ubuntu 24.04 | ~$12/mo |
| Web server | nginx | — |
| PHP runtime | PHP 8.4-FPM | — |
| Database | PostgreSQL 16 (on same Droplet) | — |
| SSL | Let's Encrypt via Certbot (auto-renewing) | free |
| Node.js | CI runner only — NOT installed on server | — |

**Total: ~$12/month**

## Server Directory Layout

```
/var/www/triggertime/
├── webroot/              ← nginx document root
│   ├── index.php         ← CakePHP front controller
│   └── spa/              ← Built Vue SPA (rsynced from CI)
├── src/
├── config/
│   ├── app.php           ← in git
│   └── app_local.php     ← NOT in git, created manually on server once
├── vendor/               ← installed by composer on server after each deploy
└── logs/                 ← excluded from rsync, writable by www-data
```

## Deployment Flow

Triggered on every push to `main`:

1. GitHub Actions checks out the repo on the runner
2. Builds Vue SPA: `cd client && npm ci && npm run build` → outputs to `webroot/spa/`
3. rsyncs project to `/var/www/triggertime/` on the Droplet, excluding:
   - `vendor/`
   - `node_modules/`
   - `logs/`
   - `config/app_local.php`
4. SSHs in and runs:
   - `composer install --no-dev --optimize-autoloader`
   - `bin/cake migrations migrate`
   - `sudo systemctl reload php8.4-fpm`

### GitHub Secrets Required

| Secret | Purpose |
|---|---|
| `DO_SSH_HOST` | Droplet IP address |
| `DO_SSH_USER` | Deploy user (e.g. `deploy`) |
| `DO_SSH_KEY` | Private SSH key for the deploy user |

## nginx Configuration

Standard CakePHP setup:
- Serve static files directly from `webroot/`
- Pass all other requests to `index.php` via PHP-FPM
- Vue SPA routing is handled by CakePHP's catch-all route (`PagesController::spa`), which returns `webroot/spa/index.html`
- HTTPS redirect from port 80

## Production Configuration

`config/app_local.php` is created once manually on the server (not in git, excluded from rsync deploys). It sets:

| Key | Value |
|---|---|
| `debug` | `false` |
| `Security.salt` | unique 64-char random hex string |
| `Datasources.default.url` | postgres connection string |
| `Stripe.publishable_key` | `pk_live_...` |
| `Stripe.secret_key` | `sk_live_...` |
| `Stripe.webhook_secret` | `whsec_...` |
| `APP_FULL_BASE_URL` | `https://yourdomain.com` |
| `ApiKeys.verify_signature` | `true` |

## Permissions

- `deploy` user owns `/var/www/triggertime/`
- PHP-FPM runs as `www-data` with read access to the app
- `logs/` is writable by `www-data`
- `deploy` user can `sudo systemctl reload php8.4-fpm` without password

## One-Time Server Setup Steps

1. Create a non-root `deploy` user with sudo rights; add SSH public key
2. Install packages: `nginx`, `php8.4-fpm` + extensions (`pdo_pgsql`, `mbstring`, `intl`, `xml`, `curl`, `zip`), `postgresql-16`, `composer`, `certbot`
3. PostgreSQL: create database and app user
4. nginx: configure vhost pointing to `webroot/`, with PHP-FPM passthrough and HTTPS redirect
5. Certbot: obtain Let's Encrypt cert (`certbot --nginx -d yourdomain.com`)
6. App directory: create `/var/www/triggertime/`, set ownership to `deploy`, create `logs/` owned by `www-data`
7. Production config: create `config/app_local.php` with all secrets
8. First deploy: trigger GitHub Actions workflow manually

## What Is NOT in Scope

- Email sending (configure `EMAIL_TRANSPORT_DEFAULT_URL` separately when needed)
- Redis/cache backend (file cache is fine at this scale)
- Log aggregation / monitoring (can be added later)
- Staging environment
