# Analytics Integration & Privacy Policy Update — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add GTM with GA4 Consent Mode v2, track key conversion events, and update the privacy policy across all 8 languages.

**Architecture:** GTM snippet in `index.html` with consent defaults set to denied. A `useAnalytics` composable bridges cookie consent state with GTM Consent Mode and provides `trackEvent()`. Events are pushed to `dataLayer` from existing Vue components/stores.

**Tech Stack:** Google Tag Manager, Google Analytics 4 (configured in GTM UI), Vue 3 Composition API, vue-i18n

---

### Task 1: Add GTM + Consent Mode v2 to `index.html`

**Files:**
- Modify: `client/index.html`

- [ ] **Step 1: Add Consent Mode v2 defaults and GTM snippet to `<head>`**

In `client/index.html`, add the following scripts inside `<head>`, immediately after the opening `<head>` tag (before the `<meta charset>` line):

```html
    <!-- Google Consent Mode v2 defaults -->
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('consent', 'default', {
        'analytics_storage': 'denied',
        'ad_storage': 'denied',
        'ad_user_data': 'denied',
        'ad_personalization': 'denied',
        'wait_for_update': 500
      });
    </script>

    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-5FFK3F55');</script>
```

- [ ] **Step 2: Add GTM noscript fallback to `<body>`**

In `client/index.html`, add immediately after the opening `<body>` tag (before `<div id="app">`):

```html
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5FFK3F55"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
```

- [ ] **Step 3: Verify the file looks correct**

Run: `cat client/index.html`

Expected: The file should have the consent defaults script first, then GTM script in `<head>`, noscript iframe in `<body>`, then `<div id="app">` and the module script.

- [ ] **Step 4: Commit**

```bash
git add client/index.html
git commit -m "feat: add GTM and Consent Mode v2 to index.html"
```

---

### Task 2: Create `useAnalytics` composable

**Files:**
- Create: `client/src/composables/useAnalytics.js`

- [ ] **Step 1: Create the composable**

Create `client/src/composables/useAnalytics.js` with this content:

```js
import { watch } from 'vue'
import { useCookieConsent } from './useCookieConsent'

function gtag() {
  window.dataLayer = window.dataLayer || []
  window.dataLayer.push(arguments)
}

function updateConsentState(analyticsAllowed, marketingAllowed) {
  gtag('consent', 'update', {
    'analytics_storage': analyticsAllowed ? 'granted' : 'denied',
    'ad_storage': marketingAllowed ? 'granted' : 'denied',
    'ad_user_data': marketingAllowed ? 'granted' : 'denied',
    'ad_personalization': marketingAllowed ? 'granted' : 'denied',
  })
}

function trackEvent(eventName, params = {}) {
  window.dataLayer = window.dataLayer || []
  window.dataLayer.push({ event: eventName, ...params })
}

let initialized = false

export function useAnalytics() {
  if (!initialized) {
    initialized = true
    const { state } = useCookieConsent()

    // Sync current consent state on load (handles returning visitors)
    if (state.timestamp) {
      updateConsentState(state.analytics, state.marketing)
    }

    // Watch for consent changes (user interacts with cookie banner)
    watch(
      () => [state.analytics, state.marketing],
      ([analytics, marketing]) => {
        updateConsentState(analytics, marketing)
      }
    )
  }

  return { trackEvent }
}
```

- [ ] **Step 2: Commit**

```bash
git add client/src/composables/useAnalytics.js
git commit -m "feat: add useAnalytics composable for GTM consent bridge"
```

---

### Task 3: Initialize analytics in `App.vue` and add page_view tracking

**Files:**
- Modify: `client/src/App.vue`
- Modify: `client/src/router/index.js`

- [ ] **Step 1: Initialize `useAnalytics` in `App.vue`**

In `client/src/App.vue`, add the import and initialization call in the `<script setup>` block. Add after the existing imports:

```js
import { useAnalytics } from '@/composables/useAnalytics'
```

And add after the `SUPPORTED_LOCALES` declaration:

```js
useAnalytics()
```

- [ ] **Step 2: Add `page_view` event to router `afterEach`**

In `client/src/router/index.js`, add an import at the top of the file (after the existing imports):

```js
import { useAnalytics } from '@/composables/useAnalytics'
```

Then modify the existing `router.afterEach` callback to also push a page_view event. Replace:

```js
router.afterEach((to) => {
    const title = to.meta.title
    document.title = title ? `TriggerTime | ${title}` : 'TriggerTime'
})
```

With:

```js
router.afterEach((to) => {
    const title = to.meta.title
    document.title = title ? `TriggerTime | ${title}` : 'TriggerTime'

    const { trackEvent } = useAnalytics()
    trackEvent('page_view', {
        page_title: document.title,
        page_path: to.fullPath,
    })
})
```

- [ ] **Step 3: Verify dev server starts**

Run from `client/`:
```bash
npm run build
```

Expected: Build succeeds with no errors.

- [ ] **Step 4: Commit**

```bash
git add client/src/App.vue client/src/router/index.js
git commit -m "feat: initialize analytics and add page_view tracking"
```

---

### Task 4: Add `sign_up` and `login` events to auth store

**Files:**
- Modify: `client/src/stores/auth.js`

- [ ] **Step 1: Add trackEvent calls to auth store**

In `client/src/stores/auth.js`, the store uses `window.dataLayer.push()` directly since it's not a Vue component and `useAnalytics` is designed for component context. Add a helper at the top of the file (after the imports):

```js
function trackEvent(eventName, params = {}) {
    window.dataLayer = window.dataLayer || []
    window.dataLayer.push({ event: eventName, ...params })
}
```

Then add tracking calls in three places:

**In `login()` function**, after `setAuthData(response.token, response.user, response.user.subscriptions?.[0])` and before `return { success: true }`:

```js
                trackEvent('login', { method: 'email' })
```

**In `register()` function**, after `setAuthData(response.token, response.user, response.user.subscriptions?.[0])` and before `return { success: true }`:

```js
                trackEvent('sign_up', { method: 'email' })
```

**In `socialLogin()` function**, after `setAuthData(response.token, response.user, response.user.subscriptions?.[0])` and before `return { success: true }`:

```js
                trackEvent('login', { method: provider })
```

- [ ] **Step 2: Commit**

```bash
git add client/src/stores/auth.js
git commit -m "feat: track sign_up and login events in auth store"
```

---

### Task 5: Add checkout and purchase events

**Files:**
- Modify: `client/src/views/public/CheckoutLanding.vue`
- Modify: `client/src/views/public/CheckoutSuccessView.vue`

- [ ] **Step 1: Add `begin_checkout` event to `CheckoutLanding.vue`**

In `client/src/views/public/CheckoutLanding.vue`, add import after the existing imports:

```js
import { useAnalytics } from '@/composables/useAnalytics'
```

Add after the existing ref declarations (after `const isAlreadyLinked = ref(false)`):

```js
const { trackEvent } = useAnalytics()
```

In the `processCheckout` function, add the tracking call after the successful response check, before the redirect. Replace:

```js
        if (res.success && res.url) {
            window.location.href = res.url // Redirect to Stripe
        }
```

With:

```js
        if (res.success && res.url) {
            trackEvent('begin_checkout', { plan: 'pro' })
            window.location.href = res.url // Redirect to Stripe
        }
```

- [ ] **Step 2: Add `purchase` event to `CheckoutSuccessView.vue`**

In `client/src/views/public/CheckoutSuccessView.vue`, add import after the existing imports:

```js
import { useAnalytics } from '@/composables/useAnalytics'
```

Add after the store/router declarations (after `const authStore = useAuthStore()`):

```js
const { trackEvent } = useAnalytics()
```

In the `onMounted` callback, add the tracking call after the user data refresh. Replace:

```js
onMounted(async () => {
  // Refresh user data so the new subscription status is reflected immediately
  if (authStore.isAuthenticated) {
    await authStore.fetchUser()
  }
})
```

With:

```js
onMounted(async () => {
  // Refresh user data so the new subscription status is reflected immediately
  if (authStore.isAuthenticated) {
    await authStore.fetchUser()
  }
  trackEvent('purchase', { plan: 'pro' })
})
```

- [ ] **Step 3: Commit**

```bash
git add client/src/views/public/CheckoutLanding.vue client/src/views/public/CheckoutSuccessView.vue
git commit -m "feat: track begin_checkout and purchase events"
```

---

### Task 6: Add device and email verification events

**Files:**
- Modify: `client/src/views/dashboard/DevicesView.vue`
- Modify: `client/src/views/public/VerifyEmailView.vue`

- [ ] **Step 1: Add `device_add` and `device_remove` events to `DevicesView.vue`**

In `client/src/views/dashboard/DevicesView.vue`, add import after the existing imports:

```js
import { useAnalytics } from '@/composables/useAnalytics'
```

Add after the store/i18n declarations (after `const { t, locale } = useI18n()`):

```js
const { trackEvent } = useAnalytics()
```

In the `handleLinkDevice` function, add tracking after the successful response. Replace:

```js
            if (linkRes.success) {
                await fetchDevices()
                showLinkModal.value = false
                linkToken.value = ''
            }
```

With:

```js
            if (linkRes.success) {
                trackEvent('device_add')
                await fetchDevices()
                showLinkModal.value = false
                linkToken.value = ''
            }
```

In the `executeUnlink` function, add tracking after the successful response. Replace:

```js
    if (response.success) {
      devices.value = devices.value.filter(d => d.device_uuid !== deviceUuid)
      showUnlinkModal.value = false
    }
```

With:

```js
    if (response.success) {
      trackEvent('device_remove')
      devices.value = devices.value.filter(d => d.device_uuid !== deviceUuid)
      showUnlinkModal.value = false
    }
```

- [ ] **Step 2: Add `email_verified` event to `VerifyEmailView.vue`**

In `client/src/views/public/VerifyEmailView.vue`, add import after the existing imports:

```js
import { useAnalytics } from '@/composables/useAnalytics'
```

Add after the store/router declarations (after `const authStore = useAuthStore()`):

```js
const { trackEvent } = useAnalytics()
```

In the `onMounted` callback, add tracking after successful verification. Replace:

```js
      if (response.success) {
        await authStore.fetchUser()
        router.replace('/dashboard?verified=1')
      }
```

With:

```js
      if (response.success) {
        trackEvent('email_verified')
        await authStore.fetchUser()
        router.replace('/dashboard?verified=1')
      }
```

- [ ] **Step 3: Verify build still succeeds**

Run from `client/`:
```bash
npm run build
```

Expected: Build succeeds with no errors.

- [ ] **Step 4: Commit**

```bash
git add client/src/views/dashboard/DevicesView.vue client/src/views/public/VerifyEmailView.vue
git commit -m "feat: track device_add, device_remove, and email_verified events"
```

---

### Task 7: Update Privacy Policy — Vue template

**Files:**
- Modify: `client/src/views/public/PrivacyPolicy.vue`

- [ ] **Step 1: Update the Third-Party Services section and add Analytics section**

In `client/src/views/public/PrivacyPolicy.vue`, replace the entire third-party section (lines 82-93):

```html
        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.third_party_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.third_party_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li><strong class="text-white">{{ $t('privacy.third_party_item_1_label') }}</strong> {{ $t('privacy.third_party_item_1_text') }}</li>
          </ul>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.third_party_no_analytics') }}
          </p>
        </section>
```

With:

```html
        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.third_party_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.third_party_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li><strong class="text-white">{{ $t('privacy.third_party_item_1_label') }}</strong> {{ $t('privacy.third_party_item_1_text') }}</li>
            <li><strong class="text-white">{{ $t('privacy.third_party_item_2_label') }}</strong> {{ $t('privacy.third_party_item_2_text') }}</li>
          </ul>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.analytics_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.analytics_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li>{{ $t('privacy.analytics_item_1') }}</li>
            <li>{{ $t('privacy.analytics_item_2') }}</li>
            <li>{{ $t('privacy.analytics_item_3') }}</li>
          </ul>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.analytics_cookies_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li><strong class="text-white">_ga:</strong> {{ $t('privacy.analytics_cookie_ga') }}</li>
            <li><strong class="text-white">_ga_*:</strong> {{ $t('privacy.analytics_cookie_ga_id') }}</li>
          </ul>
          <p class="text-secondary leading-relaxed mb-4">
            <i18n-t keypath="privacy.analytics_optout">
              <template #cookieSettings>
                <button class="text-primary hover:underline inline-link" @click="openCookieSettings">{{ $t('privacy.analytics_optout_cookie_link') }}</button>
              </template>
              <template #browserAddon>
                <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">{{ $t('privacy.analytics_optout_addon_link') }}</a>
              </template>
            </i18n-t>
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.marketing_tools_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.marketing_tools_text') }}
          </p>
        </section>
```

- [ ] **Step 2: Add the cookie settings button handler and import**

In the `<script setup>` block, replace:

```js
import AppCard from '@/components/ui/AppCard.vue'
```

With:

```js
import AppCard from '@/components/ui/AppCard.vue'
import { useCookieConsent } from '@/composables/useCookieConsent'

const { openBanner } = useCookieConsent()
const openCookieSettings = () => openBanner()
```

- [ ] **Step 3: Add the inline-link style**

In the `<style scoped>` block, add:

```css
.inline-link {
  background: none;
  border: none;
  cursor: pointer;
  font: inherit;
  padding: 0;
}
```

- [ ] **Step 4: Update section numbering in template**

The sections after the new ones need renumbered. The current section numbers are i18n keys, so the renumbering happens in the locale files (Task 8). The template itself uses `$t()` calls so no numbering changes are needed in the template.

- [ ] **Step 5: Commit**

```bash
git add client/src/views/public/PrivacyPolicy.vue
git commit -m "feat: update privacy policy template with analytics sections"
```

---

### Task 8: Update English locale file

**Files:**
- Modify: `client/src/i18n/locales/en.json`

- [ ] **Step 1: Update privacy section in English locale**

In `client/src/i18n/locales/en.json`, make these changes to the `privacy` object:

**Update `last_updated`:**
```json
"last_updated": "Last updated: March 31, 2026",
```

**Update `third_party_text` to plural:**
```json
"third_party_text": "We use the following third-party services:",
```

**Add new keys after `third_party_item_1_text`:**
```json
"third_party_item_2_label": "Google Analytics (via Google Tag Manager):",
"third_party_item_2_text": "For website analytics to understand how visitors use our site. Only activated after you consent via our cookie banner. Google's privacy policy governs their processing of this data.",
```

**Remove `third_party_no_analytics`** (the line that says "We do not use any third-party analytics...").

**Add new analytics section keys after the removed `third_party_no_analytics`. Add these new keys before `rights_heading`:**
```json
"analytics_heading": "7. Analytics and Tracking",
"analytics_text": "We use Google Analytics 4 (via Google Tag Manager) to understand how visitors interact with our website. This helps us improve the user experience. The following data may be collected when you consent to analytics cookies:",
"analytics_item_1": "Pages visited and navigation paths",
"analytics_item_2": "Session duration and interaction patterns",
"analytics_item_3": "Device type, browser, and approximate location (country/city level from anonymized IP)",
"analytics_cookies_text": "Analytics cookies are only set after you give consent via our cookie banner (Google Consent Mode v2). The following cookies may be placed:",
"analytics_cookie_ga": "Distinguishes unique visitors. Duration: 2 years.",
"analytics_cookie_ga_id": "Maintains session state. Duration: 2 years.",
"analytics_optout": "You can opt out at any time by opening your {cookieSettings} from the site footer, or by installing the {browserAddon}.",
"analytics_optout_cookie_link": "cookie settings",
"analytics_optout_addon_link": "Google Analytics Opt-out Browser Add-on",
"marketing_tools_heading": "8. Future Marketing Tools",
"marketing_tools_text": "We may use additional marketing and analytics tools in the future (such as advertising pixels or heatmap services) to improve our service and marketing efforts. Any such tools will only be activated after you have given consent in the appropriate category (analytics or marketing) via our cookie banner. The cookie consent banner will always reflect the tools currently in use.",
```

**Renumber the remaining sections** — update the heading keys' values:
```json
"rights_heading": "9. Your Rights",
"retention_heading": "10. Data Retention",
"permissions_heading": "11. Device Permissions",
"children_heading": "12. Children's Privacy",
"changes_heading": "13. Changes to This Privacy Policy",
"contact_heading": "14. Contact",
```

- [ ] **Step 2: Commit**

```bash
git add client/src/i18n/locales/en.json
git commit -m "feat: add analytics privacy policy translations (English)"
```

---

### Task 9: Update Spanish locale file

**Files:**
- Modify: `client/src/i18n/locales/es.json`

- [ ] **Step 1: Apply same structural changes as English with Spanish translations**

In `client/src/i18n/locales/es.json`, make these changes to the `privacy` object:

**Update `last_updated`:**
```json
"last_updated": "Última actualización: 31 de marzo de 2026",
```

**Update `third_party_text`:**
```json
"third_party_text": "Utilizamos los siguientes servicios de terceros:",
```

**Add after `third_party_item_1_text`:**
```json
"third_party_item_2_label": "Google Analytics (a través de Google Tag Manager):",
"third_party_item_2_text": "Para análisis web y comprender cómo los visitantes utilizan nuestro sitio. Solo se activa después de que consientas a través de nuestro banner de cookies. La política de privacidad de Google rige el procesamiento de estos datos.",
```

**Remove `third_party_no_analytics`.**

**Add new analytics section keys before `rights_heading`:**
```json
"analytics_heading": "7. Análisis y Seguimiento",
"analytics_text": "Utilizamos Google Analytics 4 (a través de Google Tag Manager) para comprender cómo los visitantes interactúan con nuestro sitio web. Esto nos ayuda a mejorar la experiencia del usuario. Los siguientes datos pueden recopilarse cuando consientes las cookies de análisis:",
"analytics_item_1": "Páginas visitadas y rutas de navegación",
"analytics_item_2": "Duración de la sesión y patrones de interacción",
"analytics_item_3": "Tipo de dispositivo, navegador y ubicación aproximada (país/ciudad a partir de IP anonimizada)",
"analytics_cookies_text": "Las cookies de análisis solo se establecen después de que des tu consentimiento a través de nuestro banner de cookies (Google Consent Mode v2). Se pueden colocar las siguientes cookies:",
"analytics_cookie_ga": "Distingue visitantes únicos. Duración: 2 años.",
"analytics_cookie_ga_id": "Mantiene el estado de la sesión. Duración: 2 años.",
"analytics_optout": "Puedes desactivarlo en cualquier momento abriendo tu {cookieSettings} desde el pie de página del sitio, o instalando el {browserAddon}.",
"analytics_optout_cookie_link": "configuración de cookies",
"analytics_optout_addon_link": "Complemento de inhabilitación de Google Analytics",
"marketing_tools_heading": "8. Herramientas de Marketing Futuras",
"marketing_tools_text": "Podemos utilizar herramientas adicionales de marketing y análisis en el futuro (como píxeles publicitarios o servicios de mapas de calor) para mejorar nuestro servicio y esfuerzos de marketing. Cualquiera de estas herramientas solo se activará después de que hayas dado tu consentimiento en la categoría correspondiente (análisis o marketing) a través de nuestro banner de cookies. El banner de consentimiento de cookies siempre reflejará las herramientas actualmente en uso.",
```

**Renumber remaining sections:**
```json
"rights_heading": "9. Tus Derechos",
"retention_heading": "10. Retención de Datos",
"permissions_heading": "11. Permisos del Dispositivo",
"children_heading": "12. Privacidad de Menores",
"changes_heading": "13. Cambios en esta Política de Privacidad",
"contact_heading": "14. Contacto",
```

- [ ] **Step 2: Commit**

```bash
git add client/src/i18n/locales/es.json
git commit -m "feat: add analytics privacy policy translations (Spanish)"
```

---

### Task 10: Update remaining 6 locale files

**Files:**
- Modify: `client/src/i18n/locales/de.json`
- Modify: `client/src/i18n/locales/fr.json`
- Modify: `client/src/i18n/locales/pt.json`
- Modify: `client/src/i18n/locales/eu.json`
- Modify: `client/src/i18n/locales/ca.json`
- Modify: `client/src/i18n/locales/gl.json`

For each locale file, apply the same structural changes as Tasks 8-9 with appropriate translations for that language. The changes are identical in structure:

1. Update `last_updated` date
2. Update `third_party_text` to plural form
3. Add `third_party_item_2_label` and `third_party_item_2_text`
4. Remove `third_party_no_analytics`
5. Add all `analytics_*` and `marketing_tools_*` keys
6. Renumber sections 7→9, 8→10, 9→11, 10→12, 11→13, 12→14

- [ ] **Step 1: Update German (de.json)**

Apply translated analytics/privacy keys in German. Use professional German translations for all new keys.

- [ ] **Step 2: Update French (fr.json)**

Apply translated analytics/privacy keys in French.

- [ ] **Step 3: Update Portuguese (pt.json)**

Apply translated analytics/privacy keys in Portuguese.

- [ ] **Step 4: Update Basque (eu.json)**

Apply translated analytics/privacy keys in Basque.

- [ ] **Step 5: Update Catalan (ca.json)**

Apply translated analytics/privacy keys in Catalan.

- [ ] **Step 6: Update Galician (gl.json)**

Apply translated analytics/privacy keys in Galician.

- [ ] **Step 7: Verify build succeeds**

Run from `client/`:
```bash
npm run build
```

Expected: Build succeeds with no errors.

- [ ] **Step 8: Commit**

```bash
git add client/src/i18n/locales/de.json client/src/i18n/locales/fr.json client/src/i18n/locales/pt.json client/src/i18n/locales/eu.json client/src/i18n/locales/ca.json client/src/i18n/locales/gl.json
git commit -m "feat: add analytics privacy policy translations (de, fr, pt, eu, ca, gl)"
```

---

### Task 11: Final verification

**Files:** None (verification only)

- [ ] **Step 1: Run full build**

Run from `client/`:
```bash
npm run build
```

Expected: Build succeeds with no errors or warnings.

- [ ] **Step 2: Manual verification checklist**

Verify in the built output or dev server:
- GTM snippet is present in `<head>` of the page source
- Cookie banner appears on first visit
- Accepting analytics cookies triggers consent update (check browser console for `dataLayer` pushes)
- Privacy policy page renders with new analytics sections
- Section numbering is correct (1-14)
- All 8 languages render the privacy policy without missing translation warnings

- [ ] **Step 3: Remind about GTM configuration**

After deployment, configure GA4 inside the GTM web UI at tagmanager.google.com:
1. Create a GA4 Configuration tag with Measurement ID `G-3DYMD8YHDX`
2. Set trigger to "All Pages" with built-in consent checks enabled
3. Publish the GTM container
