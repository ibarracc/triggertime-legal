# Server Hardening & Deployment Automation — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an Ansible project that hardens a DigitalOcean droplet (SSH, firewall, Nginx, PostgreSQL, backups, monitoring, Cloudflare API) and replaces the current raw rsync deploy with an Ansible-based deploy, supporting multiple environments.

**Architecture:** Ansible playbook with 10 roles split across two playbooks: `harden.yml` (run once per server) and `deploy.yml` (run on every push). All config is parameterized via group_vars/vault per environment. GitHub Actions runs Ansible instead of raw rsync/ssh.

**Tech Stack:** Ansible 2.16+, Ubuntu 24.04, Nginx, PHP 8.4-FPM, PostgreSQL, Certbot (DNS-01 via Cloudflare), UFW, Fail2Ban, msmtp, Logwatch, Cloudflare API v4.

**Spec:** `docs/superpowers/specs/2026-03-12-server-hardening-ansible-design.md`

---

## Chunk 1: Project Scaffolding & Configuration

### Task 1: Create Ansible project structure and configuration files

**Files:**
- Create: `ansible/ansible.cfg`
- Create: `ansible/requirements.yml`
- Create: `ansible/inventory/production.yml`
- Create: `ansible/group_vars/all.yml`
- Create: `ansible/group_vars/production.yml`
- Create: `ansible/harden.yml`
- Create: `ansible/deploy.yml`

- [ ] **Step 1: Create directory structure**

```bash
mkdir -p ansible/{inventory,group_vars,vault,roles}
```

- [ ] **Step 2: Create `ansible/ansible.cfg`**

```ini
[defaults]
inventory = inventory/
vault_password_file =
remote_tmp = /tmp/.ansible-${USER}
pipelining = true
host_key_checking = false

[ssh_connection]
ssh_args = -o ControlMaster=auto -o ControlPersist=60s
```

- [ ] **Step 3: Create `ansible/requirements.yml`**

```yaml
---
collections:
  - name: ansible.posix
  - name: community.general
  - name: community.postgresql
```

- [ ] **Step 4: Create `ansible/inventory/production.yml`**

Use `<DROPLET_IP>` as placeholder — the user will fill in the real IP.

```yaml
---
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

- [ ] **Step 5: Create `ansible/group_vars/all.yml`**

```yaml
---
ssh_port: 2222
ssh_user: deploy
fail2ban_maxretry: 3
fail2ban_bantime: 3600
fail2ban_findtime: 600
backup_retention_days: 30
backup_hour: "2"
backup_minute: "0"
cloudflare_ip_update_cron: "0 3 * * 1"
php_version: "8.4"
```

- [ ] **Step 6: Create `ansible/group_vars/production.yml`**

```yaml
---
domain: triggertime.es
deploy_path: /var/www/triggertime
deploy_user: deploy
deploy_rsync_delete: true
db_name: triggertime
db_app_user: cakeapp
db_backup_user: cakeapp_backup
db_migration_user: cakeapp_migrate
alert_email: support@triggertime.es
nginx_rate_limit_api: "10r/s"
nginx_rate_limit_api_burst: 20
nginx_rate_limit_login: "3r/m"
nginx_rate_limit_login_burst: 5
certbot_email: support@triggertime.es
```

- [ ] **Step 7: Create `ansible/harden.yml`**

```yaml
---
- name: Harden server
  hosts: all
  become: true
  vars_files:
    - "vault/{{ env_name }}.yml"
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

- [ ] **Step 8: Create `ansible/deploy.yml`**

```yaml
---
- name: Deploy application
  hosts: all
  become: true
  vars_files:
    - "vault/{{ env_name }}.yml"
  roles:
    - deploy
```

- [ ] **Step 9: Create vault template file (unencrypted example)**

Create `ansible/vault/production.yml.example` (NOT the real vault — that's created manually):

```yaml
---
# Copy this to production.yml and encrypt with: ansible-vault encrypt vault/production.yml
vault_cloudflare_api_token: "your-cloudflare-api-token"
vault_db_app_password: "generate-a-strong-password"
vault_db_backup_password: "generate-a-strong-password"
vault_db_migration_password: "generate-a-strong-password"
vault_mailgun_smtp_host: "smtp.mailgun.org"
vault_mailgun_smtp_port: 587
vault_mailgun_smtp_user: "postmaster@mg.triggertime.es"
vault_mailgun_smtp_password: "your-mailgun-smtp-password"
```

- [ ] **Step 10: Verify ansible-lint passes on scaffolding**

Run: `cd ansible && ansible-playbook harden.yml --syntax-check -e "ansible_port=22" 2>&1 || true`
Expected: Syntax check may warn about missing roles (not yet created) but should not have YAML errors.

- [ ] **Step 11: Commit**

```bash
git add ansible/
git commit -m "feat: scaffold Ansible project structure with config and inventory"
```

---

## Chunk 2: SSH Hardening Role

### Task 2: Create the SSH hardening role

**Files:**
- Create: `ansible/roles/ssh/tasks/main.yml`
- Create: `ansible/roles/ssh/handlers/main.yml`
- Create: `ansible/roles/ssh/templates/sshd_config.j2`
- Create: `ansible/roles/ssh/templates/jail.local.j2`

- [ ] **Step 1: Create role directory structure**

```bash
mkdir -p ansible/roles/ssh/{tasks,handlers,templates}
```

- [ ] **Step 2: Create `ansible/roles/ssh/templates/sshd_config.j2`**

```
# Managed by Ansible — do not edit manually
Port {{ ssh_port }}
AddressFamily any
ListenAddress 0.0.0.0
ListenAddress ::

PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
ChallengeResponseAuthentication no
UsePAM yes

X11Forwarding no
PrintMotd no
AcceptEnv LANG LC_*

Subsystem sftp /usr/lib/openssh/sftp-server
```

- [ ] **Step 3: Create `ansible/roles/ssh/templates/jail.local.j2`**

```
# Managed by Ansible — do not edit manually
[DEFAULT]
bantime = {{ fail2ban_bantime }}
findtime = {{ fail2ban_findtime }}
maxretry = {{ fail2ban_maxretry }}

[sshd]
enabled = true
port = {{ ssh_port }}
filter = sshd
logpath = /var/log/auth.log
backend = systemd
```

- [ ] **Step 4: Create `ansible/roles/ssh/tasks/main.yml`**

```yaml
---
- name: Ensure deploy user exists
  ansible.builtin.user:
    name: "{{ ssh_user }}"
    groups: sudo
    append: true
    shell: /bin/bash
    state: present

- name: Check if public key exists on control machine
  ansible.builtin.stat:
    path: "~/.ssh/do_deploy_key.pub"
  delegate_to: localhost
  become: false
  register: ssh_pubkey_file

- name: Ensure deploy user has authorized_keys
  ansible.posix.authorized_key:
    user: "{{ ssh_user }}"
    key: "{{ lookup('file', '~/.ssh/do_deploy_key.pub') }}"
    state: present
  when: ssh_pubkey_file.stat.exists

- name: Allow new SSH port in UFW before changing sshd
  community.general.ufw:
    rule: allow
    port: "{{ ssh_port }}"
    proto: tcp
    comment: "SSH"

- name: Also keep port 22 open during transition
  community.general.ufw:
    rule: allow
    port: "22"
    proto: tcp
    comment: "SSH-legacy"

- name: Deploy sshd_config
  ansible.builtin.template:
    src: sshd_config.j2
    dest: /etc/ssh/sshd_config
    owner: root
    group: root
    mode: "0644"
    validate: "sshd -t -f %s"
  notify: Restart sshd

- name: Flush handlers to apply sshd config now
  ansible.builtin.meta: flush_handlers

- name: Update Ansible connection port
  ansible.builtin.set_fact:
    ansible_port: "{{ ssh_port }}"

- name: Wait for SSH on new port
  ansible.builtin.wait_for:
    port: "{{ ssh_port }}"
    host: "{{ ansible_host }}"
    delay: 2
    timeout: 30
  delegate_to: localhost
  become: false

- name: Remove legacy port 22 rule from UFW
  community.general.ufw:
    rule: allow
    port: "22"
    proto: tcp
    delete: true
  ignore_errors: true

- name: Install fail2ban
  ansible.builtin.apt:
    name: fail2ban
    state: present
    update_cache: true

- name: Deploy fail2ban jail config
  ansible.builtin.template:
    src: jail.local.j2
    dest: /etc/fail2ban/jail.local
    owner: root
    group: root
    mode: "0644"
  notify: Restart fail2ban

- name: Enable and start fail2ban
  ansible.builtin.systemd:
    name: fail2ban
    enabled: true
    state: started
```

- [ ] **Step 5: Create `ansible/roles/ssh/handlers/main.yml`**

```yaml
---
- name: Restart sshd
  ansible.builtin.systemd:
    name: sshd
    state: restarted

- name: Restart fail2ban
  ansible.builtin.systemd:
    name: fail2ban
    state: restarted
```

- [ ] **Step 6: Syntax check**

Run: `cd ansible && ansible-playbook harden.yml --syntax-check -e "ansible_port=22" 2>&1 | head -5`
Expected: No YAML/syntax errors for the ssh role.

- [ ] **Step 7: Commit**

```bash
git add ansible/roles/ssh/
git commit -m "feat(ansible): add SSH hardening role — port change, fail2ban, disable root"
```

---

## Chunk 3: Firewall & Unattended Upgrades Roles

### Task 3: Create the firewall role

**Files:**
- Create: `ansible/roles/firewall/tasks/main.yml`
- Create: `ansible/roles/firewall/handlers/main.yml`
- Create: `ansible/roles/firewall/templates/update-cloudflare-ips.sh.j2`

- [ ] **Step 1: Create role directory structure**

```bash
mkdir -p ansible/roles/firewall/{tasks,handlers,templates}
```

- [ ] **Step 2: Create `ansible/roles/firewall/templates/update-cloudflare-ips.sh.j2`**

This script updates both UFW rules AND the Nginx realip config:

```bash
#!/bin/bash
# Managed by Ansible — do not edit manually
# Updates UFW rules and Nginx realip config for Cloudflare IP ranges
set -euo pipefail

NGINX_REALIP_CONF="/etc/nginx/snippets/cloudflare-realip.conf"

# Remove old Cloudflare UFW rules (delete in reverse order to preserve numbering)
ufw status numbered | grep 'Cloudflare' | \
  awk -F'[][]' '{print $2}' | sort -rn | \
  while read -r num; do ufw --force delete "$num"; done

# Start fresh Nginx realip config
echo "# Managed by update-cloudflare-ips.sh — do not edit manually" > "$NGINX_REALIP_CONF"

# Add Cloudflare IPv4 ranges
for ip in $(curl -s --fail https://www.cloudflare.com/ips-v4); do
  ufw allow from "$ip" to any port 80,443 proto tcp comment 'Cloudflare'
  echo "set_real_ip_from $ip;" >> "$NGINX_REALIP_CONF"
done

# Add Cloudflare IPv6 ranges
for ip in $(curl -s --fail https://www.cloudflare.com/ips-v6); do
  ufw allow from "$ip" to any port 80,443 proto tcp comment 'Cloudflare'
  echo "set_real_ip_from $ip;" >> "$NGINX_REALIP_CONF"
done

echo "real_ip_header CF-Connecting-IP;" >> "$NGINX_REALIP_CONF"

ufw reload

# Reload Nginx if it's running (may not be on first run)
if systemctl is-active --quiet nginx; then
  nginx -t && systemctl reload nginx
fi
```

- [ ] **Step 3: Create `ansible/roles/firewall/tasks/main.yml`**

```yaml
---
- name: Install UFW
  ansible.builtin.apt:
    name: ufw
    state: present
    update_cache: true

- name: Set UFW default deny incoming
  community.general.ufw:
    default: deny
    direction: incoming

- name: Set UFW default allow outgoing
  community.general.ufw:
    default: allow
    direction: outgoing

- name: Allow SSH on custom port
  community.general.ufw:
    rule: allow
    port: "{{ ssh_port }}"
    proto: tcp
    comment: "SSH"

- name: Ensure Nginx snippets directory exists
  ansible.builtin.file:
    path: /etc/nginx/snippets
    state: directory
    owner: root
    group: root
    mode: "0755"

- name: Deploy Cloudflare IP update script
  ansible.builtin.template:
    src: update-cloudflare-ips.sh.j2
    dest: /usr/local/bin/update-cloudflare-ips.sh
    owner: root
    group: root
    mode: "0755"

- name: Run Cloudflare IP update script now
  ansible.builtin.command: /usr/local/bin/update-cloudflare-ips.sh
  changed_when: true

- name: Schedule weekly Cloudflare IP update
  ansible.builtin.cron:
    name: "Update Cloudflare IPs"
    job: "/usr/local/bin/update-cloudflare-ips.sh"
    cron_file: cloudflare-ip-update
    user: root
    special_time: weekly
  when: cloudflare_ip_update_cron is not defined

- name: Schedule Cloudflare IP update with custom cron expression
  ansible.builtin.cron:
    name: "Update Cloudflare IPs"
    job: "/usr/local/bin/update-cloudflare-ips.sh"
    cron_file: cloudflare-ip-update
    user: root
    minute: "{{ cloudflare_ip_update_cron.split()[0] }}"
    hour: "{{ cloudflare_ip_update_cron.split()[1] }}"
    day: "{{ cloudflare_ip_update_cron.split()[2] }}"
    month: "{{ cloudflare_ip_update_cron.split()[3] }}"
    weekday: "{{ cloudflare_ip_update_cron.split()[4] }}"
  when: cloudflare_ip_update_cron is defined

- name: Enable UFW
  community.general.ufw:
    state: enabled
```

- [ ] **Step 4: Create `ansible/roles/firewall/handlers/main.yml`**

```yaml
---
- name: Reload UFW
  community.general.ufw:
    state: reloaded
```

- [ ] **Step 5: Commit**

```bash
git add ansible/roles/firewall/
git commit -m "feat(ansible): add firewall role — UFW with Cloudflare-only HTTP, weekly IP update"
```

### Task 4: Create the unattended-upgrades role

**Files:**
- Create: `ansible/roles/unattended-upgrades/tasks/main.yml`

- [ ] **Step 1: Create role directory structure**

```bash
mkdir -p ansible/roles/unattended-upgrades/tasks
```

- [ ] **Step 2: Create `ansible/roles/unattended-upgrades/tasks/main.yml`**

```yaml
---
- name: Install unattended-upgrades
  ansible.builtin.apt:
    name:
      - unattended-upgrades
      - apt-listchanges
    state: present
    update_cache: true

- name: Enable unattended-upgrades
  ansible.builtin.debconf:
    name: unattended-upgrades
    question: unattended-upgrades/enable_auto_updates
    value: "true"
    vtype: boolean

- name: Reconfigure unattended-upgrades
  ansible.builtin.command: dpkg-reconfigure -f noninteractive unattended-upgrades
  changed_when: false
```

- [ ] **Step 3: Commit**

```bash
git add ansible/roles/unattended-upgrades/
git commit -m "feat(ansible): add unattended-upgrades role — automatic security patches"
```

---

## Chunk 3: Certbot & Nginx Roles

### Task 5: Create the certbot role

**Files:**
- Create: `ansible/roles/certbot/tasks/main.yml`
- Create: `ansible/roles/certbot/templates/cloudflare-credentials.ini.j2`
- Create: `ansible/roles/certbot/templates/reload-nginx.sh.j2`

- [ ] **Step 1: Create role directory structure**

```bash
mkdir -p ansible/roles/certbot/{tasks,templates}
```

- [ ] **Step 2: Create `ansible/roles/certbot/templates/cloudflare-credentials.ini.j2`**

```ini
# Managed by Ansible — do not edit manually
dns_cloudflare_api_token = {{ vault_cloudflare_api_token }}
```

- [ ] **Step 3: Create `ansible/roles/certbot/templates/reload-nginx.sh.j2`**

```bash
#!/bin/bash
# Post-renewal hook — reloads Nginx after cert renewal
nginx -t && systemctl reload nginx
```

- [ ] **Step 4: Create `ansible/roles/certbot/tasks/main.yml`**

```yaml
---
- name: Install certbot and Cloudflare DNS plugin
  ansible.builtin.apt:
    name:
      - certbot
      - python3-certbot-dns-cloudflare
    state: present
    update_cache: true

- name: Create letsencrypt directory
  ansible.builtin.file:
    path: /etc/letsencrypt
    state: directory
    owner: root
    group: root
    mode: "0755"

- name: Deploy Cloudflare credentials
  ansible.builtin.template:
    src: cloudflare-credentials.ini.j2
    dest: /etc/letsencrypt/cloudflare.ini
    owner: root
    group: root
    mode: "0600"

- name: Check if certificate already exists
  ansible.builtin.stat:
    path: "/etc/letsencrypt/live/{{ domain }}/fullchain.pem"
  register: cert_file

- name: Obtain certificate via DNS-01 challenge
  ansible.builtin.command: >
    certbot certonly --dns-cloudflare
    --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini
    -d {{ domain }} -d www.{{ domain }}
    --email {{ certbot_email }}
    --agree-tos --non-interactive
  when: not cert_file.stat.exists
  register: certbot_result
  changed_when: "'Successfully received certificate' in certbot_result.stdout"

- name: Ensure renewal hooks directory exists
  ansible.builtin.file:
    path: /etc/letsencrypt/renewal-hooks/deploy
    state: directory
    owner: root
    group: root
    mode: "0755"

- name: Deploy post-renewal Nginx reload hook
  ansible.builtin.template:
    src: reload-nginx.sh.j2
    dest: /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
    owner: root
    group: root
    mode: "0755"

- name: Enable certbot renewal timer
  ansible.builtin.systemd:
    name: certbot.timer
    enabled: true
    state: started
```

- [ ] **Step 5: Commit**

```bash
git add ansible/roles/certbot/
git commit -m "feat(ansible): add certbot role — Let's Encrypt via DNS-01 with Cloudflare"
```

### Task 6: Create the nginx role

**Files:**
- Create: `ansible/roles/nginx/tasks/main.yml`
- Create: `ansible/roles/nginx/handlers/main.yml`
- Create: `ansible/roles/nginx/templates/security-headers.conf.j2`
- Create: `ansible/roles/nginx/templates/rate-limiting.conf.j2`
- Create: `ansible/roles/nginx/templates/ssl-params.conf.j2`
- Create: `ansible/roles/nginx/templates/vhost.conf.j2`

- [ ] **Step 1: Create role directory structure**

```bash
mkdir -p ansible/roles/nginx/{tasks,handlers,templates}
```

- [ ] **Step 2: Create `ansible/roles/nginx/templates/security-headers.conf.j2`**

```nginx
# Managed by Ansible — do not edit manually
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' https://js.stripe.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https://{{ domain }} https://api.stripe.com; font-src 'self'; frame-src https://js.stripe.com; frame-ancestors 'none';" always;
```

- [ ] **Step 3: Create `ansible/roles/nginx/templates/rate-limiting.conf.j2`**

```nginx
# Managed by Ansible — do not edit manually
limit_req_zone $remote_addr zone=api:10m rate={{ nginx_rate_limit_api }};
limit_req_zone $remote_addr zone=login:10m rate={{ nginx_rate_limit_login }};
```

- [ ] **Step 4: Create `ansible/roles/nginx/templates/ssl-params.conf.j2`**

```nginx
# Managed by Ansible — do not edit manually
ssl_certificate /etc/letsencrypt/live/{{ domain }}/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/{{ domain }}/privkey.pem;
ssl_protocols TLSv1.2 TLSv1.3;
ssl_prefer_server_ciphers on;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;
```

- [ ] **Step 5: Create `ansible/roles/nginx/templates/vhost.conf.j2`**

```nginx
# Managed by Ansible — do not edit manually
# Vhost for {{ domain }}

upstream php-fpm {
    server unix:/run/php/php{{ php_version }}-fpm.sock;
}

# HTTP → HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name {{ domain }} www.{{ domain }};
    return 301 https://{{ domain }}$request_uri;
}

# HTTPS server
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name {{ domain }} www.{{ domain }};

    root {{ deploy_path }}/webroot;
    index index.php;

    # Include snippets
    include /etc/nginx/snippets/cloudflare-realip.conf;
    include /etc/nginx/snippets/ssl-params.conf;
    include /etc/nginx/snippets/security-headers.conf;

    # Logging
    access_log /var/log/nginx/{{ domain }}.access.log;
    error_log /var/log/nginx/{{ domain }}.error.log;

    # Login rate limiting (must come before general /api/)
    location = /api/v1/web/auth/login {
        limit_req zone=login burst={{ nginx_rate_limit_login_burst }} nodelay;

        try_files $uri $uri/ /index.php?$args;
        include fastcgi_params;
        fastcgi_pass php-fpm;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    }

    # API rate limiting
    location /api/ {
        limit_req zone=api burst={{ nginx_rate_limit_api_burst }} nodelay;

        try_files $uri $uri/ /index.php?$args;
        include fastcgi_params;
        fastcgi_pass php-fpm;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    }

    # PHP handler
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass php-fpm;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_intercept_errors on;
    }

    # Static assets (re-include security headers since add_header in location overrides parent)
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        include /etc/nginx/snippets/security-headers.conf;
        expires 1y;
        add_header Cache-Control "public, immutable" always;
        try_files $uri =404;
    }

    # SPA fallback — all non-API, non-static routes go to CakePHP
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # Deny dotfiles
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
}
```

- [ ] **Step 6: Create `ansible/roles/nginx/tasks/main.yml`**

```yaml
---
- name: Disable server tokens
  ansible.builtin.lineinfile:
    path: /etc/nginx/nginx.conf
    regexp: '^\s*server_tokens'
    line: "        server_tokens off;"
    insertafter: 'http {'
  notify: Test and reload Nginx

- name: Include rate-limiting in nginx.conf http block
  ansible.builtin.lineinfile:
    path: /etc/nginx/nginx.conf
    regexp: 'include.*rate-limiting'
    line: "        include /etc/nginx/snippets/rate-limiting.conf;"
    insertafter: 'http {'
  notify: Test and reload Nginx

- name: Deploy security headers snippet
  ansible.builtin.template:
    src: security-headers.conf.j2
    dest: /etc/nginx/snippets/security-headers.conf
    owner: root
    group: root
    mode: "0644"
  notify: Test and reload Nginx

- name: Deploy rate-limiting snippet
  ansible.builtin.template:
    src: rate-limiting.conf.j2
    dest: /etc/nginx/snippets/rate-limiting.conf
    owner: root
    group: root
    mode: "0644"
  notify: Test and reload Nginx

- name: Deploy SSL params snippet
  ansible.builtin.template:
    src: ssl-params.conf.j2
    dest: /etc/nginx/snippets/ssl-params.conf
    owner: root
    group: root
    mode: "0644"
  notify: Test and reload Nginx

- name: Deploy vhost configuration
  ansible.builtin.template:
    src: vhost.conf.j2
    dest: "/etc/nginx/sites-available/{{ domain }}.conf"
    owner: root
    group: root
    mode: "0644"
  notify: Test and reload Nginx

- name: Enable vhost
  ansible.builtin.file:
    src: "/etc/nginx/sites-available/{{ domain }}.conf"
    dest: "/etc/nginx/sites-enabled/{{ domain }}.conf"
    state: link
  notify: Test and reload Nginx

- name: Remove default site
  ansible.builtin.file:
    path: /etc/nginx/sites-enabled/default
    state: absent
  notify: Test and reload Nginx
```

- [ ] **Step 7: Create `ansible/roles/nginx/handlers/main.yml`**

```yaml
---
- name: Test and reload Nginx
  ansible.builtin.shell: nginx -t && systemctl reload nginx
  listen: "Test and reload Nginx"
```

- [ ] **Step 8: Commit**

```bash
git add ansible/roles/nginx/
git commit -m "feat(ansible): add nginx role — vhost, security headers, rate limiting, SSL"
```

---

## Chunk 4: PostgreSQL, Backups & Monitoring Roles

### Task 7: Create the postgres role

**Files:**
- Create: `ansible/roles/postgres/tasks/main.yml`
- Create: `ansible/roles/postgres/handlers/main.yml`

- [ ] **Step 1: Create role directory structure**

```bash
mkdir -p ansible/roles/postgres/{tasks,handlers}
```

- [ ] **Step 2: Create `ansible/roles/postgres/tasks/main.yml`**

```yaml
---
- name: Gather package facts for PostgreSQL version detection
  ansible.builtin.package_facts:
    manager: auto

- name: Ensure PostgreSQL listens on localhost only
  ansible.builtin.lineinfile:
    path: "/etc/postgresql/{{ ansible_facts['packages']['postgresql'][0]['version'] | regex_search('^[0-9]+') }}/main/postgresql.conf"
    regexp: "^#?listen_addresses"
    line: "listen_addresses = 'localhost'"
  notify: Restart PostgreSQL

- name: Create application database user
  become_user: postgres
  community.postgresql.postgresql_user:
    name: "{{ db_app_user }}"
    password: "{{ vault_db_app_password }}"
    state: present

- name: Grant app user DML privileges on database
  become_user: postgres
  community.postgresql.postgresql_privs:
    database: "{{ db_name }}"
    roles: "{{ db_app_user }}"
    type: table
    objs: ALL_IN_SCHEMA
    privs: SELECT,INSERT,UPDATE,DELETE
    schema: public

- name: Grant app user sequence usage
  become_user: postgres
  community.postgresql.postgresql_privs:
    database: "{{ db_name }}"
    roles: "{{ db_app_user }}"
    type: sequence
    objs: ALL_IN_SCHEMA
    privs: USAGE,SELECT
    schema: public

- name: Set default privileges for app user on future tables
  become_user: postgres
  community.postgresql.postgresql_privs:
    database: "{{ db_name }}"
    roles: "{{ db_app_user }}"
    type: default_privs
    objs: TABLES
    privs: SELECT,INSERT,UPDATE,DELETE
    schema: public

- name: Set default privileges for app user on future sequences
  become_user: postgres
  community.postgresql.postgresql_privs:
    database: "{{ db_name }}"
    roles: "{{ db_app_user }}"
    type: default_privs
    objs: SEQUENCES
    privs: USAGE,SELECT
    schema: public

- name: Create backup user
  become_user: postgres
  community.postgresql.postgresql_user:
    name: "{{ db_backup_user }}"
    password: "{{ vault_db_backup_password }}"
    role_attr_flags: ""
    state: present

- name: Grant pg_read_all_data to backup user
  become_user: postgres
  community.postgresql.postgresql_membership:
    groups: pg_read_all_data
    target_roles: "{{ db_backup_user }}"

- name: Create migration user
  become_user: postgres
  community.postgresql.postgresql_user:
    name: "{{ db_migration_user }}"
    password: "{{ vault_db_migration_password }}"
    state: present

- name: Grant migration user full privileges on database
  become_user: postgres
  community.postgresql.postgresql_privs:
    database: "{{ db_name }}"
    roles: "{{ db_migration_user }}"
    type: database
    privs: ALL

- name: Grant migration user full privileges on schema
  become_user: postgres
  community.postgresql.postgresql_privs:
    database: "{{ db_name }}"
    roles: "{{ db_migration_user }}"
    type: schema
    objs: public
    privs: ALL

- name: Harden pg_hba.conf — local connections use scram-sha-256
  ansible.builtin.lineinfile:
    path: "/etc/postgresql/{{ ansible_facts['packages']['postgresql'][0]['version'] | regex_search('^[0-9]+') }}/main/pg_hba.conf"
    regexp: "^local\\s+all\\s+all"
    line: "local   all             all                                     scram-sha-256"
  notify: Restart PostgreSQL

- name: Harden pg_hba.conf — IPv4 localhost only scram-sha-256
  ansible.builtin.lineinfile:
    path: "/etc/postgresql/{{ ansible_facts['packages']['postgresql'][0]['version'] | regex_search('^[0-9]+') }}/main/pg_hba.conf"
    regexp: "^host\\s+all\\s+all\\s+127"
    line: "host    all             all             127.0.0.1/32            scram-sha-256"
  notify: Restart PostgreSQL

- name: Remove any remote access lines from pg_hba.conf
  ansible.builtin.lineinfile:
    path: "/etc/postgresql/{{ ansible_facts['packages']['postgresql'][0]['version'] | regex_search('^[0-9]+') }}/main/pg_hba.conf"
    regexp: "^host\\s+all\\s+all\\s+0\\.0\\.0\\.0"
    state: absent
  notify: Restart PostgreSQL

```

- [ ] **Step 3: Create `ansible/roles/postgres/handlers/main.yml`**

```yaml
---
- name: Restart PostgreSQL
  ansible.builtin.systemd:
    name: postgresql
    state: restarted
```

- [ ] **Step 4: Commit**

```bash
git add ansible/roles/postgres/
git commit -m "feat(ansible): add postgres role — dedicated users, pg_hba hardening, localhost-only"
```

### Task 8: Create the backups role

**Files:**
- Create: `ansible/roles/backups/tasks/main.yml`
- Create: `ansible/roles/backups/templates/pg-backup.sh.j2`

- [ ] **Step 1: Create role directory structure**

```bash
mkdir -p ansible/roles/backups/{tasks,templates}
```

- [ ] **Step 2: Create `ansible/roles/backups/templates/pg-backup.sh.j2`**

```bash
#!/bin/bash
# Managed by Ansible — do not edit manually
set -euo pipefail

BACKUP_DIR=/var/backups/postgresql
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_NAME="{{ db_name }}"
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

- [ ] **Step 3: Create `ansible/roles/backups/tasks/main.yml`**

```yaml
---
- name: Create backup directory
  ansible.builtin.file:
    path: /var/backups/postgresql
    state: directory
    owner: root
    group: root
    mode: "0750"

- name: Deploy pgpass file for backup user
  ansible.builtin.copy:
    content: "localhost:*:{{ db_name }}:{{ db_backup_user }}:{{ vault_db_backup_password }}"
    dest: /root/.pgpass
    owner: root
    group: root
    mode: "0600"

- name: Deploy pg-backup script
  ansible.builtin.template:
    src: pg-backup.sh.j2
    dest: /usr/local/bin/pg-backup.sh
    owner: root
    group: root
    mode: "0755"

- name: Schedule daily backup cron
  ansible.builtin.cron:
    name: "PostgreSQL daily backup"
    job: "/usr/local/bin/pg-backup.sh"
    minute: "{{ backup_minute }}"
    hour: "{{ backup_hour }}"
    user: root

- name: Run initial backup to verify
  ansible.builtin.command: /usr/local/bin/pg-backup.sh
  changed_when: true
  ignore_errors: true
```

- [ ] **Step 4: Commit**

```bash
git add ansible/roles/backups/
git commit -m "feat(ansible): add backups role — daily pg_dump with retention and failure alerts"
```

### Task 9: Create the monitoring role

**Files:**
- Create: `ansible/roles/monitoring/tasks/main.yml`
- Create: `ansible/roles/monitoring/templates/msmtprc.j2`
- Create: `ansible/roles/monitoring/templates/logwatch.conf.j2`
- Create: `ansible/roles/monitoring/templates/ssh-alert.sh.j2`

- [ ] **Step 1: Create role directory structure**

```bash
mkdir -p ansible/roles/monitoring/{tasks,templates}
```

- [ ] **Step 2: Create `ansible/roles/monitoring/templates/msmtprc.j2`**

```
# Managed by Ansible — do not edit manually
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
logfile /var/log/msmtp.log
```

- [ ] **Step 3: Create `ansible/roles/monitoring/templates/logwatch.conf.j2`**

```
# Managed by Ansible — do not edit manually
MailTo = {{ alert_email }}
MailFrom = logwatch@{{ domain }}
Detail = Med
Range = yesterday
Service = All
Output = mail
Format = html
```

- [ ] **Step 4: Create `ansible/roles/monitoring/templates/ssh-alert.sh.j2`**

```bash
# Managed by Ansible — do not edit manually
if [ -n "$SSH_CLIENT" ]; then
  # Skip alerts for automated deploy user (Ansible/CI sessions)
  if [ "$(whoami)" != "{{ deploy_user }}" ]; then
    IP=$(echo $SSH_CLIENT | awk '{print $1}')
    echo "SSH login: $(whoami) from $IP at $(date)" \
      | mail -s "SSH Alert: $(hostname)" {{ alert_email }}
  fi
fi
```

- [ ] **Step 5: Create `ansible/roles/monitoring/tasks/main.yml`**

```yaml
---
- name: Install mail tools
  ansible.builtin.apt:
    name:
      - msmtp
      - msmtp-mta
      - mailutils
    state: present
    update_cache: true

- name: Deploy msmtp config
  ansible.builtin.template:
    src: msmtprc.j2
    dest: /etc/msmtprc
    owner: root
    group: root
    mode: "0600"

- name: Create msmtp log file
  ansible.builtin.file:
    path: /var/log/msmtp.log
    state: touch
    owner: root
    group: root
    mode: "0660"
  changed_when: false

- name: Install logwatch
  ansible.builtin.apt:
    name: logwatch
    state: present

- name: Ensure logwatch conf directory exists
  ansible.builtin.file:
    path: /etc/logwatch/conf
    state: directory
    owner: root
    group: root
    mode: "0755"

- name: Deploy logwatch config
  ansible.builtin.template:
    src: logwatch.conf.j2
    dest: /etc/logwatch/conf/logwatch.conf
    owner: root
    group: root
    mode: "0644"

- name: Deploy SSH login alert script
  ansible.builtin.template:
    src: ssh-alert.sh.j2
    dest: /etc/profile.d/ssh-alert.sh
    owner: root
    group: root
    mode: "0644"

- name: Send test email to verify SMTP
  ansible.builtin.shell: |
    echo "Ansible monitoring test from $(hostname) at $(date)" \
      | mail -s "Test: Monitoring configured on $(hostname)" {{ alert_email }}
  changed_when: false
  ignore_errors: true
```

- [ ] **Step 6: Commit**

```bash
git add ansible/roles/monitoring/
git commit -m "feat(ansible): add monitoring role — msmtp/Mailgun, logwatch, SSH login alerts"
```

---

## Chunk 5: Cloudflare, Deploy Role & GitHub Actions

### Task 10: Create the cloudflare role

**Files:**
- Create: `ansible/roles/cloudflare/tasks/main.yml`

- [ ] **Step 1: Create role directory structure**

```bash
mkdir -p ansible/roles/cloudflare/tasks
```

- [ ] **Step 2: Create `ansible/roles/cloudflare/tasks/main.yml`**

```yaml
---
- name: Look up Cloudflare zone ID
  ansible.builtin.uri:
    url: "https://api.cloudflare.com/client/v4/zones?name={{ domain }}"
    method: GET
    headers:
      Authorization: "Bearer {{ vault_cloudflare_api_token }}"
    return_content: true
  register: cf_zone
  delegate_to: localhost
  become: false

- name: Set zone ID fact
  ansible.builtin.set_fact:
    cf_zone_id: "{{ cf_zone.json.result[0].id }}"

- name: Set SSL/TLS mode to Full (Strict)
  ansible.builtin.uri:
    url: "https://api.cloudflare.com/client/v4/zones/{{ cf_zone_id }}/settings/ssl"
    method: PATCH
    headers:
      Authorization: "Bearer {{ vault_cloudflare_api_token }}"
      Content-Type: "application/json"
    body_format: json
    body:
      value: "strict"
  delegate_to: localhost
  become: false

- name: Enable Always Use HTTPS
  ansible.builtin.uri:
    url: "https://api.cloudflare.com/client/v4/zones/{{ cf_zone_id }}/settings/always_use_https"
    method: PATCH
    headers:
      Authorization: "Bearer {{ vault_cloudflare_api_token }}"
      Content-Type: "application/json"
    body_format: json
    body:
      value: "on"
  delegate_to: localhost
  become: false

- name: Set Minimum TLS Version to 1.2
  ansible.builtin.uri:
    url: "https://api.cloudflare.com/client/v4/zones/{{ cf_zone_id }}/settings/min_tls_version"
    method: PATCH
    headers:
      Authorization: "Bearer {{ vault_cloudflare_api_token }}"
      Content-Type: "application/json"
    body_format: json
    body:
      value: "1.2"
  delegate_to: localhost
  become: false

- name: Enable HSTS
  ansible.builtin.uri:
    url: "https://api.cloudflare.com/client/v4/zones/{{ cf_zone_id }}/settings/security_header"
    method: PATCH
    headers:
      Authorization: "Bearer {{ vault_cloudflare_api_token }}"
      Content-Type: "application/json"
    body_format: json
    body:
      value:
        strict_transport_security:
          enabled: true
          max_age: 31536000
          include_subdomains: true
          preload: true
          nosniff: true
  delegate_to: localhost
  become: false

- name: Enable Bot Fight Mode
  ansible.builtin.uri:
    url: "https://api.cloudflare.com/client/v4/zones/{{ cf_zone_id }}/bot_management"
    method: PUT
    headers:
      Authorization: "Bearer {{ vault_cloudflare_api_token }}"
      Content-Type: "application/json"
    body_format: json
    body:
      fight_mode: true
    status_code: [200, 403]
  delegate_to: localhost
  become: false
  ignore_errors: true

- name: Set Browser Cache TTL to 4 hours
  ansible.builtin.uri:
    url: "https://api.cloudflare.com/client/v4/zones/{{ cf_zone_id }}/settings/browser_cache_ttl"
    method: PATCH
    headers:
      Authorization: "Bearer {{ vault_cloudflare_api_token }}"
      Content-Type: "application/json"
    body_format: json
    body:
      value: 14400
  delegate_to: localhost
  become: false

- name: Check existing cache rules
  ansible.builtin.uri:
    url: "https://api.cloudflare.com/client/v4/zones/{{ cf_zone_id }}/rulesets?phase=http_request_cache_settings"
    method: GET
    headers:
      Authorization: "Bearer {{ vault_cloudflare_api_token }}"
    return_content: true
  register: cf_cache_rules
  delegate_to: localhost
  become: false

- name: Create cache bypass rule for API paths
  ansible.builtin.uri:
    url: "https://api.cloudflare.com/client/v4/zones/{{ cf_zone_id }}/rulesets"
    method: POST
    headers:
      Authorization: "Bearer {{ vault_cloudflare_api_token }}"
      Content-Type: "application/json"
    body_format: json
    body:
      name: "Bypass cache for API"
      kind: "zone"
      phase: "http_request_cache_settings"
      rules:
        - expression: '(starts_with(http.request.uri.path, "/api/"))'
          description: "Bypass cache for API endpoints"
          action: "set_cache_settings"
          action_parameters:
            cache: false
    status_code: [200, 409]
  delegate_to: localhost
  become: false
  when: cf_cache_rules.json.result | length == 0
```

- [ ] **Step 3: Commit**

```bash
git add ansible/roles/cloudflare/
git commit -m "feat(ansible): add cloudflare role — SSL strict, HTTPS, TLS 1.2, HSTS, bot fight, cache rules"
```

### Task 11: Create the deploy role

**Files:**
- Create: `ansible/roles/deploy/tasks/main.yml`

- [ ] **Step 1: Create role directory structure**

```bash
mkdir -p ansible/roles/deploy/tasks
```

- [ ] **Step 2: Create `ansible/roles/deploy/tasks/main.yml`**

```yaml
---
- name: Sync application code
  become: false
  ansible.posix.synchronize:
    src: "{{ playbook_dir }}/../"
    dest: "{{ deploy_path }}/"
    delete: "{{ deploy_rsync_delete }}"
    rsync_opts:
      - "--exclude=.git/"
      - "--exclude=vendor/"
      - "--exclude=node_modules/"
      - "--exclude=client/"
      - "--exclude=logs/"
      - "--exclude=tmp/"
      - "--exclude=docs/"
      - "--exclude=tests/"
      - "--exclude=config/app_local.php"
      - "--exclude=config/.env"
      - "--exclude=.ddev/"
      - "--exclude=ansible/"
      - "--exclude=webroot/uploads/"
      - "-e ssh -i {{ ansible_ssh_private_key_file | default('~/.ssh/do_deploy_key') }} -p {{ ansible_port | default(ssh_port) }}"

- name: Run composer install
  ansible.builtin.command:
    cmd: composer install --no-dev --optimize-autoloader --no-interaction
    chdir: "{{ deploy_path }}"
  changed_when: true

- name: Create required directories
  ansible.builtin.file:
    path: "{{ deploy_path }}/{{ item }}"
    state: directory
    owner: "{{ deploy_user }}"
    group: www-data
    mode: "0775"
  loop:
    - tmp/cache/models
    - tmp/cache/persistent
    - tmp/cache/views
    - tmp/sessions
    - logs

- name: Run database migrations
  ansible.builtin.shell:
    cmd: >
      DATABASE_URL="postgres://{{ db_migration_user }}:{{ vault_db_migration_password }}@localhost/{{ db_name }}"
      bin/cake migrations migrate --no-lock
    chdir: "{{ deploy_path }}"
  changed_when: true

- name: Clear CakePHP cache
  ansible.builtin.command:
    cmd: bin/cake cache clear_all
    chdir: "{{ deploy_path }}"
  changed_when: true

- name: Build schema cache
  ansible.builtin.command:
    cmd: bin/cake schema_cache build
    chdir: "{{ deploy_path }}"
  changed_when: true

- name: Reload PHP-FPM
  ansible.builtin.systemd:
    name: "php{{ php_version }}-fpm"
    state: reloaded
```

- [ ] **Step 3: Commit**

```bash
git add ansible/roles/deploy/
git commit -m "feat(ansible): add deploy role — rsync, composer, migrations, cache, PHP-FPM reload"
```

### Task 12: Update GitHub Actions deploy workflow

**Files:**
- Modify: `.github/workflows/deploy.yml`

- [ ] **Step 1: Replace `.github/workflows/deploy.yml` with Ansible-based deploy**

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    environment: live

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Install Ansible
        run: |
          pip install ansible
          ansible-galaxy install -r ansible/requirements.yml

      - name: Set up SSH key
        run: |
          mkdir -p ~/.ssh
          printf '%s\n' "${{ secrets.DO_SSH_KEY }}" > ~/.ssh/deploy_key
          chmod 600 ~/.ssh/deploy_key
          ssh-keyscan -p ${{ secrets.DO_SSH_PORT }} -H "${{ secrets.DO_SSH_HOST }}" >> ~/.ssh/known_hosts
          chmod 600 ~/.ssh/known_hosts

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

- [ ] **Step 2: Verify YAML syntax**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/workflows/deploy.yml'))" && echo "Valid YAML"`
Expected: "Valid YAML"

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/deploy.yml
git commit -m "feat: replace raw rsync deploy with Ansible-based deploy via GitHub Actions"
```

---

## Chunk 6: Final Verification & Documentation

### Task 13: Add .gitignore rules and verify project

**Files:**
- Modify: `.gitignore` (add ansible vault exclusion)

- [ ] **Step 1: Add vault files to .gitignore**

Add to the root `.gitignore`:

```
# Ansible vault (encrypted secrets — created manually, never committed)
ansible/vault/*.yml
!ansible/vault/*.yml.example
```

- [ ] **Step 2: Run full syntax check on both playbooks**

```bash
cd ansible
ansible-playbook harden.yml --syntax-check -e "ansible_port=22"
ansible-playbook deploy.yml --syntax-check
```

Expected: No syntax errors for either playbook.

- [ ] **Step 3: Verify all role directories are present**

```bash
ls -d ansible/roles/*/
```

Expected: ssh, firewall, unattended-upgrades, certbot, nginx, postgres, backups, monitoring, cloudflare, deploy (10 roles).

- [ ] **Step 4: Commit**

```bash
git add .gitignore
git commit -m "chore: add ansible vault to .gitignore"
```

### Task 14: Final commit — all Ansible files together

This is a verification step. If all tasks above were committed individually, this step is a no-op. If anything was missed:

- [ ] **Step 1: Check for uncommitted files**

```bash
git status
```

- [ ] **Step 2: If anything is left, commit it**

```bash
git add ansible/ .github/workflows/deploy.yml
git commit -m "feat: complete Ansible server hardening and deployment automation"
```

---

## Post-Implementation: Manual Steps Checklist

These cannot be automated and must be done by the user after the code is committed:

- [ ] Fill in `<DROPLET_IP>` in `ansible/inventory/production.yml`
- [ ] Create encrypted vault: `cd ansible && ansible-vault create vault/production.yml` (use `vault/production.yml.example` as template)
- [ ] Revoke the Cloudflare API token shared in chat, generate a new one
- [ ] Add `ANSIBLE_VAULT_PASSWORD` and `DO_SSH_PORT=2222` to GitHub repo secrets
- [ ] Take a DigitalOcean snapshot before running hardening (safety net)
- [ ] Run hardening: `ansible-playbook -i inventory/production.yml harden.yml --ask-vault-pass -e "ansible_port=22"`
- [ ] Verify site works after hardening
- [ ] Update `DO_SSH_PORT` GitHub secret to `2222`
- [ ] Push to `main` to test the new Ansible-based deploy via GitHub Actions
- [ ] Monitor Cloudflare firewall events for 1 week for false positives
- [ ] Enable DigitalOcean monitoring agent from droplet dashboard
