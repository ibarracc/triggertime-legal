# Language Dropdown Fix + GDPR Cookie Consent Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix the language dropdown styling to match AppInput, and add a GDPR-compliant cookie consent banner with Accept All, Reject All, and granular preference management.

**Architecture:** Extend AppInput with `type="select"` support, replace raw selects in RegisterView and ProfileView. Create a `useCookieConsent` composable for localStorage-based consent state, a `CookieConsent.vue` banner component mounted in App.vue, add footer link to re-open settings, and translate all new keys to 8 languages.

**Tech Stack:** Vue 3 (Composition API), vue-i18n, Vite, localStorage

---

### Task 1: Add `type="select"` support to AppInput

**Files:**
- Modify: `client/src/components/ui/AppInput.vue`

**Step 1: Add `options` prop to defineProps**

In `client/src/components/ui/AppInput.vue`, add the `options` prop and a computed `isSelect`:

Replace the existing `defineProps` block (lines 40-51):

```javascript
const props = defineProps({
  modelValue: [String, Number],
  label: String,
  type: { type: String, default: 'text' },
  placeholder: String,
  id: { type: String, default: () => `input-${Math.random().toString(36).substr(2, 9)}` },
  required: Boolean,
  disabled: Boolean,
  error: String,
  autocomplete: String,
  name: String
})
```

With:

```javascript
const props = defineProps({
  modelValue: [String, Number],
  label: String,
  type: { type: String, default: 'text' },
  placeholder: String,
  id: { type: String, default: () => `input-${Math.random().toString(36).substr(2, 9)}` },
  required: Boolean,
  disabled: Boolean,
  error: String,
  autocomplete: String,
  name: String,
  options: { type: Array, default: () => [] }
})
```

Add a computed after the existing `isPassword`:

```javascript
const isSelect = computed(() => props.type === 'select')
```

**Step 2: Add `<select>` rendering in the template**

In the template's `<div class="input-wrapper">`, add a `<select>` element before the existing `<input>`. Use `v-if="isSelect"` on the select and `v-else-if="!isSelect"` (or just `v-else`) on the existing input.

Replace the `<input ... />` block (lines 5-17) with:

```html
      <select
        v-if="isSelect"
        :id="id"
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
        :required="required"
        :disabled="disabled"
        :name="name"
        class="input"
        :class="{ 'has-error': error }"
      >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option v-for="opt in options" :key="opt.value" :value="opt.value">
          {{ opt.label }}
        </option>
      </select>
      <input
        v-else
        :id="id"
        :type="inputType"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :autocomplete="autocomplete"
        :name="name"
        class="input"
        :class="{ 'has-error': error, 'has-icon': $slots.icon, 'has-trailing-icon': isPassword }"
      />
```

**Step 3: Add select-specific CSS**

Add this CSS rule inside `<style scoped>` to ensure the native select arrow looks good and the select has the same `appearance` override:

```css
select.input {
  appearance: none;
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 16px center;
  padding-right: 44px;
  cursor: pointer;
}

select.input option {
  background: var(--bg-elevated);
  color: var(--text-primary);
}
```

**Step 4: Verify it builds**

Run from `client/`:
```bash
npm run build
```
Expected: Build succeeds with no errors.

**Step 5: Commit**

```bash
git add client/src/components/ui/AppInput.vue
git commit -m "feat: add type=select support to AppInput component"
```

---

### Task 2: Replace raw language dropdown in RegisterView

**Files:**
- Modify: `client/src/views/public/RegisterView.vue`

**Step 1: Replace the raw `<select>` block**

In `client/src/views/public/RegisterView.vue`, replace lines 31-41:

```html
        <div class="mb-4">
          <label class="block text-sm font-medium text-secondary mb-1.5">{{ $t('common.language') }}</label>
          <select
            v-model="language"
            class="w-full bg-elevated border border-subtle rounded-lg p-2.5 text-sm text-primary focus:border-primary outline-none transition-all"
          >
            <option v-for="l in availableLocales" :key="l.code" :value="l.code">
              {{ l.flag }} {{ l.name }}
            </option>
          </select>
        </div>
```

With:

```html
        <AppInput
          v-model="language"
          :label="$t('common.language')"
          type="select"
          :options="availableLocales.map(l => ({ value: l.code, label: `${l.flag} ${l.name}` }))"
        />
```

**Step 2: Verify it builds**

Run from `client/`:
```bash
npm run build
```
Expected: Build succeeds with no errors.

**Step 3: Commit**

```bash
git add client/src/views/public/RegisterView.vue
git commit -m "feat: use AppInput select for language in RegisterView"
```

---

### Task 3: Replace raw language dropdown in ProfileView

**Files:**
- Modify: `client/src/views/dashboard/ProfileView.vue`

**Step 1: Replace the raw `<select>` block**

In `client/src/views/dashboard/ProfileView.vue`, replace lines 34-44:

```html
          <div class="mb-4">
            <label class="block text-sm font-medium text-secondary mb-1.5">{{ $t('common.language') }}</label>
            <select
              v-model="profileForm.language"
              class="w-full bg-elevated border border-subtle rounded-lg p-2.5 text-sm text-primary focus:border-primary outline-none transition-all"
            >
              <option v-for="l in availableLocales" :key="l.code" :value="l.code">
                {{ l.flag }} {{ l.name }}
              </option>
            </select>
          </div>
```

With:

```html
          <AppInput
            v-model="profileForm.language"
            :label="$t('common.language')"
            type="select"
            :options="availableLocales.map(l => ({ value: l.code, label: `${l.flag} ${l.name}` }))"
          />
```

**Step 2: Verify it builds**

Run from `client/`:
```bash
npm run build
```
Expected: Build succeeds with no errors.

**Step 3: Commit**

```bash
git add client/src/views/dashboard/ProfileView.vue
git commit -m "feat: use AppInput select for language in ProfileView"
```

---

### Task 4: Create `useCookieConsent` composable

**Files:**
- Create: `client/src/composables/useCookieConsent.js`

**Step 1: Create the composable**

Create `client/src/composables/useCookieConsent.js` with this content:

```javascript
import { reactive, readonly } from 'vue'

const STORAGE_KEY = 'tt_cookie_consent'

const state = reactive({
  essential: true,
  analytics: false,
  marketing: false,
  timestamp: null,
  showBanner: false
})

function loadConsent() {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored) {
      const parsed = JSON.parse(stored)
      state.essential = true // always true
      state.analytics = parsed.analytics || false
      state.marketing = parsed.marketing || false
      state.timestamp = parsed.timestamp || null
      state.showBanner = false
    } else {
      state.showBanner = true
    }
  } catch {
    state.showBanner = true
  }
}

function saveConsent() {
  const data = {
    essential: true,
    analytics: state.analytics,
    marketing: state.marketing,
    timestamp: new Date().toISOString()
  }
  localStorage.setItem(STORAGE_KEY, JSON.stringify(data))
  state.timestamp = data.timestamp
  state.showBanner = false
}

function acceptAll() {
  state.analytics = true
  state.marketing = true
  saveConsent()
}

function rejectAll() {
  state.analytics = false
  state.marketing = false
  saveConsent()
}

function savePreferences(prefs) {
  state.analytics = prefs.analytics || false
  state.marketing = prefs.marketing || false
  saveConsent()
}

function openBanner() {
  state.showBanner = true
}

function isAllowed(category) {
  if (category === 'essential') return true
  return state[category] || false
}

// Initialize on first import
loadConsent()

export function useCookieConsent() {
  return {
    state: readonly(state),
    acceptAll,
    rejectAll,
    savePreferences,
    openBanner,
    isAllowed
  }
}
```

**Step 2: Verify it builds**

Run from `client/`:
```bash
npm run build
```
Expected: Build succeeds (composable is tree-shaken if unused, but should not cause errors).

**Step 3: Commit**

```bash
git add client/src/composables/useCookieConsent.js
git commit -m "feat: add useCookieConsent composable for GDPR consent"
```

---

### Task 5: Add cookie consent i18n keys to `en.json`

**Files:**
- Modify: `client/src/i18n/locales/en.json`

**Step 1: Add `cookies` section**

In `client/src/i18n/locales/en.json`, add a new `"cookies"` section right before the closing `}` of the JSON (i.e., after the `"notFound"` block). Also add `"footer.cookie_settings"` to the `"footer"` section.

Add to the `"footer"` section (after `"rights"`):

```json
"cookie_settings": "Cookie Settings"
```

Add the new `"cookies"` top-level section (before the final `}`):

```json
"cookies": {
    "banner_title": "We value your privacy",
    "banner_text": "We use cookies to improve your experience. You can choose which categories to allow. Essential cookies are always active as they are necessary for the site to function.",
    "accept_all": "Accept All",
    "reject_all": "Reject All",
    "manage": "Manage Preferences",
    "save_preferences": "Save Preferences",
    "essential_title": "Essential",
    "essential_desc": "Required for the website to function. These cannot be disabled.",
    "analytics_title": "Analytics",
    "analytics_desc": "Help us understand how visitors use our website to improve the experience.",
    "marketing_title": "Marketing",
    "marketing_desc": "Used to deliver relevant advertisements and track campaign effectiveness.",
    "always_on": "Always on"
}
```

**Step 2: Verify JSON is valid**

Run:
```bash
node -e "JSON.parse(require('fs').readFileSync('client/src/i18n/locales/en.json','utf8')); console.log('OK')"
```
Expected: `OK`

**Step 3: Commit**

```bash
git add client/src/i18n/locales/en.json
git commit -m "feat: add cookie consent i18n keys to en.json"
```

---

### Task 6: Translate cookie consent keys to all 7 other locales

**Files:**
- Modify: `client/src/i18n/locales/es.json`
- Modify: `client/src/i18n/locales/de.json`
- Modify: `client/src/i18n/locales/fr.json`
- Modify: `client/src/i18n/locales/pt.json`
- Modify: `client/src/i18n/locales/eu.json`
- Modify: `client/src/i18n/locales/ca.json`
- Modify: `client/src/i18n/locales/gl.json`

**Step 1: Add `cookies` section + `footer.cookie_settings` to each locale file**

For each of the 7 locale files, add the translated `"cookie_settings"` key to the `"footer"` section and add a translated `"cookies"` section with the same 13 keys as `en.json`.

**Important:** Use ONLY straight ASCII double quotes (`"`) in the JSON. Do NOT use curly/smart quotes or locale-specific quotation marks.

**Translations to add (provided in full for each locale):**

**es.json:**
```json
"cookie_settings": "Configuración de Cookies"
```
```json
"cookies": {
    "banner_title": "Valoramos tu privacidad",
    "banner_text": "Usamos cookies para mejorar tu experiencia. Puedes elegir qué categorías permitir. Las cookies esenciales están siempre activas ya que son necesarias para el funcionamiento del sitio.",
    "accept_all": "Aceptar Todas",
    "reject_all": "Rechazar Todas",
    "manage": "Gestionar Preferencias",
    "save_preferences": "Guardar Preferencias",
    "essential_title": "Esenciales",
    "essential_desc": "Necesarias para el funcionamiento del sitio web. No se pueden desactivar.",
    "analytics_title": "Analíticas",
    "analytics_desc": "Nos ayudan a entender cómo los visitantes usan nuestro sitio web para mejorar la experiencia.",
    "marketing_title": "Marketing",
    "marketing_desc": "Se utilizan para mostrar anuncios relevantes y medir la efectividad de las campañas.",
    "always_on": "Siempre activas"
}
```

**de.json:**
```json
"cookie_settings": "Cookie-Einstellungen"
```
```json
"cookies": {
    "banner_title": "Wir respektieren Ihre Privatsphäre",
    "banner_text": "Wir verwenden Cookies, um Ihre Erfahrung zu verbessern. Sie können wählen, welche Kategorien Sie zulassen möchten. Essentielle Cookies sind immer aktiv, da sie für die Funktion der Website erforderlich sind.",
    "accept_all": "Alle akzeptieren",
    "reject_all": "Alle ablehnen",
    "manage": "Einstellungen verwalten",
    "save_preferences": "Einstellungen speichern",
    "essential_title": "Essenziell",
    "essential_desc": "Erforderlich für die Funktion der Website. Diese können nicht deaktiviert werden.",
    "analytics_title": "Analyse",
    "analytics_desc": "Helfen uns zu verstehen, wie Besucher unsere Website nutzen, um die Erfahrung zu verbessern.",
    "marketing_title": "Marketing",
    "marketing_desc": "Werden verwendet, um relevante Werbung anzuzeigen und die Wirksamkeit von Kampagnen zu messen.",
    "always_on": "Immer aktiv"
}
```

**fr.json:**
```json
"cookie_settings": "Paramètres des Cookies"
```
```json
"cookies": {
    "banner_title": "Nous respectons votre vie privée",
    "banner_text": "Nous utilisons des cookies pour améliorer votre expérience. Vous pouvez choisir les catégories à autoriser. Les cookies essentiels sont toujours actifs car ils sont nécessaires au fonctionnement du site.",
    "accept_all": "Tout accepter",
    "reject_all": "Tout refuser",
    "manage": "Gérer les préférences",
    "save_preferences": "Enregistrer les préférences",
    "essential_title": "Essentiels",
    "essential_desc": "Nécessaires au fonctionnement du site web. Ils ne peuvent pas être désactivés.",
    "analytics_title": "Analytiques",
    "analytics_desc": "Nous aident à comprendre comment les visiteurs utilisent notre site pour améliorer l'expérience.",
    "marketing_title": "Marketing",
    "marketing_desc": "Utilisés pour diffuser des publicités pertinentes et mesurer l'efficacité des campagnes.",
    "always_on": "Toujours actifs"
}
```

**pt.json:**
```json
"cookie_settings": "Configurações de Cookies"
```
```json
"cookies": {
    "banner_title": "Valorizamos a sua privacidade",
    "banner_text": "Utilizamos cookies para melhorar a sua experiência. Pode escolher quais categorias permitir. Os cookies essenciais estão sempre ativos pois são necessários para o funcionamento do site.",
    "accept_all": "Aceitar Todos",
    "reject_all": "Rejeitar Todos",
    "manage": "Gerir Preferências",
    "save_preferences": "Guardar Preferências",
    "essential_title": "Essenciais",
    "essential_desc": "Necessários para o funcionamento do site. Não podem ser desativados.",
    "analytics_title": "Analíticos",
    "analytics_desc": "Ajudam-nos a compreender como os visitantes utilizam o nosso site para melhorar a experiência.",
    "marketing_title": "Marketing",
    "marketing_desc": "Utilizados para apresentar anúncios relevantes e medir a eficácia das campanhas.",
    "always_on": "Sempre ativos"
}
```

**eu.json (Basque):**
```json
"cookie_settings": "Cookie Ezarpenak"
```
```json
"cookies": {
    "banner_title": "Zure pribatutasuna errespetatzen dugu",
    "banner_text": "Cookieak erabiltzen ditugu zure esperientzia hobetzeko. Kategoria bakoitza baimendu nahi duzun aukeratu dezakezu. Funtsezko cookieak beti aktibo daude webgunearen funtzionamendurako beharrezkoak direlako.",
    "accept_all": "Guztiak onartu",
    "reject_all": "Guztiak baztertu",
    "manage": "Hobespenak kudeatu",
    "save_preferences": "Hobespenak gorde",
    "essential_title": "Funtsezko",
    "essential_desc": "Webgunearen funtzionamendurako beharrezkoak. Ezin dira desaktibatu.",
    "analytics_title": "Analitikoak",
    "analytics_desc": "Bisitariek gure webgunea nola erabiltzen duten ulertzen laguntzen digute esperientzia hobetzeko.",
    "marketing_title": "Marketinga",
    "marketing_desc": "Iragarki garrantzitsuak erakusteko eta kanpainen eraginkortasuna neurtzeko erabiltzen dira.",
    "always_on": "Beti aktibo"
}
```

**ca.json (Catalan):**
```json
"cookie_settings": "Configuració de Cookies"
```
```json
"cookies": {
    "banner_title": "Valorem la teva privacitat",
    "banner_text": "Utilitzem cookies per millorar la teva experiència. Pots triar quines categories permetre. Les cookies essencials estan sempre actives ja que són necessàries per al funcionament del lloc.",
    "accept_all": "Acceptar Totes",
    "reject_all": "Rebutjar Totes",
    "manage": "Gestionar Preferències",
    "save_preferences": "Desar Preferències",
    "essential_title": "Essencials",
    "essential_desc": "Necessàries per al funcionament del lloc web. No es poden desactivar.",
    "analytics_title": "Analítiques",
    "analytics_desc": "Ens ajuden a entendre com els visitants utilitzen el nostre lloc web per millorar l'experiència.",
    "marketing_title": "Màrqueting",
    "marketing_desc": "S'utilitzen per mostrar anuncis rellevants i mesurar l'efectivitat de les campanyes.",
    "always_on": "Sempre actives"
}
```

**gl.json (Galician):**
```json
"cookie_settings": "Configuración de Cookies"
```
```json
"cookies": {
    "banner_title": "Valoramos a túa privacidade",
    "banner_text": "Usamos cookies para mellorar a túa experiencia. Podes escoller que categorías permitir. As cookies esenciais están sempre activas xa que son necesarias para o funcionamento do sitio.",
    "accept_all": "Aceptar Todas",
    "reject_all": "Rexeitar Todas",
    "manage": "Xestionar Preferencias",
    "save_preferences": "Gardar Preferencias",
    "essential_title": "Esenciais",
    "essential_desc": "Necesarias para o funcionamento do sitio web. Non se poden desactivar.",
    "analytics_title": "Analíticas",
    "analytics_desc": "Axúdannos a entender como os visitantes usan o noso sitio web para mellorar a experiencia.",
    "marketing_title": "Márketing",
    "marketing_desc": "Utilízanse para mostrar anuncios relevantes e medir a efectividade das campañas.",
    "always_on": "Sempre activas"
}
```

**Step 2: Validate all JSON files**

Run:
```bash
for f in client/src/i18n/locales/*.json; do node -e "JSON.parse(require('fs').readFileSync('$f','utf8')); console.log('OK: $f')" || echo "FAIL: $f"; done
```
Expected: All 8 files print `OK`.

**Step 3: Commit**

```bash
git add client/src/i18n/locales/es.json client/src/i18n/locales/de.json client/src/i18n/locales/fr.json client/src/i18n/locales/pt.json client/src/i18n/locales/eu.json client/src/i18n/locales/ca.json client/src/i18n/locales/gl.json
git commit -m "feat: add cookie consent translations for 7 locales"
```

---

### Task 7: Create CookieConsent.vue component

**Files:**
- Create: `client/src/components/CookieConsent.vue`

**Step 1: Create the component**

Create `client/src/components/CookieConsent.vue`:

```vue
<template>
  <Transition name="slide-up">
    <div v-if="consent.state.showBanner" class="cookie-banner">
      <div class="cookie-content">
        <div class="cookie-text">
          <h3 class="cookie-title">{{ $t('cookies.banner_title') }}</h3>
          <p class="cookie-desc">{{ $t('cookies.banner_text') }}</p>
        </div>

        <!-- Category toggles (shown when managing preferences) -->
        <div v-if="showPreferences" class="cookie-categories">
          <div class="cookie-category">
            <div class="category-header">
              <div>
                <span class="category-name">{{ $t('cookies.essential_title') }}</span>
                <p class="category-desc">{{ $t('cookies.essential_desc') }}</p>
              </div>
              <span class="always-on-badge">{{ $t('cookies.always_on') }}</span>
            </div>
          </div>

          <div class="cookie-category">
            <div class="category-header">
              <div>
                <span class="category-name">{{ $t('cookies.analytics_title') }}</span>
                <p class="category-desc">{{ $t('cookies.analytics_desc') }}</p>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="prefs.analytics" />
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>

          <div class="cookie-category">
            <div class="category-header">
              <div>
                <span class="category-name">{{ $t('cookies.marketing_title') }}</span>
                <p class="category-desc">{{ $t('cookies.marketing_desc') }}</p>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="prefs.marketing" />
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <div class="cookie-actions">
          <template v-if="!showPreferences">
            <button class="cookie-btn cookie-btn-primary" @click="consent.acceptAll()">
              {{ $t('cookies.accept_all') }}
            </button>
            <button class="cookie-btn cookie-btn-secondary" @click="consent.rejectAll()">
              {{ $t('cookies.reject_all') }}
            </button>
            <button class="cookie-btn cookie-btn-link" @click="showPreferences = true">
              {{ $t('cookies.manage') }}
            </button>
          </template>
          <template v-else>
            <button class="cookie-btn cookie-btn-primary" @click="handleSavePreferences">
              {{ $t('cookies.save_preferences') }}
            </button>
            <button class="cookie-btn cookie-btn-secondary" @click="consent.acceptAll()">
              {{ $t('cookies.accept_all') }}
            </button>
          </template>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useCookieConsent } from '@/composables/useCookieConsent'

const consent = useCookieConsent()
const showPreferences = ref(false)
const prefs = reactive({
  analytics: consent.state.analytics,
  marketing: consent.state.marketing
})

const handleSavePreferences = () => {
  consent.savePreferences({
    analytics: prefs.analytics,
    marketing: prefs.marketing
  })
  showPreferences.value = false
}
</script>

<style scoped>
.cookie-banner {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  background: var(--bg-surface);
  border-top: 1px solid var(--border-subtle);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
  padding: 24px;
}

.cookie-content {
  max-width: 960px;
  margin: 0 auto;
}

.cookie-title {
  font-family: var(--font-heading);
  font-size: 1.125rem;
  font-weight: 700;
  margin-bottom: 8px;
  color: var(--text-primary);
}

.cookie-desc {
  font-size: 0.875rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin-bottom: 16px;
}

.cookie-categories {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.cookie-category {
  background: var(--bg-elevated);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 16px;
}

.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.category-name {
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--text-primary);
}

.category-desc {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-top: 4px;
  margin-bottom: 0;
  line-height: 1.4;
}

.always-on-badge {
  font-size: 0.75rem;
  color: var(--primary);
  background: rgba(193, 255, 114, 0.1);
  padding: 4px 10px;
  border-radius: 20px;
  white-space: nowrap;
  font-weight: 500;
}

/* Toggle switch */
.toggle {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
  flex-shrink: 0;
}

.toggle input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: var(--bg-base);
  border: 1px solid var(--border-subtle);
  border-radius: 24px;
  transition: all 0.2s ease;
}

.toggle-slider::before {
  content: '';
  position: absolute;
  height: 18px;
  width: 18px;
  left: 2px;
  bottom: 2px;
  background: var(--text-secondary);
  border-radius: 50%;
  transition: all 0.2s ease;
}

.toggle input:checked + .toggle-slider {
  background: var(--primary);
  border-color: var(--primary);
}

.toggle input:checked + .toggle-slider::before {
  transform: translateX(20px);
  background: #0A0A0F;
}

/* Buttons */
.cookie-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.cookie-btn {
  padding: 10px 20px;
  border-radius: 12px;
  font-family: var(--font-body);
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.cookie-btn-primary {
  background: var(--primary);
  color: #0A0A0F;
}

.cookie-btn-primary:hover {
  background: var(--primary-hover);
}

.cookie-btn-secondary {
  background: var(--bg-elevated);
  color: var(--text-primary);
  border-color: var(--border-subtle);
}

.cookie-btn-secondary:hover {
  border-color: rgba(255, 255, 255, 0.3);
}

.cookie-btn-link {
  background: none;
  color: var(--text-secondary);
  padding: 10px 12px;
}

.cookie-btn-link:hover {
  color: var(--primary);
}

/* Slide-up transition */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

@media (max-width: 600px) {
  .cookie-banner {
    padding: 16px;
  }

  .cookie-actions {
    flex-direction: column;
  }

  .cookie-btn {
    width: 100%;
    text-align: center;
  }

  .category-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}
</style>
```

**Step 2: Verify it builds**

Run from `client/`:
```bash
npm run build
```
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add client/src/components/CookieConsent.vue
git commit -m "feat: add CookieConsent banner component"
```

---

### Task 8: Mount CookieConsent in App.vue

**Files:**
- Modify: `client/src/App.vue`

**Step 1: Import and add CookieConsent to App.vue**

In `client/src/App.vue`, add the import:

```javascript
import CookieConsent from '@/components/CookieConsent.vue'
```

And add `<CookieConsent />` in the template, after the `<component>` tag:

The template should become:

```html
<template>
  <component :is="layoutComponent">
    <router-view />
  </component>
  <CookieConsent />
</template>
```

**Step 2: Verify it builds**

Run from `client/`:
```bash
npm run build
```
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add client/src/App.vue
git commit -m "feat: mount CookieConsent banner in App.vue"
```

---

### Task 9: Add "Cookie Settings" link to AppFooter

**Files:**
- Modify: `client/src/components/layout/AppFooter.vue`

**Step 1: Import useCookieConsent and add footer link**

In `client/src/components/layout/AppFooter.vue`, add a `<script setup>` block and add the cookie settings link.

Add this script block before `<style scoped>`:

```html
<script setup>
import { useCookieConsent } from '@/composables/useCookieConsent'

const { openBanner } = useCookieConsent()
</script>
```

In the template, add a new link after the existing "Terms" link inside `<div class="footer-links ...">`. Add this after the `<a href="mailto:...">` line (line 13):

```html
          <button class="nav-link cookie-link" @click="openBanner">{{ $t('footer.cookie_settings') }}</button>
```

Add this CSS inside `<style scoped>`:

```css
.cookie-link {
  background: none;
  border: none;
  font-family: inherit;
  cursor: pointer;
  padding: 0;
}
```

**Step 2: Verify it builds**

Run from `client/`:
```bash
npm run build
```
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add client/src/components/layout/AppFooter.vue
git commit -m "feat: add Cookie Settings link to footer"
```

---

### Task 10: Final build verification

**Files:** None (verification only)

**Step 1: Run full production build**

Run from `client/`:
```bash
npm run build
```
Expected: Build succeeds with no errors or warnings.

**Step 2: Verify all 8 locale JSON files are valid**

Run:
```bash
for f in client/src/i18n/locales/*.json; do node -e "JSON.parse(require('fs').readFileSync('$f','utf8')); console.log('OK: $f')" || echo "FAIL: $f"; done
```
Expected: All 8 files print `OK`.

**Step 3: Verify key counts are consistent**

Run:
```bash
for f in client/src/i18n/locales/*.json; do echo "$f: $(node -e "const j=JSON.parse(require('fs').readFileSync('$f','utf8')); console.log(Object.keys(j.cookies || {}).length)")"; done
```
Expected: All 8 files should show `13` (the 13 cookie keys).
