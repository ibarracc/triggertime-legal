# Landing Page Updates & Admin Sync Data Management — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Update the TriggerTime landing page to reflect newly shipped features and add an admin panel view for browsing/editing synced user data.

**Architecture:** Part 1 (Tasks 1–4) modifies the Vue SPA landing page and i18n files — pure frontend. Part 2 (Tasks 5–10) adds a CakePHP admin controller with tests, then a Vue admin view with tabs/modals. Part 2 depends on the sync entity tables from the `cloud-sync-new-entities` plan being migrated first.

**Tech Stack:** CakePHP 5.3 (PHP 8.2+), Vue 3 Composition API, Vite, vue-i18n, Axios, PHPUnit

**Dependency:** Tasks 5–10 require the sync entity tables (`sync_weapons`, `sync_ammo`, `sync_competitions`, `sync_competition_reminders`, `sync_ammo_transactions`) to exist. Run the `cloud-sync-new-entities` plan first, or at minimum Tasks 1–5 of that plan (the migrations + models).

---

## File Structure

**Create:**
- `src/Controller/Api/V1/Admin/SyncDataController.php` — Admin CRUD for sync entities
- `tests/TestCase/Controller/Api/V1/Admin/SyncDataControllerTest.php` — Backend tests
- `tests/Fixture/SyncWeaponsFixture.php` — Test fixture (if not already created by cloud-sync plan)
- `client/src/views/admin/SyncDataView.vue` — Admin sync data view

**Modify:**
- `client/src/views/landing/LandingPage.vue` — SVG icons, new cards, remove coming-soon, pricing update, hero fix
- `client/src/i18n/locales/en.json` — New i18n keys (landing + admin)
- `client/src/i18n/locales/es.json` — Spanish translations
- `client/src/i18n/locales/de.json` — German translations
- `client/src/i18n/locales/ca.json` — Catalan translations
- `client/src/i18n/locales/eu.json` — Basque translations
- `client/src/i18n/locales/fr.json` — French translations
- `client/src/i18n/locales/gl.json` — Galician translations
- `client/src/i18n/locales/pt.json` — Portuguese translations
- `client/src/api/admin.js` — Add sync data API methods
- `client/src/router/index.js` — Add sync-data admin route
- `client/src/views/admin/AdminLayout.vue` — Add "Sync Data" nav tab
- `config/routes.php` — Add admin sync-data routes

---

## Part 1: Landing Page

### Task 1: Replace emoji icons with inline SVGs in LandingPage.vue

**Files:**
- Modify: `client/src/views/landing/LandingPage.vue`

- [ ] **Step 1: Replace all emoji spans in the Core Features section with inline SVGs**

In `client/src/views/landing/LandingPage.vue`, replace the 4 core feature card headers. Change each `<span class="text-2xl mr-2">EMOJI</span>` to an inline SVG.

Replace the entire `<!-- Core Features Section -->` content (lines 35–69) with:

```html
    <!-- Core Features Section -->
    <section id="features" class="features py-24 bg-surface">
      <div class="container text-center">
        <h2 class="section-title mb-16">{{ $t('landing.features_title') }}</h2>
        
        <div class="features-grid">
          <AppCard hoverable>
            <template #header><svg class="icon-feature" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg> {{ $t('landing.feat_tracking_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_tracking_desc') }}
            </p>
          </AppCard>
          
          <AppCard hoverable>
            <template #header><svg class="icon-feature" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg> {{ $t('landing.feat_private_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_private_desc') }}
            </p>
          </AppCard>
          
          <AppCard hoverable>
            <template #header><svg class="icon-feature" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" x2="14" y1="2" y2="2"/><line x1="12" x2="15" y1="14" y2="11"/><circle cx="12" cy="14" r="8"/></svg> {{ $t('landing.feat_stopwatch_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_stopwatch_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable>
            <template #header><svg class="icon-feature" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg> {{ $t('landing.feat_issf_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_issf_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable>
            <template #header><svg class="icon-feature" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg> {{ $t('landing.feat_inventory_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_inventory_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable>
            <template #header><svg class="icon-feature" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5C7 4 9 8 12 8s5-4 7.5-4a2.5 2.5 0 0 1 0 5H18"/><path d="M18 15H6"/><path d="M10 22h4"/><path d="M14 22a8 8 0 0 0 1.73-15"/><path d="M10 22a8 8 0 0 1-1.73-15"/></svg> {{ $t('landing.feat_competitions_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_competitions_desc') }}
            </p>
          </AppCard>
        </div>
      </div>
    </section>
```

- [ ] **Step 2: Replace all emoji spans in the Premium Features section with inline SVGs**

Replace the entire `<!-- Premium Features Section -->` content (lines 72–121) with:

```html
    <!-- Premium Features Section -->
    <section id="premium" class="premium py-24">
      <div class="container text-center">
        <AppBadge variant="warning" class="mb-4">{{ $t('dashboard.upgrade_pro') }}</AppBadge>
        <h2 class="section-title mb-16">{{ $t('landing.premium_title') }}</h2>
        
        <div class="features-grid">
          <AppCard hoverable class="border-warning-subtle">
            <template #header><svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg> {{ $t('landing.prem_timer_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_timer_desc') }}
            </p>
          </AppCard>
          
          <AppCard hoverable class="border-warning-subtle">
            <template #header><svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/></svg> {{ $t('landing.prem_excel_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_excel_desc') }}
            </p>
          </AppCard>
          
          <AppCard hoverable class="border-warning-subtle">
            <template #header><svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12c-2-2.67-4-4-6.5-4a3.5 3.5 0 1 0 0 7h.5"/><path d="M12 12c2-2.67 4-4 6.5-4a3.5 3.5 0 1 1 0 7H18"/></svg> {{ $t('landing.prem_unlimited_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_unlimited_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable class="border-warning-subtle">
            <template #header><svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg> {{ $t('landing.prem_custom_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_custom_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable class="border-warning-subtle">
            <template #header><svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg> {{ $t('landing.prem_sync_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_sync_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable class="border-warning-subtle">
            <template #header><svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="22" x2="18" y1="12" y2="12"/><line x1="6" x2="2" y1="12" y2="12"/><line x1="12" x2="12" y1="6" y2="2"/><line x1="12" x2="12" y1="22" y2="18"/></svg> {{ $t('landing.prem_ammo_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_ammo_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable class="border-warning-subtle coming-soon-card">
            <template #header><svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M7 16l4-8 4 5 4-3"/></svg> {{ $t('landing.prem_analytics_title') }} <span class="coming-soon-badge">{{ $t('subscription.coming_soon') }}</span></template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_analytics_desc') }}
            </p>
          </AppCard>
        </div>
      </div>
    </section>
```

- [ ] **Step 3: Add the `.icon-feature` CSS class**

In the `<style scoped>` section, add after the `.m-0` rule (line 283):

```css
.icon-feature {
  width: 24px;
  height: 24px;
  display: inline-block;
  vertical-align: middle;
  margin-right: 8px;
  flex-shrink: 0;
}
```

- [ ] **Step 4: Verify locally**

Run: `cd client && npm run dev`

Open `http://localhost:5173` in a browser. Verify:
- Core features section shows 6 cards with SVG icons (no emoji)
- Premium section shows 7 cards with SVG icons in warning color
- Cloud Sync card is fully visible (not dimmed)
- Only Analytics & Charts has the "Coming Soon" badge
- All icons render at consistent 24×24 size

- [ ] **Step 5: Commit**

```bash
git add client/src/views/landing/LandingPage.vue
git commit -m "feat(landing): replace emoji icons with inline SVGs, add new feature cards"
```

---

### Task 2: Update Pro+ pricing card and fix hero background

**Files:**
- Modify: `client/src/views/landing/LandingPage.vue`

- [ ] **Step 1: Add Cloud Sync and Ammo Tracking to the Pro+ pricing features list**

In `LandingPage.vue`, find the Pro+ pricing card `<ul class="pricing-features">` (around line 155–161 after Task 1 changes). Add two new items after the existing 4:

```html
                <li>✓ {{ $t('landing.prem_sync_title') }}</li>
                <li>✓ {{ $t('landing.prem_ammo_title') }}</li>
```

The full list becomes:
```html
              <ul class="pricing-features mb-8 text-left">
                <li>✓ {{ $t('landing.prem_unlimited_title') }}</li>
                <li>✓ {{ $t('landing.prem_timer_title') }}</li>
                <li>✓ {{ $t('landing.prem_excel_title') }}</li>
                <li>✓ {{ $t('landing.prem_custom_title') }}</li>
                <li>✓ {{ $t('landing.prem_sync_title') }}</li>
                <li>✓ {{ $t('landing.prem_ammo_title') }}</li>
              </ul>
```

- [ ] **Step 2: Remove `background-attachment: fixed` from the hero CSS**

In the `<style scoped>` section, find the `.hero` rule and remove `background-attachment: fixed;`. The rule should look like:

```css
.hero {
  position: relative;
  background-color: var(--bg-base);
  background-image: linear-gradient(to bottom, rgba(10, 10, 15, 0.75), rgba(10, 10, 15, 0.95)), url('https://images.unsplash.com/photo-1584984647365-18151480f745?q=80&w=2600&auto=format&fit=crop');
  background-size: cover;
  background-position: center;
}
```

- [ ] **Step 3: Verify locally**

Open the landing page. Verify:
- Pro+ pricing card lists 6 features (including Cloud Sync and Ammo Tracking)
- Hero background scrolls normally (no parallax effect)
- On mobile viewport (375px), layout still looks good

- [ ] **Step 4: Commit**

```bash
git add client/src/views/landing/LandingPage.vue
git commit -m "feat(landing): add sync and ammo to Pro+ pricing, remove hero parallax"
```

---

### Task 3: Add i18n keys for new landing page features (English)

**Files:**
- Modify: `client/src/i18n/locales/en.json`

- [ ] **Step 1: Add new landing page keys to en.json**

In `client/src/i18n/locales/en.json`, inside the `"landing"` object, add these keys after `"prem_analytics_desc"` (line 68):

```json
        "feat_inventory_title": "Inventory Management",
        "feat_inventory_desc": "Track your firearms and ammunition in one place.",
        "feat_competitions_title": "Competition Calendar",
        "feat_competitions_desc": "Plan and manage your competition schedule with status tracking and countdown timers.",
        "prem_ammo_title": "Ammo Tracking",
        "prem_ammo_desc": "Track ammo purchases, usage per session, and stock levels.",
```

- [ ] **Step 2: Add admin sync data keys to en.json**

In `client/src/i18n/locales/en.json`, find the end of the `"admin"` object (or add one if it doesn't exist at the top level). Add:

```json
        "sync_data_title": "Sync Data",
        "sync_data_subtitle": "Browse and edit synced user data",
        "select_user": "Select User",
        "tab_weapons": "Weapons",
        "tab_ammo": "Ammo",
        "tab_competitions": "Competitions",
        "tab_reminders": "Reminders",
        "tab_transactions": "Transactions",
        "edit_sync_record": "Edit Record",
        "delete_sync_record": "Delete Record",
        "delete_sync_confirm": "Are you sure you want to delete this record? It will be removed from the user's device on next sync.",
        "no_sync_data": "No synced data found for this user.",
        "sync_modified_at": "Last Modified"
```

- [ ] **Step 3: Verify the JSON is valid**

Run: `cd client && node -e "JSON.parse(require('fs').readFileSync('src/i18n/locales/en.json','utf8')); console.log('Valid JSON')"`

Expected: `Valid JSON`

- [ ] **Step 4: Commit**

```bash
git add client/src/i18n/locales/en.json
git commit -m "feat(i18n): add English keys for landing page features and admin sync data"
```

---

### Task 4: Add i18n keys for other 7 locales

**Files:**
- Modify: `client/src/i18n/locales/es.json`
- Modify: `client/src/i18n/locales/de.json`
- Modify: `client/src/i18n/locales/ca.json`
- Modify: `client/src/i18n/locales/eu.json`
- Modify: `client/src/i18n/locales/fr.json`
- Modify: `client/src/i18n/locales/gl.json`
- Modify: `client/src/i18n/locales/pt.json`

- [ ] **Step 1: Add Spanish (es.json) translations**

In `client/src/i18n/locales/es.json`, inside the `"landing"` object, add:

```json
        "feat_inventory_title": "Gestión de Inventario",
        "feat_inventory_desc": "Gestiona tus armas y munición en un solo lugar.",
        "feat_competitions_title": "Calendario de Competiciones",
        "feat_competitions_desc": "Planifica y gestiona tu calendario de competiciones con seguimiento de estado y cuentas atrás.",
        "prem_ammo_title": "Seguimiento de Munición",
        "prem_ammo_desc": "Registra compras de munición, uso por sesión y niveles de stock.",
```

In the `"admin"` object, add:

```json
        "sync_data_title": "Datos Sincronizados",
        "sync_data_subtitle": "Explorar y editar datos sincronizados de usuarios",
        "select_user": "Seleccionar Usuario",
        "tab_weapons": "Armas",
        "tab_ammo": "Munición",
        "tab_competitions": "Competiciones",
        "tab_reminders": "Recordatorios",
        "tab_transactions": "Transacciones",
        "edit_sync_record": "Editar Registro",
        "delete_sync_record": "Eliminar Registro",
        "delete_sync_confirm": "¿Estás seguro de que quieres eliminar este registro? Se eliminará del dispositivo del usuario en la próxima sincronización.",
        "no_sync_data": "No se encontraron datos sincronizados para este usuario.",
        "sync_modified_at": "Última Modificación"
```

- [ ] **Step 2: Add German (de.json) translations**

In `client/src/i18n/locales/de.json`, inside the `"landing"` object, add:

```json
        "feat_inventory_title": "Inventarverwaltung",
        "feat_inventory_desc": "Verwalten Sie Ihre Waffen und Munition an einem Ort.",
        "feat_competitions_title": "Wettkampfkalender",
        "feat_competitions_desc": "Planen und verwalten Sie Ihren Wettkampfkalender mit Statusverfolgung und Countdown-Timern.",
        "prem_ammo_title": "Munitionsverfolgung",
        "prem_ammo_desc": "Verfolgen Sie Munitionskäufe, Verbrauch pro Sitzung und Lagerbestände.",
```

In the `"admin"` object, add:

```json
        "sync_data_title": "Synchronisierte Daten",
        "sync_data_subtitle": "Synchronisierte Benutzerdaten durchsuchen und bearbeiten",
        "select_user": "Benutzer auswählen",
        "tab_weapons": "Waffen",
        "tab_ammo": "Munition",
        "tab_competitions": "Wettkämpfe",
        "tab_reminders": "Erinnerungen",
        "tab_transactions": "Transaktionen",
        "edit_sync_record": "Eintrag bearbeiten",
        "delete_sync_record": "Eintrag löschen",
        "delete_sync_confirm": "Möchten Sie diesen Eintrag wirklich löschen? Er wird beim nächsten Sync vom Gerät des Benutzers entfernt.",
        "no_sync_data": "Keine synchronisierten Daten für diesen Benutzer gefunden.",
        "sync_modified_at": "Zuletzt geändert"
```

- [ ] **Step 3: Add Catalan (ca.json) translations**

In `client/src/i18n/locales/ca.json`, inside the `"landing"` object, add:

```json
        "feat_inventory_title": "Gestió d'Inventari",
        "feat_inventory_desc": "Gestiona les teves armes i munició en un sol lloc.",
        "feat_competitions_title": "Calendari de Competicions",
        "feat_competitions_desc": "Planifica i gestiona el teu calendari de competicions amb seguiment d'estat i comptes enrere.",
        "prem_ammo_title": "Seguiment de Munició",
        "prem_ammo_desc": "Registra compres de munició, ús per sessió i nivells d'estoc.",
```

In the `"admin"` object, add the same keys as Spanish (admin panel is admin-only, Spanish is close enough for Catalan admin users):

```json
        "sync_data_title": "Dades Sincronitzades",
        "sync_data_subtitle": "Explorar i editar dades sincronitzades d'usuaris",
        "select_user": "Seleccionar Usuari",
        "tab_weapons": "Armes",
        "tab_ammo": "Munició",
        "tab_competitions": "Competicions",
        "tab_reminders": "Recordatoris",
        "tab_transactions": "Transaccions",
        "edit_sync_record": "Editar Registre",
        "delete_sync_record": "Eliminar Registre",
        "delete_sync_confirm": "Estàs segur que vols eliminar aquest registre? S'eliminarà del dispositiu de l'usuari a la propera sincronització.",
        "no_sync_data": "No s'han trobat dades sincronitzades per a aquest usuari.",
        "sync_modified_at": "Última Modificació"
```

- [ ] **Step 4: Add Basque (eu.json) translations**

In `client/src/i18n/locales/eu.json`, inside the `"landing"` object, add:

```json
        "feat_inventory_title": "Inbentario Kudeaketa",
        "feat_inventory_desc": "Kudeatu zure armak eta munizioa leku bakar batean.",
        "feat_competitions_title": "Lehiaketa Egutegia",
        "feat_competitions_desc": "Planifikatu eta kudeatu zure lehiaketa egutegia egoera jarraipenarekin eta atzerako kontaketekin.",
        "prem_ammo_title": "Munizio Jarraipena",
        "prem_ammo_desc": "Erregistratu munizio erosketak, saio bakoitzeko erabilera eta stock mailak.",
```

In the `"admin"` object, add:

```json
        "sync_data_title": "Sinkronizatutako Datuak",
        "sync_data_subtitle": "Erabiltzaileen sinkronizatutako datuak arakatu eta editatu",
        "select_user": "Erabiltzailea Hautatu",
        "tab_weapons": "Armak",
        "tab_ammo": "Munizioa",
        "tab_competitions": "Lehiaketak",
        "tab_reminders": "Oroigarriak",
        "tab_transactions": "Transakzioak",
        "edit_sync_record": "Erregistroa Editatu",
        "delete_sync_record": "Erregistroa Ezabatu",
        "delete_sync_confirm": "Ziur zaude erregistro hau ezabatu nahi duzula? Erabiltzailearen gailutik hurrengo sinkronizazioan ezabatuko da.",
        "no_sync_data": "Ez dira sinkronizatutako daturik aurkitu erabiltzaile honentzat.",
        "sync_modified_at": "Azken Aldaketa"
```

- [ ] **Step 5: Add French (fr.json) translations**

In `client/src/i18n/locales/fr.json`, inside the `"landing"` object, add:

```json
        "feat_inventory_title": "Gestion d'Inventaire",
        "feat_inventory_desc": "Gérez vos armes et munitions en un seul endroit.",
        "feat_competitions_title": "Calendrier des Compétitions",
        "feat_competitions_desc": "Planifiez et gérez votre calendrier de compétitions avec suivi de statut et comptes à rebours.",
        "prem_ammo_title": "Suivi des Munitions",
        "prem_ammo_desc": "Suivez les achats de munitions, l'utilisation par session et les niveaux de stock.",
```

In the `"admin"` object, add:

```json
        "sync_data_title": "Données Synchronisées",
        "sync_data_subtitle": "Parcourir et modifier les données synchronisées des utilisateurs",
        "select_user": "Sélectionner un Utilisateur",
        "tab_weapons": "Armes",
        "tab_ammo": "Munitions",
        "tab_competitions": "Compétitions",
        "tab_reminders": "Rappels",
        "tab_transactions": "Transactions",
        "edit_sync_record": "Modifier l'Enregistrement",
        "delete_sync_record": "Supprimer l'Enregistrement",
        "delete_sync_confirm": "Êtes-vous sûr de vouloir supprimer cet enregistrement ? Il sera supprimé de l'appareil de l'utilisateur lors de la prochaine synchronisation.",
        "no_sync_data": "Aucune donnée synchronisée trouvée pour cet utilisateur.",
        "sync_modified_at": "Dernière Modification"
```

- [ ] **Step 6: Add Galician (gl.json) translations**

In `client/src/i18n/locales/gl.json`, inside the `"landing"` object, add:

```json
        "feat_inventory_title": "Xestión de Inventario",
        "feat_inventory_desc": "Xestiona as túas armas e munición nun só lugar.",
        "feat_competitions_title": "Calendario de Competicións",
        "feat_competitions_desc": "Planifica e xestiona o teu calendario de competicións con seguimento de estado e contas atrás.",
        "prem_ammo_title": "Seguimento de Munición",
        "prem_ammo_desc": "Rexistra compras de munición, uso por sesión e niveis de stock.",
```

In the `"admin"` object, add:

```json
        "sync_data_title": "Datos Sincronizados",
        "sync_data_subtitle": "Explorar e editar datos sincronizados de usuarios",
        "select_user": "Seleccionar Usuario",
        "tab_weapons": "Armas",
        "tab_ammo": "Munición",
        "tab_competitions": "Competicións",
        "tab_reminders": "Recordatorios",
        "tab_transactions": "Transaccións",
        "edit_sync_record": "Editar Rexistro",
        "delete_sync_record": "Eliminar Rexistro",
        "delete_sync_confirm": "Estás seguro de que queres eliminar este rexistro? Eliminarase do dispositivo do usuario na próxima sincronización.",
        "no_sync_data": "Non se atoparon datos sincronizados para este usuario.",
        "sync_modified_at": "Última Modificación"
```

- [ ] **Step 7: Add Portuguese (pt.json) translations**

In `client/src/i18n/locales/pt.json`, inside the `"landing"` object, add:

```json
        "feat_inventory_title": "Gestão de Inventário",
        "feat_inventory_desc": "Gerencie suas armas e munição em um só lugar.",
        "feat_competitions_title": "Calendário de Competições",
        "feat_competitions_desc": "Planeje e gerencie seu calendário de competições com acompanhamento de status e contagens regressivas.",
        "prem_ammo_title": "Rastreamento de Munição",
        "prem_ammo_desc": "Rastreie compras de munição, uso por sessão e níveis de estoque.",
```

In the `"admin"` object, add:

```json
        "sync_data_title": "Dados Sincronizados",
        "sync_data_subtitle": "Explorar e editar dados sincronizados de usuários",
        "select_user": "Selecionar Usuário",
        "tab_weapons": "Armas",
        "tab_ammo": "Munição",
        "tab_competitions": "Competições",
        "tab_reminders": "Lembretes",
        "tab_transactions": "Transações",
        "edit_sync_record": "Editar Registro",
        "delete_sync_record": "Excluir Registro",
        "delete_sync_confirm": "Tem certeza de que deseja excluir este registro? Ele será removido do dispositivo do usuário na próxima sincronização.",
        "no_sync_data": "Nenhum dado sincronizado encontrado para este usuário.",
        "sync_modified_at": "Última Modificação"
```

- [ ] **Step 8: Validate all JSON files**

Run: `cd client && for f in src/i18n/locales/*.json; do node -e "JSON.parse(require('fs').readFileSync('$f','utf8')); console.log('OK: $f')" || echo "FAIL: $f"; done`

Expected: All 8 files print "OK"

- [ ] **Step 9: Commit**

```bash
git add client/src/i18n/locales/
git commit -m "feat(i18n): add translations for 7 locales (landing features + admin sync data)"
```

---

## Part 2: Admin Sync Data Management

### Task 5: Add admin sync-data routes to CakePHP

**Files:**
- Modify: `config/routes.php`

- [ ] **Step 1: Add the sync-data routes inside the admin prefix**

In `config/routes.php`, inside the admin scope (after line 119 `$admin->resources('Devices');`), add:

```php
                $admin->get('/sync-data', ['controller' => 'SyncData', 'action' => 'index']);
                $admin->put('/sync-data/{id}', ['controller' => 'SyncData', 'action' => 'edit'])->setPass(['id']);
                $admin->delete('/sync-data/{id}', ['controller' => 'SyncData', 'action' => 'delete'])->setPass(['id']);
```

- [ ] **Step 2: Commit**

```bash
git add config/routes.php
git commit -m "feat(routes): add admin sync-data endpoints"
```

---

### Task 6: Create SyncDataController

**Files:**
- Create: `src/Controller/Api/V1/Admin/SyncDataController.php`

- [ ] **Step 1: Create the controller file**

Create `src/Controller/Api/V1/Admin/SyncDataController.php`:

```php
<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\DateTime;
use Cake\Http\Response;

class SyncDataController extends AppController
{
    private const TYPE_MAP = [
        'weapons' => 'SyncWeapons',
        'ammo' => 'SyncAmmo',
        'competitions' => 'SyncCompetitions',
        'competition_reminders' => 'SyncCompetitionReminders',
        'ammo_transactions' => 'SyncAmmoTransactions',
    ];

    private const EDITABLE_FIELDS = [
        'weapons' => ['name', 'caliber', 'notes', 'is_favorite', 'is_archived'],
        'ammo' => ['brand', 'name', 'caliber', 'grain_weight', 'current_stock', 'notes', 'is_archived'],
        'competitions' => ['name', 'date', 'end_date', 'location', 'discipline_id', 'status', 'notes'],
        'competition_reminders' => ['reminder_date', 'type'],
        'ammo_transactions' => ['type', 'quantity', 'notes'],
    ];

    private const DIRECT_OWNERSHIP = ['weapons', 'ammo', 'competitions'];

    private const VIA_PARENT = [
        'competition_reminders' => ['parent_table' => 'SyncCompetitions', 'fk' => 'competition_uuid'],
        'ammo_transactions' => ['parent_table' => 'SyncAmmo', 'fk' => 'ammo_uuid'],
    ];

    private function ensureAdmin(): void
    {
        $payload = $this->request->getAttribute('jwt_payload');
        if (!isset($payload['role']) || $payload['role'] !== 'admin') {
            throw new ForbiddenException('Admin access required');
        }
    }

    private function resolveTable(string $type): \Cake\ORM\Table
    {
        if (!isset(self::TYPE_MAP[$type])) {
            throw new BadRequestException('Invalid type: ' . $type . '. Valid types: ' . implode(', ', array_keys(self::TYPE_MAP)));
        }

        return $this->fetchTable(self::TYPE_MAP[$type]);
    }

    public function index(): Response
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['get']);

        $type = $this->request->getQuery('type');
        $userId = $this->request->getQuery('user_id');

        if (!$type || !$userId) {
            throw new BadRequestException('Both type and user_id query parameters are required');
        }

        $table = $this->resolveTable($type);

        if (in_array($type, self::DIRECT_OWNERSHIP)) {
            $records = $table->find()
                ->where([
                    $table->getAlias() . '.user_id' => $userId,
                    $table->getAlias() . '.deleted_at IS' => null,
                ])
                ->orderBy([$table->getAlias() . '.modified_at' => 'DESC'])
                ->all();
        } else {
            $parentConfig = self::VIA_PARENT[$type];
            $parentTable = $this->fetchTable($parentConfig['parent_table']);
            $fk = $parentConfig['fk'];

            $parentIds = $parentTable->find()
                ->where(['user_id' => $userId])
                ->select(['id'])
                ->all()
                ->extract('id')
                ->toArray();

            if (empty($parentIds)) {
                return $this->response->withType('application/json')->withStringBody((string)json_encode([
                    'success' => true,
                    'records' => [],
                ]));
            }

            $records = $table->find()
                ->where([
                    $fk . ' IN' => $parentIds,
                    'deleted_at IS' => null,
                ])
                ->orderBy(['modified_at' => 'DESC'])
                ->all();
        }

        return $this->response->withType('application/json')->withStringBody((string)json_encode([
            'success' => true,
            'records' => $records,
        ]));
    }

    public function edit(string $id): Response
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['put', 'patch']);

        $data = $this->request->getData();
        $type = $data['type'] ?? $this->request->getQuery('type');

        if (!$type) {
            throw new BadRequestException('type parameter is required');
        }

        $table = $this->resolveTable($type);

        try {
            $record = $table->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Record not found');
        }

        $allowedFields = self::EDITABLE_FIELDS[$type];
        $patchData = array_intersect_key($data, array_flip($allowedFields));
        $patchData['modified_at'] = DateTime::now();

        $record = $table->patchEntity($record, $patchData, ['fields' => array_merge($allowedFields, ['modified_at'])]);

        if ($table->save($record)) {
            return $this->response->withType('application/json')->withStringBody((string)json_encode([
                'success' => true,
                'record' => $record,
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody((string)json_encode([
            'success' => false,
            'errors' => $record->getErrors(),
        ]));
    }

    public function delete(string $id): Response
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['delete']);

        $type = $this->request->getQuery('type');

        if (!$type) {
            throw new BadRequestException('type query parameter is required');
        }

        $table = $this->resolveTable($type);

        try {
            $record = $table->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Record not found');
        }

        $now = DateTime::now();
        $record->set('deleted_at', $now);
        $record->set('modified_at', $now);

        if ($table->save($record)) {
            return $this->response->withType('application/json')->withStringBody((string)json_encode([
                'success' => true,
                'message' => 'Record soft-deleted',
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody((string)json_encode([
            'success' => false,
            'message' => 'Failed to delete record',
        ]));
    }
}
```

- [ ] **Step 2: Run code sniffer**

Run: `composer cs-check`

Fix any style issues reported.

- [ ] **Step 3: Commit**

```bash
git add src/Controller/Api/V1/Admin/SyncDataController.php
git commit -m "feat(admin): add SyncDataController with index/edit/delete"
```

---

### Task 7: Write SyncDataController tests

**Files:**
- Create: `tests/TestCase/Controller/Api/V1/Admin/SyncDataControllerTest.php`

- [ ] **Step 1: Create the test file**

Create `tests/TestCase/Controller/Api/V1/Admin/SyncDataControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Admin;

use App\Service\JwtService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SyncDataControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.SyncWeapons',
        'app.SyncAmmo',
        'app.SyncCompetitions',
        'app.SyncCompetitionReminders',
        'app.SyncAmmoTransactions',
    ];

    private string $adminUserId = 'c3792a3c-af61-479e-aaa3-16e763aacbf8';
    private string $regularUserId = 'f2f2f2f2-a3a3-4b4b-8c5c-d6d6d6d6d6d2';

    private function getAdminToken(): string
    {
        $jwt = new JwtService();

        return $jwt->generateToken([
            'sub' => $this->adminUserId,
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
    }

    private function getUserToken(): string
    {
        $jwt = new JwtService();

        return $jwt->generateToken([
            'sub' => $this->regularUserId,
            'email' => 'user2@example.com',
            'role' => 'user',
        ]);
    }

    private function configureAdminRequest(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getAdminToken(),
            ],
        ]);
    }

    private function configureUserRequest(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getUserToken(),
            ],
        ]);
    }

    public function testIndexRequiresAdmin(): void
    {
        $this->configureUserRequest();
        $this->get('/api/v1/admin/sync-data?user_id=' . $this->adminUserId . '&type=weapons');
        $this->assertResponseCode(403);
    }

    public function testIndexRequiresUserIdAndType(): void
    {
        $this->configureAdminRequest();
        $this->get('/api/v1/admin/sync-data');
        $this->assertResponseCode(400);
    }

    public function testIndexInvalidTypeReturns400(): void
    {
        $this->configureAdminRequest();
        $this->get('/api/v1/admin/sync-data?user_id=' . $this->adminUserId . '&type=invalid');
        $this->assertResponseCode(400);
    }

    public function testIndexWeaponsSuccess(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'aaaa1111-bbbb-cccc-dddd-eeeeeeee0001',
            'user_id' => $this->adminUserId,
            'device_uuid' => 'test-device',
            'name' => 'Test Pistol',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ]);
        $table->save($weapon);

        $this->configureAdminRequest();
        $this->get('/api/v1/admin/sync-data?user_id=' . $this->adminUserId . '&type=weapons');
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertCount(1, $body['records']);
        $this->assertEquals('Test Pistol', $body['records'][0]['name']);
    }

    public function testEditUpdatesFieldsAndModifiedAt(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'aaaa1111-bbbb-cccc-dddd-eeeeeeee0002',
            'user_id' => $this->adminUserId,
            'device_uuid' => 'test-device',
            'name' => 'Old Name',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ]);
        $table->save($weapon);

        $this->configureAdminRequest();
        $this->put('/api/v1/admin/sync-data/aaaa1111-bbbb-cccc-dddd-eeeeeeee0002', json_encode([
            'type' => 'weapons',
            'name' => 'Updated Name',
            'caliber' => '.45 ACP',
        ]));
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Updated Name', $body['record']['name']);
        $this->assertEquals('.45 ACP', $body['record']['caliber']);
        $this->assertNotEquals('2026-04-24 12:00:00', $body['record']['modified_at']);
    }

    public function testEditRejectsNonEditableFields(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'aaaa1111-bbbb-cccc-dddd-eeeeeeee0003',
            'user_id' => $this->adminUserId,
            'device_uuid' => 'test-device',
            'name' => 'Original',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ]);
        $table->save($weapon);

        $this->configureAdminRequest();
        $this->put('/api/v1/admin/sync-data/aaaa1111-bbbb-cccc-dddd-eeeeeeee0003', json_encode([
            'type' => 'weapons',
            'user_id' => $this->regularUserId,
        ]));
        $this->assertResponseOk();

        $updated = $table->get('aaaa1111-bbbb-cccc-dddd-eeeeeeee0003');
        $this->assertEquals($this->adminUserId, $updated->user_id);
    }

    public function testDeleteSoftDeletes(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'aaaa1111-bbbb-cccc-dddd-eeeeeeee0004',
            'user_id' => $this->adminUserId,
            'device_uuid' => 'test-device',
            'name' => 'To Delete',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ]);
        $table->save($weapon);

        $this->configureAdminRequest();
        $this->delete('/api/v1/admin/sync-data/aaaa1111-bbbb-cccc-dddd-eeeeeeee0004?type=weapons');
        $this->assertResponseOk();

        $deleted = $table->get('aaaa1111-bbbb-cccc-dddd-eeeeeeee0004');
        $this->assertNotNull($deleted->deleted_at);
        $this->assertNotEquals('2026-04-24 12:00:00', (string)$deleted->modified_at);
    }

    public function testDeleteNotFoundReturns404(): void
    {
        $this->configureAdminRequest();
        $this->delete('/api/v1/admin/sync-data/nonexistent-uuid?type=weapons');
        $this->assertResponseCode(404);
    }
}
```

- [ ] **Step 2: Run the tests**

Run: `vendor/bin/phpunit tests/TestCase/Controller/Api/V1/Admin/SyncDataControllerTest.php`

Expected: All tests pass (assuming sync entity tables + fixtures exist from cloud-sync plan).

- [ ] **Step 3: Commit**

```bash
git add tests/TestCase/Controller/Api/V1/Admin/SyncDataControllerTest.php
git commit -m "test(admin): add SyncDataController tests"
```

---

### Task 8: Add sync data API methods to frontend admin module

**Files:**
- Modify: `client/src/api/admin.js`

- [ ] **Step 1: Add the three sync data methods**

In `client/src/api/admin.js`, add before the closing `}` of the `adminApi` object:

```javascript

    // Sync Data
    getSyncData: (userId, type) => api.get('/admin/sync-data', { params: { user_id: userId, type } }),
    updateSyncData: (id, data) => api.put(`/admin/sync-data/${id}`, data),
    deleteSyncData: (id, type) => api.delete(`/admin/sync-data/${id}`, { params: { type } }),
```

- [ ] **Step 2: Commit**

```bash
git add client/src/api/admin.js
git commit -m "feat(admin): add sync data API methods"
```

---

### Task 9: Create SyncDataView.vue

**Files:**
- Create: `client/src/views/admin/SyncDataView.vue`

- [ ] **Step 1: Create the view file**

Create `client/src/views/admin/SyncDataView.vue`:

```vue
<template>
  <div class="admin-sync-data">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold font-heading">{{ $t('admin.sync_data_title') }}</h1>
        <p class="text-secondary text-sm mt-1">{{ $t('admin.sync_data_subtitle') }}</p>
      </div>
    </div>

    <!-- User Selector -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-[var(--text-secondary)] mb-2">{{ $t('admin.select_user') }}</label>
      <select v-model="selectedUserId" class="form-select max-w-md" @change="loadData">
        <option :value="null" disabled>{{ $t('admin.select_user') }}...</option>
        <option v-for="user in users" :key="user.id" :value="user.id">
          {{ user.email }}
          <template v-if="user.first_name || user.last_name"> — {{ user.first_name }} {{ user.last_name }}</template>
        </option>
      </select>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6">
      {{ error }}
    </div>

    <!-- Tabs -->
    <div v-if="selectedUserId" class="mb-6">
      <div class="flex gap-2 border-b border-[var(--border-subtle)] pb-2">
        <button
          v-for="tab in tabs"
          :key="tab.type"
          class="tab-btn"
          :class="{ active: activeTab === tab.type }"
          @click="switchTab(tab.type)"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-spin h-8 w-8 border-4 border-[var(--primary)] border-t-transparent rounded-full"></div>
    </div>

    <!-- Data Table -->
    <div v-else-if="selectedUserId && records.length > 0" class="table-card mt-4 mb-6">
      <div class="table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th v-for="col in activeColumns" :key="col.key">{{ col.label }}</th>
              <th class="text-right">{{ $t('common.edit') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in records" :key="record.id" class="hover:bg-white/5 transition-colors group">
              <td v-for="col in activeColumns" :key="col.key" class="text-sm">
                <template v-if="col.type === 'boolean'">
                  <span :class="record[col.key] ? 'badge badge-green' : 'badge badge-gray'">
                    {{ record[col.key] ? 'Yes' : 'No' }}
                  </span>
                </template>
                <template v-else-if="col.type === 'datetime'">
                  {{ formatDate(record[col.key]) }}
                </template>
                <template v-else>
                  {{ record[col.key] ?? '-' }}
                </template>
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <AppButton variant="secondary" size="sm" @click="openEditModal(record)">{{ $t('common.edit') }}</AppButton>
                  <AppButton variant="secondary" size="sm" @click="confirmDelete(record)" class="text-red-400 hover:text-red-300 border-red-500/30 hover:bg-red-500/10">
                    {{ $t('common.delete') }}
                  </AppButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="selectedUserId && !loading" class="p-12 text-center bg-elevated border border-subtle rounded-xl">
      <p class="text-secondary">{{ $t('admin.no_sync_data') }}</p>
    </div>

    <!-- Delete Confirmation Modal -->
    <AppModal :isOpen="!!deletingRecord" :title="$t('admin.delete_sync_record')" @close="deletingRecord = null">
      <p class="text-[var(--text-secondary)] mb-6">{{ $t('admin.delete_sync_confirm') }}</p>
      <div class="flex gap-3 justify-end mt-4">
        <AppButton variant="secondary" @click="deletingRecord = null">{{ $t('common.cancel') }}</AppButton>
        <AppButton variant="danger" @click="executeDelete">{{ $t('common.delete') }}</AppButton>
      </div>
    </AppModal>

    <!-- Edit Modal -->
    <AppModal :isOpen="editModal.isOpen" :title="$t('admin.edit_sync_record')" @close="closeEditModal">
      <div v-if="editModal.error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-3 rounded-lg mb-4 text-sm">
        {{ editModal.error }}
      </div>
      <div v-for="field in editableFields" :key="field.key" class="form-group mb-4">
        <label class="form-label">{{ field.label }}</label>
        <template v-if="field.type === 'boolean'">
          <select v-model="editModal.data[field.key]" class="form-select">
            <option :value="true">Yes</option>
            <option :value="false">No</option>
          </select>
        </template>
        <template v-else-if="field.type === 'textarea'">
          <textarea v-model="editModal.data[field.key]" class="form-input" rows="3"></textarea>
        </template>
        <template v-else-if="field.type === 'number'">
          <AppInput v-model.number="editModal.data[field.key]" type="number" />
        </template>
        <template v-else>
          <AppInput v-model="editModal.data[field.key]" :type="field.inputType || 'text'" />
        </template>
      </div>
      <div class="flex gap-4 justify-end mt-6">
        <AppButton variant="secondary" @click="closeEditModal">{{ $t('common.cancel') }}</AppButton>
        <AppButton @click="submitEdit" :loading="editModal.loading">{{ $t('common.save') }}</AppButton>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api/admin'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppModal from '@/components/ui/AppModal.vue'

const { t } = useI18n()

const users = ref([])
const selectedUserId = ref(null)
const activeTab = ref('weapons')
const records = ref([])
const loading = ref(false)
const error = ref('')
const deletingRecord = ref(null)
const editModal = ref({ isOpen: false, data: {}, error: '', loading: false })

const tabs = computed(() => [
  { type: 'weapons', label: t('admin.tab_weapons') },
  { type: 'ammo', label: t('admin.tab_ammo') },
  { type: 'competitions', label: t('admin.tab_competitions') },
  { type: 'competition_reminders', label: t('admin.tab_reminders') },
  { type: 'ammo_transactions', label: t('admin.tab_transactions') },
])

const columnDefs = {
  weapons: [
    { key: 'name', label: 'Name' },
    { key: 'caliber', label: 'Caliber' },
    { key: 'is_favorite', label: 'Favorite', type: 'boolean' },
    { key: 'is_archived', label: 'Archived', type: 'boolean' },
    { key: 'modified_at', label: t('admin.sync_modified_at'), type: 'datetime' },
  ],
  ammo: [
    { key: 'brand', label: 'Brand' },
    { key: 'name', label: 'Name' },
    { key: 'caliber', label: 'Caliber' },
    { key: 'grain_weight', label: 'Grain' },
    { key: 'current_stock', label: 'Stock' },
    { key: 'is_archived', label: 'Archived', type: 'boolean' },
    { key: 'modified_at', label: t('admin.sync_modified_at'), type: 'datetime' },
  ],
  competitions: [
    { key: 'name', label: 'Name' },
    { key: 'date', label: 'Date' },
    { key: 'location', label: 'Location' },
    { key: 'status', label: 'Status' },
    { key: 'modified_at', label: t('admin.sync_modified_at'), type: 'datetime' },
  ],
  competition_reminders: [
    { key: 'competition_uuid', label: 'Competition UUID' },
    { key: 'reminder_date', label: 'Date' },
    { key: 'type', label: 'Type' },
    { key: 'modified_at', label: t('admin.sync_modified_at'), type: 'datetime' },
  ],
  ammo_transactions: [
    { key: 'ammo_uuid', label: 'Ammo UUID' },
    { key: 'type', label: 'Type' },
    { key: 'quantity', label: 'Qty' },
    { key: 'modified_at', label: t('admin.sync_modified_at'), type: 'datetime' },
  ],
}

const fieldDefs = {
  weapons: [
    { key: 'name', label: 'Name' },
    { key: 'caliber', label: 'Caliber' },
    { key: 'notes', label: 'Notes', type: 'textarea' },
    { key: 'is_favorite', label: 'Favorite', type: 'boolean' },
    { key: 'is_archived', label: 'Archived', type: 'boolean' },
  ],
  ammo: [
    { key: 'brand', label: 'Brand' },
    { key: 'name', label: 'Name' },
    { key: 'caliber', label: 'Caliber' },
    { key: 'grain_weight', label: 'Grain Weight', type: 'number' },
    { key: 'current_stock', label: 'Current Stock', type: 'number' },
    { key: 'notes', label: 'Notes', type: 'textarea' },
    { key: 'is_archived', label: 'Archived', type: 'boolean' },
  ],
  competitions: [
    { key: 'name', label: 'Name' },
    { key: 'date', label: 'Date', inputType: 'date' },
    { key: 'end_date', label: 'End Date', inputType: 'date' },
    { key: 'location', label: 'Location' },
    { key: 'discipline_id', label: 'Discipline ID', type: 'number' },
    { key: 'status', label: 'Status' },
    { key: 'notes', label: 'Notes', type: 'textarea' },
  ],
  competition_reminders: [
    { key: 'reminder_date', label: 'Reminder Date', inputType: 'datetime-local' },
    { key: 'type', label: 'Type' },
  ],
  ammo_transactions: [
    { key: 'type', label: 'Type' },
    { key: 'quantity', label: 'Quantity', type: 'number' },
    { key: 'notes', label: 'Notes', type: 'textarea' },
  ],
}

const activeColumns = computed(() => columnDefs[activeTab.value] || [])
const editableFields = computed(() => fieldDefs[activeTab.value] || [])

const formatDate = (val) => {
  if (!val) return '-'
  return new Date(val).toLocaleString()
}

const loadUsers = async () => {
  try {
    const res = await adminApi.getUsers()
    if (res.success) {
      users.value = res.users
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load users'
  }
}

const loadData = async () => {
  if (!selectedUserId.value) return
  loading.value = true
  error.value = ''
  try {
    const res = await adminApi.getSyncData(selectedUserId.value, activeTab.value)
    if (res.success) {
      records.value = res.records
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load sync data'
    records.value = []
  } finally {
    loading.value = false
  }
}

const switchTab = (type) => {
  activeTab.value = type
  loadData()
}

const openEditModal = (record) => {
  const data = {}
  for (const field of editableFields.value) {
    data[field.key] = record[field.key]
  }
  data.type = activeTab.value
  editModal.value = { isOpen: true, data, error: '', loading: false, recordId: record.id }
}

const closeEditModal = () => {
  editModal.value.isOpen = false
}

const submitEdit = async () => {
  editModal.value.error = ''
  editModal.value.loading = true
  try {
    await adminApi.updateSyncData(editModal.value.recordId, editModal.value.data)
    closeEditModal()
    await loadData()
  } catch (err) {
    editModal.value.error = err.response?.data?.message || 'Update failed'
  } finally {
    editModal.value.loading = false
  }
}

const confirmDelete = (record) => {
  deletingRecord.value = record
}

const executeDelete = async () => {
  if (!deletingRecord.value) return
  try {
    await adminApi.deleteSyncData(deletingRecord.value.id, activeTab.value)
    await loadData()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete record'
  } finally {
    deletingRecord.value = null
  }
}

onMounted(() => {
  loadUsers()
})
</script>

<style scoped>
.tab-btn {
  padding: 8px 16px;
  color: var(--text-secondary);
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s;
  white-space: nowrap;
  font-size: 0.9rem;
  border: none;
  background: none;
  cursor: pointer;
}

.tab-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-primary);
}

.tab-btn.active {
  background: var(--bg-surface);
  color: var(--primary);
  border: 1px solid var(--border-subtle);
}
</style>
```

- [ ] **Step 2: Verify the file has no syntax errors**

Run: `cd client && npx vue-tsc --noEmit 2>&1 | head -20` (or just start the dev server)

- [ ] **Step 3: Commit**

```bash
git add client/src/views/admin/SyncDataView.vue
git commit -m "feat(admin): add SyncDataView with user selector, tabs, tables, and edit/delete modals"
```

---

### Task 10: Wire up router and admin nav

**Files:**
- Modify: `client/src/router/index.js`
- Modify: `client/src/views/admin/AdminLayout.vue`

- [ ] **Step 1: Add the sync-data route**

In `client/src/router/index.js`, inside the admin children array, after the RemoteConfigDetailView route (around line 200), add:

```javascript
            {
                path: 'sync-data',
                name: 'AdminSyncData',
                meta: { requiresSuperAdmin: true, title: 'Admin | Sync Data' },
                component: () => import('../views/admin/SyncDataView.vue')
            },
```

- [ ] **Step 2: Add the nav tab to AdminLayout.vue**

In `client/src/views/admin/AdminLayout.vue`, after the Remote Configs router-link (line 12), add:

```html
      <router-link v-if="auth.isAdmin" to="/admin/sync-data" class="admin-tab" active-class="active">{{ $t('admin.sync_data_title') }}</router-link>
```

- [ ] **Step 3: Verify locally**

Run: `cd client && npm run dev`

Open `http://localhost:5173/admin/sync-data`. Verify:
- The "Sync Data" tab appears in the admin nav (visible to admin users only)
- The user selector dropdown loads
- Selecting a user and a tab loads data (or shows empty state if no sync data exists)
- Edit modal opens and submits correctly
- Delete modal shows confirmation and soft-deletes

- [ ] **Step 4: Commit**

```bash
git add client/src/router/index.js client/src/views/admin/AdminLayout.vue
git commit -m "feat(admin): wire up sync-data route and nav tab"
```
