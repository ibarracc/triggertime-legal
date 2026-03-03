# URL Language Setting Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Allow any page URL to carry `?lang=es` to set the page language for the session, with profile language always winning for authenticated users.

**Architecture:** Two minimal frontend changes — (1) `beforeEach` router guard detects `?lang=` param, applies locale + cleans the URL; (2) `App.vue` watches `auth.user` to apply profile language on login/fetchUser. No backend changes needed (already complete).

**Tech Stack:** Vue 3, Vue Router 4, vue-i18n (Composition API), Pinia

**Design doc:** `docs/plans/2026-03-03-url-language-design.md`

---

## Context: What Already Works

These are complete and must NOT be changed:

- `users.language` DB column exists
- `AuthController::register()` saves `language`
- `AuthController::updateProfile()` saves `language`
- `AuthController::login()` + `me()` return `user.language`
- `useLocale.setLocale()` handles dropdown changes (locale + localStorage + profile API)
- `RegisterView.vue` already reads `locale.value` and sends it on `register()`

## Supported Locales

```js
const SUPPORTED_LOCALES = ['en', 'es', 'de', 'fr', 'pt', 'eu', 'ca', 'gl']
```

---

## Task 1: Add `?lang=` param detection to router

**Files:**
- Modify: `client/src/router/index.js`

The `beforeEach` guard must:
1. Read `to.query.lang`
2. Validate against `SUPPORTED_LOCALES`
3. If valid and user **not authenticated**: apply to locale + localStorage + `document.lang`
4. In all cases (with or without auth): return the same route with `lang` stripped from query and `replace: true`

This handles the URL cleanup so users never see `?lang=es` lingering.

**Step 1: Add the imports and constant at the top of `router/index.js`**

At the top of `client/src/router/index.js`, add these two lines after the existing imports:

```js
import i18n from '@/i18n'

const SUPPORTED_LOCALES = ['en', 'es', 'de', 'fr', 'pt', 'eu', 'ca', 'gl']
```

**Step 2: Add the lang param handler at the START of the existing `beforeEach` callback**

The existing guard is:
```js
router.beforeEach((to, from) => {
    const authStore = useAuthStore()

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // ...
```

Insert the new block BEFORE the existing auth checks, so the final guard looks like:

```js
router.beforeEach((to, from) => {
    const authStore = useAuthStore()

    // Handle ?lang= query param from external links
    const langParam = to.query.lang
    if (langParam && SUPPORTED_LOCALES.includes(langParam)) {
        if (!authStore.isAuthenticated) {
            // Apply locale for unauthenticated users
            i18n.global.locale.value = langParam
            localStorage.setItem('preferredLanguage', langParam)
            document.documentElement.lang = langParam
        }
        // Always strip ?lang= from URL (clean up regardless of auth state)
        const { lang, ...remainingQuery } = to.query
        return { ...to, query: remainingQuery, replace: true }
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return { name: 'Login', query: { redirect: to.fullPath } }
    } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
        return { name: 'Dashboard' }
    }

    if (to.meta.requiresAdminRole && !authStore.isAdmin && !authStore.isClubAdmin) {
        return { name: 'Dashboard' }
    }

    if (to.meta.requiresSuperAdmin && !authStore.isAdmin) {
        return { name: 'AdminLicenses' }
    }
})
```

**Step 3: Manual verification — guest user**

Open the browser at:
```
http://triggertime.ddev.site/privacy?lang=de
```

Expected:
- Page renders in German
- URL changes to `http://triggertime.ddev.site/privacy` (no `?lang=de`)
- `localStorage.getItem('preferredLanguage')` === `'de'` in DevTools console
- `document.documentElement.lang` === `'de'`

Also test an invalid locale does nothing:
```
http://triggertime.ddev.site/privacy?lang=xx
```
Expected: page renders in whatever language was previously set, URL stays as-is (no redirect).

**Step 4: Manual verification — authenticated user**

Log in as any user. Then visit:
```
http://triggertime.ddev.site/dashboard?lang=fr
```

Expected:
- URL changes to `http://triggertime.ddev.site/dashboard` (cleaned)
- Page language does NOT change to French (auth user locale is NOT overridden by URL)
- `localStorage.getItem('preferredLanguage')` does NOT change to `'fr'`

**Step 5: Commit**

```bash
git add client/src/router/index.js
git commit -m "feat: detect ?lang= query param in router to set locale for guests"
```

---

## Task 2: Apply profile language on login and fetchUser

**Files:**
- Modify: `client/src/App.vue`

When `auth.user` is set (covers both the login flow and the `fetchUser()` call on mount), apply `user.language` to the active locale, localStorage, and `document.lang`.

This ensures the user's profile language always wins over any previously set URL param or localStorage value.

**Step 1: Add imports to `App.vue`**

The current `<script setup>` starts with:
```js
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
```

Change it to:
```js
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
```

**Step 2: Add the watcher in `App.vue`**

After the existing `const auth = useAuthStore()` line, add:

```js
const { locale } = useI18n()

const SUPPORTED_LOCALES = ['en', 'es', 'de', 'fr', 'pt', 'eu', 'ca', 'gl']

watch(() => auth.user, (user) => {
  if (user?.language && SUPPORTED_LOCALES.includes(user.language)) {
    locale.value = user.language
    localStorage.setItem('preferredLanguage', user.language)
    document.documentElement.lang = user.language
  }
})
```

The `watch` does NOT need `{ immediate: true }` — `fetchUser()` is called in `onMounted`, so the watcher will fire when the user data arrives. `{ immediate: false }` is the default.

The final `App.vue` `<script setup>` block should look like:

```js
<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import PublicLayout from '@/components/layout/PublicLayout.vue'
import DashboardLayout from '@/components/layout/DashboardLayout.vue'
import CookieConsent from '@/components/CookieConsent.vue'

const route = useRoute()
const auth = useAuthStore()
const { locale } = useI18n()

const SUPPORTED_LOCALES = ['en', 'es', 'de', 'fr', 'pt', 'eu', 'ca', 'gl']

watch(() => auth.user, (user) => {
  if (user?.language && SUPPORTED_LOCALES.includes(user.language)) {
    locale.value = user.language
    localStorage.setItem('preferredLanguage', user.language)
    document.documentElement.lang = user.language
  }
})

const layoutComponent = computed(() => {
  if (route.path.startsWith('/dashboard') || route.path.startsWith('/admin')) {
    return DashboardLayout
  }
  return PublicLayout
})

onMounted(() => {
  if (auth.isAuthenticated) {
    auth.fetchUser()
  }
})
</script>
```

**Step 3: Manual verification — fetchUser on page reload**

1. Set your user's profile language to `de` via the language dropdown (or directly in DB)
2. Set `localStorage.preferredLanguage` to `en` in DevTools
3. Reload the page while logged in

Expected:
- Page renders initially in English (from localStorage)
- After `fetchUser()` completes, switches to German
- `localStorage.getItem('preferredLanguage')` === `'de'`

**Step 4: Manual verification — login flow**

1. Log out
2. Set `localStorage.preferredLanguage` to `fr` in DevTools
3. Log in with a user whose profile language is `es`

Expected:
- After login completes, page switches to Spanish
- `localStorage.getItem('preferredLanguage')` === `'es'`

**Step 5: Manual verification — end-to-end with URL param**

1. Log out
2. Visit `http://triggertime.ddev.site/privacy?lang=de` — page renders in German, URL cleans up
3. Register a new account (the language dropdown should default to German)
4. After registration, profile should have `language = 'de'`

To verify: check the DB or call `GET /api/v1/web/me` and inspect `user.language`.

**Step 6: Commit**

```bash
git add client/src/App.vue
git commit -m "feat: apply user profile language on login and fetchUser"
```

---

## Task 3: Build and verify

**Step 1: Run the frontend build**

From `client/`:
```bash
npm run build
```

Expected: exits with no errors, output in `../webroot/spa/`

**Step 2: Full end-to-end smoke test**

Test all scenarios from the behavior table in the design doc:

| URL | Auth state | Expected outcome |
|---|---|---|
| `/privacy?lang=es` | Guest | Spanish, URL cleaned, localStorage = `es` |
| `/privacy?lang=xx` | Guest | No change, URL unchanged |
| `/dashboard?lang=fr` | Logged in | URL cleaned, no language change |
| `/` | Logged in (profile=de) | German (from fetchUser) |

**Step 3: Commit build output**

```bash
git add webroot/spa/
git commit -m "chore: rebuild SPA with URL language feature"
```
