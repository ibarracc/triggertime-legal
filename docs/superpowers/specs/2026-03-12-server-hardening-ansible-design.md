# Server Hardening & Deployment Automation — Ansible Design Spec

**Date:** 2026-03-12
**Status:** Draft
**Stack:** Ubuntu 24.04 LTS, Nginx, PHP 8.4, CakePHP 5.3, Vue 3, PostgreSQL, Cloudflare (Free plan)
**Target:** DigitalOcean droplet (`triggertime.es`)

---

## 1. Goal

Create an Ansible project that:

1. **Hardens a fresh or existing DigitalOcean droplet** by applying the recommendations from the Server Security Hardening Guide (SSH, firewall, Nginx, PostgreSQL, monitoring, Cloudflare API settings).
2. **Deploys the application** (replaces the current raw rsync GitHub Actions workflow with an Ansible deploy role).
3. **Supports multiple environments** — adding a new environment (staging, QA, etc.) requires only new inventory/vars files and a single `harden.yml` run.

---

## 2. Project Structure

```
ansible/
├── ansible.cfg                    # Ansible config (inventory path, vault, SSH settings)
├── requirements.yml               # Ansible Galaxy dependencies (posix, community.general)
├── inventory/
│   ├── production.yml             # Droplet IP, SSH user, connection vars
│   └── staging.yml                # (template for future environments)
├── group_vars/
│   ├── all.yml                    # Shared defaults across all environments
│   ├── production.yml             # Production-specific: domain, DB, branch, paths
│   └── staging.yml                # (template for future environments)
├── vault/
│   ├── production.yml             # Encrypted: CF API token, DB passwords, Mailgun SMTP creds
│   └── staging.yml                # (template for future environments)
├── harden.yml                     # Playbook: run once per new server
├── deploy.yml                     # Playbook: run on every code push
├── roles/
│   ├── ssh/
│   │   ├── tasks/main.yml
│   │   ├── handlers/main.yml
│   │   └── templates/
│   │       └── sshd_config.j2
│   ├── firewall/
│   │   ├── tasks/main.yml
│   │   ├── handlers/main.yml
│   │   ├── templates/
│   │   │   └── update-cloudflare-ips.sh.j2
│   │   └── files/
│   ├── unattended-upgrades/
│   │   └── tasks/main.yml
│   ├── certbot/
│   │   ├── tasks/main.yml
│   │   └── templates/
│   │       └── cloudflare-credentials.ini.j2
│   ├── nginx/
│   │   ├── tasks/main.yml
│   │   ├── handlers/main.yml
│   │   └── templates/
│   │       ├── security-headers.conf.j2
│   │       ├── rate-limiting.conf.j2
│   │       └── ssl-params.conf.j2
│   ├── postgres/
│   │   ├── tasks/main.yml
│   │   ├── handlers/main.yml
│   │   └── templates/
│   │       └── pg_hba.conf.j2
│   ├── backups/
│   │   ├── tasks/main.yml
│   │   └── templates/
│   │       └── pg-backup.sh.j2
│   ├── monitoring/
│   │   ├── tasks/main.yml
│   │   └── templates/
│   │       ├── msmtprc.j2
│   │       ├── logwatch.conf.j2
│   │       └── ssh-alert.sh.j2
│   ├── cloudflare/
│   │   └── tasks/main.yml
│   └── deploy/
│       └── tasks/main.yml
└── templates/                     # Shared templates (if needed)
```

---

## 3. Inventory & Configuration

### `ansible.cfg`

```ini
[defaults]
inventory = inventory/
vault_password_file =
remote_tmp = /tmp/.ansible-${USER}
pipelining = true
host_key_checking = false  # Acceptable: traffic is over SSH with key auth; use ssh-keyscan for known_hosts in CI

[ssh_connection]
ssh_args = -o ControlMaster=auto -o ControlPersist=60s
```

### `inventory/production.yml`

```yaml
all:
  hosts:
    triggertime-prod:
      ansible_host: "<DROPLET_IP>"
      ansible_port: 2222
      ansible_user: deploy
      ansible_ssh_private_key_file: ~/.ssh/do_deploy_key
      ansible_python_interpreter: /usr/bin/python3
  vars:
    env_name: production
```

> **First run note:** Override port with `-e "ansible_port=22"` since sshd hasn't been reconfigured yet.

---

## 4. Variables

### `group_vars/all.yml` — Shared defaults

```yaml
ssh_port: 2222
ssh_user: deploy
fail2ban_maxretry: 3
fail2ban_bantime: 3600
fail2ban_findtime: 600
backup_retention_days: 30
backup_time: "02:00"
cloudflare_ip_update_cron: "0 3 * * 1"  # Weekly Monday 3AM
php_version: "8.4"
```

### `group_vars/production.yml` — Per-environment

```yaml
domain: triggertime.es
deploy_path: /var/www/triggertime
deploy_user: deploy
deploy_rsync_delete: true      # Remove stale files on server not in source
db_name: triggertime
db_app_user: cakeapp
db_backup_user: cakeapp_backup # Read-only user for pg_dump
db_migration_user: cakeapp_migrate
alert_email: support@triggertime.es
nginx_rate_limit_api: "10r/s"
nginx_rate_limit_api_burst: 20
nginx_rate_limit_login: "3r/m"
nginx_rate_limit_login_burst: 5
certbot_email: support@triggertime.es
```

> **Note on `deploy_branch`:** This variable is intentionally omitted. The deployed branch is determined by the GitHub Actions trigger (`on.push.branches`), not by Ansible. The deploy role syncs whatever code the CI runner checks out. The current production branch is `main` (the existing `master` branch deploy trigger will be updated to `main` as part of the GitHub Actions changes).

### `vault/production.yml` — Encrypted secrets

```yaml
vault_cloudflare_api_token: "<token>"
vault_db_app_password: "<generated>"
vault_db_backup_password: "<generated>"
vault_db_migration_password: "<generated>"
vault_mailgun_smtp_host: "smtp.mailgun.org"
vault_mailgun_smtp_port: 587
vault_mailgun_smtp_user: "<postmaster@mg.triggertime.es>"
vault_mailgun_smtp_password: "<password>"
```

---

## 5. Playbooks

### `harden.yml` — Run once per new server

```yaml
- name: Harden server
  hosts: all
  become: true
  roles:
    - ssh
    - firewall
    - unattended-upgrades
    - certbot
    - nginx
    - postgres
    - backups
    - monitoring
    - cloudflare
```

**Execution order matters:**
1. `ssh` runs first — changes port, but adds UFW rule for new port BEFORE modifying sshd
2. `firewall` — enables UFW after all allow rules are in place
3. `certbot` before `nginx` — certs must exist before Nginx SSL config references them
4. `cloudflare` runs last — server must be ready before setting Full (Strict) SSL

### `deploy.yml` — Run on every push

```yaml
- name: Deploy application
  hosts: all
  become: true
  roles:
    - deploy
```

---

## 6. Role Specifications

### 6.1`ssh` Role

**Tasks:**
1. Ensure `deploy` user exists with sudo access (idempotent — skip if exists)
2. Ensure SSH authorized_keys are in place for `deploy` user
3. Deploy templated `sshd_config` with:
   - `Port {{ ssh_port }}`
   - `PermitRootLogin no`
   - `PasswordAuthentication no`
   - `PubkeyAuthentication yes`
   - `ChallengeResponseAuthentication no`
   - `UsePAM yes`
4. Add UFW allow rule for new SSH port BEFORE restarting sshd
5. Install `fail2ban`
6. Deploy Fail2Ban jail config:
   - `[sshd]` enabled, port={{ ssh_port }}, maxretry={{ fail2ban_maxretry }}, bantime={{ fail2ban_bantime }}, findtime={{ fail2ban_findtime }}
7. Enable and start fail2ban

**Handlers:**
- Restart sshd
- Restart fail2ban

**Safety:**
- The role allows BOTH port 22 and {{ ssh_port }} in UFW during the transition. Port 22 rule is removed only after verifying sshd is listening on the new port.
- Ansible's `ansible_port` is updated dynamically after the switch via `set_fact`.

### 6.2 `firewall` Role

**Tasks:**
1. Install UFW
2. Set default deny incoming, allow outgoing
3. Allow SSH on `{{ ssh_port }}/tcp`
4. Deploy `/usr/local/bin/update-cloudflare-ips.sh` (Jinja2 template):
   - Fetches Cloudflare IPv4 and IPv6 ranges
   - Removes old Cloudflare UFW rules
   - Adds new rules allowing ports 80,443 from Cloudflare IPs only
   - **Also regenerates** `/etc/nginx/snippets/cloudflare-realip.conf` with `set_real_ip_from` directives for each IP range (used by Nginx to restore real client IPs)
   - Reloads UFW and Nginx
5. Run the script immediately
6. Add weekly cron job for the script (`{{ cloudflare_ip_update_cron }}`)
7. Enable UFW (with `--force` to avoid interactive prompt)

**Idempotency:** The Cloudflare IP script is designed to remove-then-add, safe to re-run.

### 6.3 `unattended-upgrades` Role

**Tasks:**
1. Install `unattended-upgrades`
2. Enable via `dpkg-reconfigure` (non-interactive)
3. Ensure security updates origin pattern is active

### 6.4 `certbot` Role

**Tasks:**
1. Install `certbot` and `python3-certbot-dns-cloudflare`
2. Deploy Cloudflare credentials file to `/etc/letsencrypt/cloudflare.ini` (mode 0600):
   ```ini
   dns_cloudflare_api_token = {{ vault_cloudflare_api_token }}
   ```
3. Obtain certificate (if not already present):
   ```
   certbot certonly --dns-cloudflare \
     --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini \
     -d {{ domain }} -d www.{{ domain }} \
     --email {{ certbot_email }} \
     --agree-tos --non-interactive
   ```
4. Ensure auto-renewal timer is active (`systemctl enable certbot.timer`)
5. Deploy post-renewal hook to reload Nginx: `/etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh`

**Idempotency:** Certbot skips if cert already exists and is valid.

### 6.5 `nginx` Role

**Tasks:**
1. Set `server_tokens off` in `/etc/nginx/nginx.conf` (lineinfile)
2. Deploy `/etc/nginx/snippets/cloudflare-realip.conf` — `set_real_ip_from` directives for all Cloudflare IP ranges + `real_ip_header CF-Connecting-IP`. This ensures `$remote_addr` reflects the actual client IP, not the Cloudflare edge IP. The Cloudflare IP update script (firewall role) regenerates this file alongside UFW rules.
3. Deploy `/etc/nginx/snippets/security-headers.conf`:
   - `X-Content-Type-Options: nosniff`
   - `X-Frame-Options: SAMEORIGIN`
   - `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`
   - `Referrer-Policy: strict-origin-when-cross-origin`
   - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
   - `Content-Security-Policy: default-src 'self'; script-src 'self' https://js.stripe.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https://{{ domain }} https://api.stripe.com; font-src 'self'; frame-src https://js.stripe.com; frame-ancestors 'none';`
   - *(Note: `X-XSS-Protection` omitted — deprecated, CSP is the modern replacement)*
4. Deploy `/etc/nginx/snippets/rate-limiting.conf`:
   - `limit_req_zone $remote_addr zone=api:10m rate={{ nginx_rate_limit_api }};`
   - `limit_req_zone $remote_addr zone=login:10m rate={{ nginx_rate_limit_login }};`
   - *(Uses `$remote_addr` which is the real client IP after `cloudflare-realip.conf` is loaded)*
5. Deploy `/etc/nginx/snippets/ssl-params.conf`:
   - `ssl_certificate /etc/letsencrypt/live/{{ domain }}/fullchain.pem;`
   - `ssl_certificate_key /etc/letsencrypt/live/{{ domain }}/privkey.pem;`
   - `ssl_protocols TLSv1.2 TLSv1.3;`
   - `ssl_prefer_server_ciphers on;`
6. **Template the full vhost config** (`/etc/nginx/sites-available/{{ domain }}.conf`):
   - HTTP server block: redirect to HTTPS
   - HTTPS server block with: `include` for all snippets above, `location /api/` with rate limiting, `location /api/v1/web/auth/login` with login rate limiting, PHP-FPM upstream, SPA fallback
   - Symlink to `sites-enabled/`
   - Remove default site if present
7. Validate Nginx config (`nginx -t`)
8. Reload Nginx

**Handlers:**
- Test and reload Nginx

**Note:** The vhost is fully templated (not patched with `lineinfile`), making it reproducible and safe to re-run. All environment-specific values come from variables.

### 6.6 `postgres` Role

**Tasks:**
1. Ensure `listen_addresses = 'localhost'` in `postgresql.conf`
2. Create dedicated application user `{{ db_app_user }}` with password from vault:
   - GRANT SELECT, INSERT, UPDATE, DELETE on all tables in `{{ db_name }}`
   - GRANT USAGE, SELECT on all sequences (needed for INSERTs with serial/identity columns)
   - NO CREATE, DROP, ALTER, GRANT privileges
3. Create backup user `{{ db_backup_user }}` with read-only access:
   - GRANT `pg_read_all_data` role (allows full schema+data dumps without ownership)
4. Create migration user `{{ db_migration_user }}` with broader permissions:
   - Full privileges on `{{ db_name }}` (used only for `bin/cake migrations migrate`)
5. Harden `pg_hba.conf`:
   - Local connections: `scram-sha-256` (no `trust`)
   - Reject all remote connections
6. Set default privileges so future tables also grant to `{{ db_app_user }}`

**Handlers:**
- Restart PostgreSQL

**Note:** The deploy role uses `{{ db_migration_user }}` for migrations; the CakePHP app runtime uses `{{ db_app_user }}` (configured in `config/app_local.php` on the server).

### 6.7 `backups` Role

**Tasks:**
1. Create backup directory `/var/backups/postgresql/`
2. Deploy `/usr/local/bin/pg-backup.sh`:
   ```bash
   #!/bin/bash
   set -euo pipefail

   BACKUP_DIR=/var/backups/postgresql
   TIMESTAMP=$(date +%Y%m%d_%H%M%S)
   DB_NAME={{ db_name }}
   DUMP_FILE="$BACKUP_DIR/${DB_NAME}_${TIMESTAMP}.sql.gz"

   if ! pg_dump -U {{ db_backup_user }} "$DB_NAME" | gzip > "$DUMP_FILE"; then
     echo "pg_dump failed for $DB_NAME at $(date)" \
       | mail -s "BACKUP FAILED: $(hostname)" {{ alert_email }}
     rm -f "$DUMP_FILE"
     exit 1
   fi

   # Retain last {{ backup_retention_days }} days
   find "$BACKUP_DIR" -name '*.sql.gz' -mtime +{{ backup_retention_days }} -delete
   ```
3. Create `.pgpass` file for the backup user (mode 0600) so cron runs without password prompt
4. Add daily cron job at `{{ backup_time }}`
5. Verify first backup runs successfully (run script once as a check)

**Complements daily droplet snapshots** — this provides fast logical DB restore without needing a full snapshot restore.

### 6.8 `monitoring` Role

**Tasks:**
1. Install `msmtp`, `msmtp-mta`, and `mailutils` (provides the `mail` command used by backup alerts and SSH alerts)
2. Deploy `/etc/msmtprc` from vault variables:
   ```
   account default
   host {{ vault_mailgun_smtp_host }}
   port {{ vault_mailgun_smtp_port }}
   auth on
   user {{ vault_mailgun_smtp_user }}
   password {{ vault_mailgun_smtp_password }}
   tls on
   tls_starttls on
   tls_certcheck on
   tls_trust_file /etc/ssl/certs/ca-certificates.crt
   from {{ alert_email }}
   ```
3. Set permissions on `/etc/msmtprc` to 0600
4. Install `logwatch`
5. Deploy `/etc/logwatch/conf/logwatch.conf`:
   - `MailTo = {{ alert_email }}`
   - `Detail = Med`
   - `Range = yesterday`
   - `MailFrom = logwatch@{{ domain }}`
6. Deploy `/etc/profile.d/ssh-alert.sh`:
   ```bash
   if [ -n "$SSH_CLIENT" ]; then
     # Skip alerts for automated deploy user (Ansible/CI sessions)
     if [ "$(whoami)" != "{{ deploy_user }}" ]; then
       IP=$(echo $SSH_CLIENT | awk '{print $1}')
       echo "SSH login: $(whoami) from $IP at $(date)" \
         | mail -s "SSH Alert: $(hostname)" {{ alert_email }}
     fi
   fi
   ```
7. Send a test email to verify SMTP works

### 6.9 `cloudflare` Role

**Tasks (all via Cloudflare API v4 using `uri` module):**

1. Look up zone ID for `{{ domain }}`
2. Set SSL/TLS mode to `full` (strict)
3. Set Always Use HTTPS to `on`
4. Set Minimum TLS Version to `1.2`
5. Enable HSTS: `max-age=31536000, includeSubDomains, preload`
6. Enable Bot Fight Mode
7. Set Browser Cache TTL to `14400` (4 hours)
8. Create **Cache Rule** (not Page Rule — Page Rules are deprecated): `*{{ domain }}/api/*` → Cache Level: Bypass (if not already present)

**Authentication:** Bearer token via `vault_cloudflare_api_token`.

**Idempotency:** All API calls are PUT/PATCH (setting values), safe to re-run. Cache Rule creation checks for existing rule first.

**Free plan limitations handled:**
- WAF Managed Rulesets: **skipped** (requires Pro)
- Rate Limiting: **skipped** in Cloudflare (handled by Nginx instead) — Free plan only allows 1 rule which isn't worth the complexity
- Uses Cache Rules API (modern replacement for deprecated Page Rules)

### 6.10 `deploy` Role

**Tasks:**
1. Sync application code using `ansible.posix.synchronize`:
   - Source: project root
   - Destination: `{{ deploy_path }}`
   - `delete: {{ deploy_rsync_delete }}` — removes stale files on server not in source
   - Excludes: `.git/`, `vendor/`, `node_modules/`, `client/`, `logs/`, `tmp/`, `docs/`, `tests/`, `config/app_local.php`, `config/.env`, `ansible/`, `webroot/uploads/` (runtime-generated files)
   - SSH key wiring: set `ansible_ssh_private_key_file` in inventory; the `synchronize` module inherits it automatically. For CI, explicitly pass via `rsync_opts: ["-e", "ssh -i ~/.ssh/deploy_key -p {{ ssh_port }}"]`
2. Run `composer install --no-dev --optimize-autoloader --no-interaction`
3. Create required directories (`tmp/cache/models`, `tmp/cache/persistent`, `tmp/cache/views`, `tmp/sessions`, `logs/`) with proper ownership (`{{ deploy_user }}:www-data`) and permissions (775)
4. Run migrations using `DATABASE_URL` environment variable override:
   ```bash
   DATABASE_URL="postgres://{{ db_migration_user }}:{{ vault_db_migration_password }}@localhost/{{ db_name }}" \
     bin/cake migrations migrate --no-lock
   ```
   This allows the migration to run with elevated privileges without modifying `config/app_local.php`. The app runtime continues using `{{ db_app_user }}` from `app_local.php`.
5. Run `bin/cake cache clear_all`
6. Run `bin/cake schema_cache build` (uses `db_app_user` from `app_local.php` — only needs SELECT)
7. Reload PHP-FPM: `systemctl reload php{{ php_version }}-fpm`

**Rollback strategy:** No automated rollback is implemented (same as current deploy). For critical failures: restore from the most recent DigitalOcean droplet snapshot, or `git revert` and re-deploy. Consider implementing symlink-based releases (`current/previous/shared` pattern) as a future enhancement.

---

## 7. GitHub Actions Integration

### Updated `.github/workflows/deploy.yml`

```yaml
name: Deploy
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Install Ansible
        run: |
          pip install ansible
          ansible-galaxy install -r ansible/requirements.yml

      - name: Write SSH key
        run: |
          mkdir -p ~/.ssh
          echo "${{ secrets.DO_SSH_KEY }}" > ~/.ssh/deploy_key
          chmod 600 ~/.ssh/deploy_key
          ssh-keyscan -p ${{ secrets.DO_SSH_PORT }} ${{ secrets.DO_SSH_HOST }} >> ~/.ssh/known_hosts

      - name: Write vault password
        run: echo "${{ secrets.ANSIBLE_VAULT_PASSWORD }}" > .vault_pass

      - name: Deploy
        run: |
          ansible-playbook -i ansible/inventory/production.yml \
            ansible/deploy.yml \
            --vault-password-file .vault_pass \
            -e "ansible_ssh_private_key_file=~/.ssh/deploy_key"

      - name: Cleanup
        if: always()
        run: rm -f ~/.ssh/deploy_key .vault_pass
```

### New GitHub Secrets needed:

| Secret | Value |
|--------|-------|
| `DO_SSH_HOST` | Droplet IP (already exists) |
| `DO_SSH_PORT` | `2222` (new — after hardening) |
| `DO_SSH_KEY` | SSH private key (already exists) |
| `ANSIBLE_VAULT_PASSWORD` | Password used to encrypt vault files (new) |

**Note:** `DO_SSH_USER` is no longer needed as a secret — it's defined in the Ansible inventory.

---

## 8. Running the Playbooks

### Initial setup (local machine):

```bash
# Install Ansible
brew install ansible  # macOS

# Install dependencies
cd ansible/
ansible-galaxy install -r requirements.yml

# Create vault (enter Mailgun, CF token, DB passwords)
ansible-vault create vault/production.yml

# First run — harden the server
# IMPORTANT: First run uses port 22 (before SSH port change)
ansible-playbook -i inventory/production.yml harden.yml \
  --ask-vault-pass \
  -e "ansible_port=22"

# Subsequent runs use port 2222 automatically (from inventory)
ansible-playbook -i inventory/production.yml harden.yml \
  --ask-vault-pass
```

### Adding a new environment:

1. Create `inventory/staging.yml` with new droplet IP
2. Create `group_vars/staging.yml` overriding domain, db_name, deploy_path
3. Create `vault/staging.yml` with environment-specific secrets
4. Run: `ansible-playbook -i inventory/staging.yml harden.yml --ask-vault-pass -e "ansible_port=22"`
5. Add GitHub Actions workflow for staging branch

---

## 9. Cloudflare Manual Checklist

Items that **cannot** be automated on the Free plan or require human judgment:

- [ ] **Generate new Cloudflare API token** (the one shared in chat should be revoked)
- [ ] **Monitor WAF/firewall events** for the first week after hardening — check for false positives
- [ ] **Upgrade to Pro** (optional) to enable WAF Managed Rulesets (OWASP Core Rules) and additional rate limiting rules
- [ ] **Enable Authenticated Origin Pulls** in Cloudflare dashboard (SSL/TLS > Origin Server) for extra origin validation — this requires downloading Cloudflare's CA cert and configuring Nginx to verify client certs
- [ ] **DigitalOcean monitoring:** Enable monitoring agent from droplet dashboard, set alerts for CPU > 80%, Memory > 90%, Disk > 85%

---

## 10. Application Code Changes (Out of Scope — Separate PRs)

These items from the security guide are **application-level changes**, not server hardening. They should be addressed in separate work:

| Item | Current State | Recommendation |
|------|--------------|----------------|
| JWT in localStorage | `tt_token` in localStorage | Consider httpOnly cookie (significant refactor) |
| CORS middleware | Not configured | Add explicit CORS with `origin: ['https://triggertime.es']` |
| Debug mode | Env-var controlled, defaults false | Verify production env has `DEBUG=false` |
| Session cookie settings | PHP defaults | Add httpOnly, secure, SameSite=Strict in `config/app.php` |
| Source maps | Not explicitly disabled | Add `sourcemap: false` to `client/vite.config.js` build config |
| v-html audit | Unknown | Audit Vue components for XSS-prone `v-html` usage |
| npm audit | Not in CI | Add `npm audit` step to CI workflow |
| JWT expiration | 30 days | Consider shorter expiration + refresh token |
| Fallback JWT secret | Hardcoded in JwtService.php | Remove fallback, fail loudly if salt not configured |

---

## 11. Ansible Galaxy Dependencies

`requirements.yml`:

```yaml
collections:
  - name: ansible.posix        # synchronize module (rsync)
  - name: community.general    # ufw module, mail module
  - name: community.postgresql # PostgreSQL user/db management
```

---

## 12. Risks and Mitigations

| Risk | Mitigation |
|------|-----------|
| SSH port change locks out deploy | UFW allows both ports during transition; Ansible updates its own connection port dynamically |
| Certbot fails to obtain cert | Role checks for existing cert before requesting; DNS propagation may take a few minutes |
| UFW blocks legitimate traffic | Cloudflare IP script runs before UFW enable; SSH port always allowed |
| GitHub Actions deploy fails after port change | `DO_SSH_PORT` secret must be updated to 2222 before next deploy |
| Nginx config breaks site | `nginx -t` validation before any reload; handlers only reload on successful test |
| PostgreSQL user change breaks app | Migration user retains full privileges; app user change requires updating `config/app_local.php` on server |
| Vault password lost | Store vault password in a secure password manager; also stored as GitHub Secret |
