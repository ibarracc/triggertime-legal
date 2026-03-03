# DigitalOcean Deployment Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deploy TriggerTime Site to a single DigitalOcean Droplet with automated GitHub Actions deployments on every push to `main`.

**Architecture:** nginx + PHP 8.4-FPM + PostgreSQL 16 on Ubuntu 24.04. GitHub Actions builds the Vue SPA on the CI runner and rsyncs it along with the PHP app to the Droplet. Production secrets live in `config/app_local.php` on the server only (never in git).

**Tech Stack:** Ubuntu 24.04, nginx, PHP 8.4-FPM, PostgreSQL 16, Certbot, GitHub Actions, rsync

---

## Phase 1: Repo Files

### Task 1: Create production `app_local.php` template

**Files:**
- Create: `config/app_local.php.production.example`

**Step 1: Create the template file**

This is a reference file that gets filled in manually on the server. It is committed to git as documentation only.

```php
<?php

use function Cake\Core\env;

/*
 * Production configuration for TriggerTime.
 * Copy this to config/app_local.php on the server and fill in all values.
 * NEVER commit the real app_local.php to git.
 *
 * Generate SECURITY_SALT with: openssl rand -hex 32
 */

return [
    'debug' => false,

    'Security' => [
        'salt' => 'REPLACE_WITH_OUTPUT_OF_openssl_rand_-hex_32',
    ],

    'ApiKeys' => [
        'verify_signature' => true,
        'keys' => [
            'tt_live_a8f3k2m9xQ7bR4cN' => [
                'app_instance' => 'com.ibarracc.triggertime',
                'secret' => 'REPLACE_WITH_REAL_HMAC_SECRET_FOR_TRIGGERTIME',
            ],
            'tt_live_pL5wE8jH2sK9dF1v' => [
                'app_instance' => 'com.ibarracc.ctz',
                'secret' => 'REPLACE_WITH_REAL_HMAC_SECRET_FOR_CTZ',
            ],
        ],
    ],

    'Datasources' => [
        'default' => [
            'url' => 'postgres://ttapp:REPLACE_DB_PASSWORD@localhost:5432/triggertime',
        ],
        'test' => [
            'url' => env('DATABASE_TEST_URL', 'sqlite://127.0.0.1/tmp/tests.sqlite'),
        ],
    ],

    'EmailTransport' => [
        'default' => [
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],

    'Stripe' => [
        'publishable_key' => 'REPLACE_WITH_pk_live_...',
        'secret_key' => 'REPLACE_WITH_sk_live_...',
        'webhook_secret' => 'REPLACE_WITH_whsec_...',
    ],

    'Subscriptions' => [
        'free' => ['max_devices_allowed' => 999],
        'pro' => ['max_devices_allowed' => 999],
    ],
];
```

**Step 2: Commit**

```bash
git add config/app_local.php.production.example
git commit -m "chore: add production app_local.php template"
```

---

### Task 2: Create nginx vhost configuration

**Files:**
- Create: `config/nginx/triggertime.conf`

**Step 1: Create the config file**

Replace `REPLACE_DOMAIN` with your actual domain before deploying to the server.

```nginx
server {
    listen 80;
    server_name REPLACE_DOMAIN www.REPLACE_DOMAIN;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name REPLACE_DOMAIN www.REPLACE_DOMAIN;

    root /var/www/triggertime/webroot;
    index index.php;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml image/svg+xml;

    # Long-lived cache for versioned static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Main routing: serve static files or hand off to CakePHP
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
    }

    # Deny access to hidden files and sensitive directories
    location ~ /\. {
        deny all;
    }

    location ~ ^/(src|tests|vendor|config|bin)/ {
        deny all;
    }

    # SSL — Certbot will add these lines automatically when you run certbot --nginx
    # ssl_certificate /etc/letsencrypt/live/REPLACE_DOMAIN/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/REPLACE_DOMAIN/privkey.pem;
}
```

**Step 2: Commit**

```bash
git add config/nginx/triggertime.conf
git commit -m "chore: add nginx vhost configuration"
```

---

### Task 3: Create GitHub Actions deploy workflow

**Files:**
- Create: `.github/workflows/deploy.yml`

**Step 1: Create the workflow**

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Set up Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: 'npm'
          cache-dependency-path: client/package-lock.json

      - name: Build Vue SPA
        run: |
          cd client
          npm ci
          npm run build

      - name: Set up SSH key
        run: |
          mkdir -p ~/.ssh
          echo "${{ secrets.DO_SSH_KEY }}" > ~/.ssh/deploy_key
          chmod 600 ~/.ssh/deploy_key
          ssh-keyscan -H "${{ secrets.DO_SSH_HOST }}" >> ~/.ssh/known_hosts

      - name: Deploy files via rsync
        run: |
          rsync -avz --delete \
            --exclude='.git/' \
            --exclude='vendor/' \
            --exclude='node_modules/' \
            --exclude='client/node_modules/' \
            --exclude='client/' \
            --exclude='logs/' \
            --exclude='tmp/' \
            --exclude='config/app_local.php' \
            --exclude='config/.env' \
            --exclude='.ddev/' \
            --exclude='docs/' \
            --exclude='tests/' \
            -e "ssh -i ~/.ssh/deploy_key" \
            ./ ${{ secrets.DO_SSH_USER }}@${{ secrets.DO_SSH_HOST }}:/var/www/triggertime/

      - name: Post-deploy tasks
        run: |
          ssh -i ~/.ssh/deploy_key ${{ secrets.DO_SSH_USER }}@${{ secrets.DO_SSH_HOST }} << 'ENDSSH'
            set -e
            cd /var/www/triggertime
            composer install --no-dev --optimize-autoloader --no-interaction
            bin/cake migrations migrate --no-lock
            sudo systemctl reload php8.4-fpm
            echo "Deployment complete."
          ENDSSH
```

Note: `client/` is excluded from rsync because the built SPA output (`webroot/spa/`) is already included — the raw source is not needed on the server.

**Step 2: Commit**

```bash
git add .github/workflows/deploy.yml
git commit -m "feat: add GitHub Actions production deploy workflow"
```

---

## Phase 2: Server Provisioning (manual, run once)

These steps are done via SSH. There is no automated testing for infrastructure setup — verify each step by checking the expected output.

### Task 4: Create Droplet on DigitalOcean

1. Log in to [cloud.digitalocean.com](https://cloud.digitalocean.com) → **Create → Droplets**
2. Settings:
   - **Region:** closest to your users
   - **OS:** Ubuntu 24.04 LTS x64
   - **Size:** Basic → Regular → 2 GB RAM / 1 vCPU ($12/mo)
   - **Authentication:** SSH Key — add your local public key (`cat ~/.ssh/id_ed25519.pub`)
   - **Hostname:** `triggertime-prod`
3. Click **Create Droplet** and note the IP address

**Verify:** SSH into the Droplet as root:
```bash
ssh root@YOUR_DROPLET_IP
```
Expected: successful shell prompt.

---

### Task 5: Initial system setup

Run as root on the Droplet.

**Step 1: Create `deploy` user**

```bash
adduser deploy
usermod -aG sudo deploy
mkdir -p /home/deploy/.ssh
cp ~/.ssh/authorized_keys /home/deploy/.ssh/
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
```

Verify you can SSH as the deploy user from your local machine:
```bash
ssh deploy@YOUR_DROPLET_IP
```

**Step 2: Add PHP 8.4 repository**

Ubuntu 24.04 ships with PHP 8.3. Add the Ondřej Surý PPA for PHP 8.4:
```bash
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

**Step 3: Install all required packages**

```bash
sudo apt install -y \
  nginx \
  php8.4-fpm \
  php8.4-pgsql \
  php8.4-mbstring \
  php8.4-intl \
  php8.4-xml \
  php8.4-curl \
  php8.4-zip \
  postgresql \
  certbot \
  python3-certbot-nginx \
  unzip
```

**Step 4: Install Composer**

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

Verify: `composer --version` → should print Composer 2.x.

---

### Task 6: Configure PostgreSQL

**Step 1: Create database and user**

```bash
sudo -u postgres psql
```

In the psql prompt:
```sql
CREATE USER ttapp WITH PASSWORD 'REPLACE_WITH_STRONG_PASSWORD';
CREATE DATABASE triggertime OWNER ttapp;
GRANT ALL PRIVILEGES ON DATABASE triggertime TO ttapp;
\q
```

**Step 2: Verify connection**

```bash
psql -U ttapp -d triggertime -h localhost
```

If prompted for password, enter the one you set above. Expected: psql prompt. Type `\q` to exit.

---

### Task 7: Set up app directory and permissions

```bash
# App directory owned by deploy user
sudo mkdir -p /var/www/triggertime
sudo chown deploy:deploy /var/www/triggertime

# CakePHP writable directories owned by www-data (PHP-FPM user)
sudo mkdir -p /var/www/triggertime/logs
sudo mkdir -p /var/www/triggertime/tmp/cache/models
sudo mkdir -p /var/www/triggertime/tmp/cache/persistent
sudo mkdir -p /var/www/triggertime/tmp/cache/views
sudo mkdir -p /var/www/triggertime/tmp/sessions
sudo chown -R www-data:www-data /var/www/triggertime/logs
sudo chown -R www-data:www-data /var/www/triggertime/tmp
```

---

### Task 8: Allow deploy user to reload PHP-FPM without a password

This is needed by the GitHub Actions post-deploy step.

```bash
sudo visudo
```

Add this line at the bottom of the file:
```
deploy ALL=(ALL) NOPASSWD: /bin/systemctl reload php8.4-fpm
```

Save and exit. Verify with:
```bash
sudo systemctl reload php8.4-fpm
```
Expected: no password prompt, no error.

---

### Task 9: Configure nginx

**Step 1: Copy the vhost config**

At this point the repo files aren't on the server yet, so create the nginx config manually:

```bash
sudo nano /etc/nginx/sites-available/triggertime
```

Paste the contents of `config/nginx/triggertime.conf` (from the repo) and replace `REPLACE_DOMAIN` with your actual domain (e.g. `triggertime.app`).

**Step 2: Enable the site**

```bash
sudo ln -s /etc/nginx/sites-available/triggertime /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
```

Expected: `nginx: configuration file /etc/nginx/nginx.conf test is successful`

```bash
sudo systemctl reload nginx
```

---

### Task 10: Set up SSL with Certbot

Your domain's DNS A record must point to the Droplet IP before running this.

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

Follow the prompts. Certbot will automatically modify the nginx config to add the SSL block.

**Verify auto-renewal works:**
```bash
sudo certbot renew --dry-run
```

Expected: `Congratulations, all simulated renewals succeeded.`

---

### Task 11: Create production `app_local.php` on the server

This file must exist **before** the first deploy (the deploy workflow excludes it from rsync, so it will never be overwritten).

```bash
nano /var/www/triggertime/config/app_local.php
```

Use `config/app_local.php.production.example` from the repo as your template. Fill in all `REPLACE_*` values:

| Field | How to get the value |
|---|---|
| `Security.salt` | Run `openssl rand -hex 32` on the server |
| `Datasources.default.url` | Use the password from Task 6 |
| `ApiKeys.keys[*].secret` | Your real HMAC secrets for mobile apps |
| `Stripe.*` | From the Stripe Dashboard → Developers → API keys (use live keys) |

Make the config readable only by its owner:
```bash
chmod 640 /var/www/triggertime/config/app_local.php
```

---

## Phase 3: GitHub Secrets & First Deploy

### Task 12: Add GitHub Actions secrets

In GitHub: repo page → **Settings → Secrets and variables → Actions → New repository secret**.

Add these three secrets:

| Name | Value |
|---|---|
| `DO_SSH_HOST` | Your Droplet IP address |
| `DO_SSH_USER` | `deploy` |
| `DO_SSH_KEY` | Your **private** SSH key (the one corresponding to the public key on the Droplet) |

To print your private key locally:
```bash
cat ~/.ssh/id_ed25519
```
Copy the entire output including the `-----BEGIN...` and `-----END...` lines.

---

### Task 13: Trigger the first deployment

Push to `main` (or trigger manually in the GitHub UI: **Actions → Deploy to Production → Run workflow**):

```bash
git push origin main
```

Watch the **Actions** tab. All steps should be green. The deploy step takes ~1–2 minutes.

If a step fails, read the step output — common issues:
- **rsync fails:** SSH key or host is wrong → recheck secrets
- **composer install fails:** missing PHP extension → install it on the server
- **migrations fail:** DB connection issue → verify `app_local.php` DB URL

---

### Task 14: Smoke test

1. Visit `https://yourdomain.com` — should load the Vue SPA landing page
2. Visit `https://yourdomain.com/api/v1/` — should return a JSON response (404 or similar, not a PHP error)
3. Check nginx logs if anything is wrong:
   ```bash
   sudo tail -f /var/log/nginx/error.log
   ```
4. Check CakePHP logs:
   ```bash
   tail -f /var/www/triggertime/logs/error.log
   ```
