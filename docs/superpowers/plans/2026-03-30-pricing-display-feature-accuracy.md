# Pricing Display & Feature Accuracy Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix pricing display spacing, add per-user pricing to Club Pro+, and add "coming soon" features section to the subscription dashboard view.

**Architecture:** Pure frontend changes — Vue template edits and i18n key additions. No backend, no new components, no routing changes.

**Tech Stack:** Vue 3, vue-i18n, Vite

---

## File Structure

| File | Action | Responsibility |
|------|--------|---------------|
| `client/src/views/landing/LandingPage.vue` | Modify (lines 118, 138, 157) | Price spacing fix + Club Pro+ per-user pricing |
| `client/src/views/dashboard/DashboardHome.vue` | Modify (line 38) | Price spacing fix in upsell card |
| `client/src/views/dashboard/SubscriptionView.vue` | Modify (line 35, lines 75-83) | Price spacing fix + coming soon section |
| `client/src/i18n/locales/en.json` | Modify | Add new keys: `per_user_month`, `club_pro_min_users`, `coming_soon`, `coming_soon_sync`, `coming_soon_analytics` |
| `client/src/i18n/locales/es.json` | Modify | Same new keys (Spanish) |
| `client/src/i18n/locales/fr.json` | Modify | Same new keys (French) |
| `client/src/i18n/locales/de.json` | Modify | Same new keys (German) |
| `client/src/i18n/locales/pt.json` | Modify | Same new keys (Portuguese) |
| `client/src/i18n/locales/ca.json` | Modify | Same new keys (Catalan) |
| `client/src/i18n/locales/eu.json` | Modify | Same new keys (Basque) |
| `client/src/i18n/locales/gl.json` | Modify | Same new keys (Galician) |

---

### Task 1: Add new i18n keys to all 8 locale files

**Files:**
- Modify: `client/src/i18n/locales/en.json` (subscription section, around line 217)
- Modify: `client/src/i18n/locales/es.json` (subscription section, around line 217)
- Modify: `client/src/i18n/locales/fr.json` (subscription section, around line 217)
- Modify: `client/src/i18n/locales/de.json` (subscription section, around line 217)
- Modify: `client/src/i18n/locales/pt.json` (subscription section, around line 217)
- Modify: `client/src/i18n/locales/ca.json` (subscription section, around line 217)
- Modify: `client/src/i18n/locales/eu.json` (subscription section, around line 217)
- Modify: `client/src/i18n/locales/gl.json` (subscription section, around line 217)

Also add `club_pro_min_users` to the `landing` section of each locale (around line 79).

- [ ] **Step 1: Add new keys to `en.json`**

In the `subscription` section, after the `"per_month": "month"` line (line 217), add:

```json
"per_user_month": "user / month",
"coming_soon": "Coming Soon",
"coming_soon_sync": "Sync your data across all your devices",
"coming_soon_analytics": "Historical shooting analytics, trends, and charts",
```

In the `landing` section, after `"from": "from"` (line 79), add:

```json
"club_pro_min_users": "Minimum 10 users",
```

- [ ] **Step 2: Add new keys to `es.json`**

In the `subscription` section, after `"per_month": "mes"`, add:

```json
"per_user_month": "usuario / mes",
"coming_soon": "Próximamente",
"coming_soon_sync": "Sincroniza tus datos en todos tus dispositivos",
"coming_soon_analytics": "Análisis histórico de tiro, tendencias y gráficos",
```

In the `landing` section, after `"from": "desde"`, add:

```json
"club_pro_min_users": "Mínimo 10 usuarios",
```

- [ ] **Step 3: Add new keys to `fr.json`**

In the `subscription` section, after `"per_month"`, add:

```json
"per_user_month": "utilisateur / mois",
"coming_soon": "Bientôt disponible",
"coming_soon_sync": "Synchronisez vos données sur tous vos appareils",
"coming_soon_analytics": "Analyse historique de tir, tendances et graphiques",
```

In the `landing` section, after `"from"`, add:

```json
"club_pro_min_users": "Minimum 10 utilisateurs",
```

- [ ] **Step 4: Add new keys to `de.json`**

In the `subscription` section, after `"per_month"`, add:

```json
"per_user_month": "Benutzer / Monat",
"coming_soon": "Demnächst verfügbar",
"coming_soon_sync": "Synchronisieren Sie Ihre Daten auf all Ihren Geräten",
"coming_soon_analytics": "Historische Schießanalysen, Trends und Diagramme",
```

In the `landing` section, after `"from"`, add:

```json
"club_pro_min_users": "Mindestens 10 Benutzer",
```

- [ ] **Step 5: Add new keys to `pt.json`**

In the `subscription` section, after `"per_month"`, add:

```json
"per_user_month": "utilizador / mês",
"coming_soon": "Em breve",
"coming_soon_sync": "Sincronize os seus dados em todos os seus dispositivos",
"coming_soon_analytics": "Análise histórica de tiro, tendências e gráficos",
```

In the `landing` section, after `"from"`, add:

```json
"club_pro_min_users": "Mínimo 10 utilizadores",
```

- [ ] **Step 6: Add new keys to `ca.json`**

In the `subscription` section, after `"per_month"`, add:

```json
"per_user_month": "usuari / mes",
"coming_soon": "Properament",
"coming_soon_sync": "Sincronitza les teves dades a tots els teus dispositius",
"coming_soon_analytics": "Anàlisi històrica de tir, tendències i gràfics",
```

In the `landing` section, after `"from"`, add:

```json
"club_pro_min_users": "Mínim 10 usuaris",
```

- [ ] **Step 7: Add new keys to `eu.json`**

In the `subscription` section, after `"per_month"`, add:

```json
"per_user_month": "erabiltzaile / hilabete",
"coming_soon": "Laster",
"coming_soon_sync": "Sinkronizatu zure datuak zure gailu guztietan",
"coming_soon_analytics": "Tiro-analisi historikoa, joerak eta grafikoak",
```

In the `landing` section, after `"from"`, add:

```json
"club_pro_min_users": "Gutxienez 10 erabiltzaile",
```

- [ ] **Step 8: Add new keys to `gl.json`**

In the `subscription` section, after `"per_month"`, add:

```json
"per_user_month": "usuario / mes",
"coming_soon": "Proximamente",
"coming_soon_sync": "Sincroniza os teus datos en todos os teus dispositivos",
"coming_soon_analytics": "Análise histórica de tiro, tendencias e gráficos",
```

In the `landing` section, after `"from"`, add:

```json
"club_pro_min_users": "Mínimo 10 usuarios",
```

- [ ] **Step 9: Verify the app compiles**

Run: `cd client && npm run build 2>&1 | tail -5`

Expected: Build succeeds with no i18n errors.

- [ ] **Step 10: Commit**

```bash
git add client/src/i18n/locales/*.json
git commit -m "feat: add i18n keys for per-user pricing and coming soon features"
```

---

### Task 2: Fix price spacing on Landing Page

**Files:**
- Modify: `client/src/views/landing/LandingPage.vue:118,138,157`

- [ ] **Step 1: Fix Free Plan price spacing (line 118)**

Change:
```html
<div class="price font-heading text-4xl font-bold mb-6">$0<span class="text-base font-body font-normal text-secondary">/ {{ $t('subscription.forever') }}</span></div>
```
to:
```html
<div class="price font-heading text-4xl font-bold mb-6">$0 <span class="text-base font-body font-normal text-secondary">/ {{ $t('subscription.forever') }}</span></div>
```

(Added a space after `$0`)

- [ ] **Step 2: Fix Pro+ price spacing (line 138)**

Change:
```html
<div class="price font-heading text-4xl font-bold mb-6">$4.99<span class="text-base font-body font-normal text-secondary">/ {{ $t('subscription.per_month') }}</span></div>
```
to:
```html
<div class="price font-heading text-4xl font-bold mb-6">$4.99 <span class="text-base font-body font-normal text-secondary">/ {{ $t('subscription.per_month') }}</span></div>
```

(Added a space after `$4.99`)

- [ ] **Step 3: Fix Club Pro+ price — change to per-user pricing (line 157)**

Change:
```html
<div class="price font-heading text-4xl font-bold mb-6"><span class="text-base font-body font-normal text-secondary">{{ $t('landing.from') }} </span>$4.99<span class="text-base font-body font-normal text-secondary">/ {{ $t('subscription.per_month') }}</span></div>
```
to:
```html
<div class="price font-heading text-4xl font-bold mb-6"><span class="text-base font-body font-normal text-secondary">{{ $t('landing.from') }} </span>$4.99 <span class="text-base font-body font-normal text-secondary">/ {{ $t('subscription.per_user_month') }}</span></div>
<div class="text-sm text-secondary mb-2">{{ $t('landing.club_pro_min_users') }}</div>
```

This changes `from $4.99/ month` → `from $4.99 / user / month` with "Minimum 10 users" text below.

- [ ] **Step 4: Visually verify in browser**

Run: `cd client && npm run dev`

Navigate to the landing page and check:
- Free Plan shows: `$0 / forever`
- Pro+ shows: `$4.99 / month`
- Club Pro+ shows: `from $4.99 / user / month` with "Minimum 10 users" below

- [ ] **Step 5: Commit**

```bash
git add client/src/views/landing/LandingPage.vue
git commit -m "fix: add spacing to pricing display and per-user pricing for Club Pro+"
```

---

### Task 3: Fix price spacing on Dashboard Home

**Files:**
- Modify: `client/src/views/dashboard/DashboardHome.vue:38`

- [ ] **Step 1: Fix upsell price spacing (line 38)**

Change:
```html
<div class="metric-value mb-6">$4.99<span class="text-sm text-secondary font-body">/{{ $t('subscription.per_month') }}</span></div>
```
to:
```html
<div class="metric-value mb-6">$4.99 <span class="text-sm text-secondary font-body">/ {{ $t('subscription.per_month') }}</span></div>
```

(Added space after `$4.99` and space after `/`)

- [ ] **Step 2: Commit**

```bash
git add client/src/views/dashboard/DashboardHome.vue
git commit -m "fix: add spacing to price display on dashboard home"
```

---

### Task 4: Fix price spacing and add Coming Soon section on Subscription View

**Files:**
- Modify: `client/src/views/dashboard/SubscriptionView.vue:35,75-83`

- [ ] **Step 1: Fix price spacing (line 35)**

Change:
```html
{{ auth.isProPlus ? '$4.99' : '$0.00' }}<span class="text-sm text-secondary font-body font-normal">/{{ $t('subscription.per_month') }}</span>
```
to:
```html
{{ auth.isProPlus ? '$4.99' : '$0.00' }} <span class="text-sm text-secondary font-body font-normal">/ {{ $t('subscription.per_month') }}</span>
```

(Added space before `<span>` and space after `/`)

- [ ] **Step 2: Add Coming Soon section after the Pro+ features list (after line 83)**

Change the features-list section from:
```html
<div class="features-list glass-card p-6 rounded-lg">
  <h3 class="mb-4">{{ $t('subscription.pro_features') }}</h3>
  <ul class="check-list text-secondary">
    <li>{{ $t('subscription.unlimited_sessions') }}</li>
    <li>{{ $t('subscription.shot_timer') }}</li>
    <li>{{ $t('subscription.export_excel') }}</li>
    <li>{{ $t('subscription.custom_disciplines') }}</li>
  </ul>
</div>
```
to:
```html
<div class="features-list glass-card p-6 rounded-lg">
  <h3 class="mb-4">{{ $t('subscription.pro_features') }}</h3>
  <ul class="check-list text-secondary">
    <li>{{ $t('subscription.unlimited_sessions') }}</li>
    <li>{{ $t('subscription.shot_timer') }}</li>
    <li>{{ $t('subscription.export_excel') }}</li>
    <li>{{ $t('subscription.custom_disciplines') }}</li>
  </ul>

  <h4 class="coming-soon-title mt-6 mb-3">{{ $t('subscription.coming_soon') }}</h4>
  <ul class="check-list check-list-muted text-secondary">
    <li>{{ $t('subscription.coming_soon_sync') }}</li>
    <li>{{ $t('subscription.coming_soon_analytics') }}</li>
  </ul>
</div>
```

- [ ] **Step 3: Add CSS for the coming soon section**

In the `<style scoped>` section, after the `.check-list li::before` rule (around line 174), add:

```css
.coming-soon-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.mt-6 { margin-top: 1.5rem; }
.mb-3 { margin-bottom: 0.75rem; }

.check-list-muted li {
  opacity: 0.6;
}
.check-list-muted li::before {
  content: '○';
}
```

- [ ] **Step 4: Visually verify in browser**

Navigate to `/dashboard/subscription` (logged in) and check:
- Price shows `$4.99 / month` (with space)
- Pro+ features list shows the 4 real features
- "Coming Soon" section appears below with muted styling
- Coming soon items show with `○` bullets instead of `✓`

- [ ] **Step 5: Commit**

```bash
git add client/src/views/dashboard/SubscriptionView.vue
git commit -m "fix: add spacing to subscription price and add coming soon features section"
```

---

### Task 5: Final verification

- [ ] **Step 1: Run the production build**

Run: `cd client && npm run build 2>&1 | tail -10`

Expected: Build succeeds with no errors.

- [ ] **Step 2: Verify all locale files are valid JSON**

Run: `for f in client/src/i18n/locales/*.json; do echo -n "$f: "; node -e "JSON.parse(require('fs').readFileSync('$f','utf8')); console.log('OK')" 2>&1; done`

Expected: All 8 files print "OK".

- [ ] **Step 3: Commit any remaining changes (if any)**

If there are uncommitted changes from visual verification fixes:

```bash
git add -A
git commit -m "chore: final cleanup for pricing display fixes"
```
