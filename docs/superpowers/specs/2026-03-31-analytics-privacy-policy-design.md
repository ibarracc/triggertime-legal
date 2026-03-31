# Analytics Integration & Privacy Policy Update — Design Spec

**Date:** 2026-03-31
**Status:** Approved

## Overview

Add Google Tag Manager (GTM) with Google Analytics 4 (GA4) and Consent Mode v2 to the TriggerTime site. Update the privacy policy to reflect analytics usage and include a forward-looking clause for future marketing tools. Track key conversion events through the dataLayer.

## Credentials

- **GTM Container ID:** `GTM-5FFK3F55`
- **GA4 Measurement ID:** `G-3DYMD8YHDX` (configured inside GTM, not in code)

## Architecture

### 1. GTM + Consent Mode v2 in `client/index.html`

Add to `<head>`, before any other scripts:

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

Add to `<body>`, immediately after opening tag:

```html
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5FFK3F55"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
```

**Key behavior:**
- GTM loads on every page load (the script itself doesn't set cookies)
- All consent categories default to `denied`
- GA4, configured inside GTM, will not set cookies or collect identifying data until consent is granted
- `wait_for_update: 500` gives the cookie consent composable time to send the consent update on returning visitors

### 2. Analytics Composable — `client/src/composables/useAnalytics.js`

A new composable that:

1. **Initializes consent bridge on app startup** — reads current cookie consent state from `useCookieConsent` and calls `gtag('consent', 'update', ...)` to sync GTM's consent state. This handles returning visitors who already accepted cookies.
2. **Watches for consent changes** — when the user accepts/rejects cookies via the banner, immediately updates GTM consent state.
3. **Exposes `trackEvent(eventName, params)`** — pushes events to `window.dataLayer` for GTM to process.

```js
// Consent update call:
gtag('consent', 'update', {
  'analytics_storage': analyticsAllowed ? 'granted' : 'denied',
  'ad_storage': marketingAllowed ? 'granted' : 'denied',
  'ad_user_data': marketingAllowed ? 'granted' : 'denied',
  'ad_personalization': marketingAllowed ? 'granted' : 'denied',
})

// Event tracking call:
window.dataLayer.push({ event: eventName, ...params })
```

**Initialization:** Called once in `App.vue` via `useAnalytics()` in the `<script setup>` block.

### 3. Custom Conversion Events

| Event Name | Source File | Trigger | Parameters |
|---|---|---|---|
| `sign_up` | `stores/auth.js` — `register()` | After successful registration | `method: 'email'` |
| `login` | `stores/auth.js` — `login()` | After successful login | `method: 'email'` |
| `login` | `stores/auth.js` — `socialLogin()` | After successful social login/signup | `method: provider` |
| `begin_checkout` | `views/public/CheckoutLanding.vue` | When Stripe checkout is initiated | `plan: 'pro'` |
| `purchase` | `views/public/CheckoutSuccessView.vue` | On checkout success page load | `plan: 'pro'` |
| `device_add` | `views/dashboard/DevicesView.vue` | After successfully adding a device | — |
| `device_remove` | `views/dashboard/DevicesView.vue` | After successfully removing a device | — |
| `email_verified` | `views/public/VerifyEmailView.vue` | After successful email verification | — |
| `page_view` | `router/index.js` — `afterEach` | On every route navigation | `page_title`, `page_path` |

**Notes:**
- `sign_up`, `login`, `begin_checkout`, `purchase` are GA4 recommended event names — they auto-map to built-in GA4 reports
- `socialLogin()` fires `login` for both new and returning users since the API doesn't distinguish between them in the response. The `sign_up` event is only fired from `register()` (email registration)
- `device_add`, `device_remove`, `email_verified` are custom events — need to be defined as custom dimensions/events in GA4
- `page_view` is handled automatically by the router's `afterEach` hook
- The `trackEvent` function from `useAnalytics` will be imported where needed. For the auth store (not a Vue component), it will push directly to `window.dataLayer`

### 4. Privacy Policy Update

**Files affected:**
- `client/src/views/public/PrivacyPolicy.vue`
- All 8 locale files in `client/src/i18n/locales/` (en, es, de, fr, pt, eu, ca, gl)

**Changes:**

1. **Remove** the statement "We do not use any third-party analytics, advertising networks, or tracking services."

2. **Add section: "Analytics and Tracking"**
   - We use Google Analytics 4 (via Google Tag Manager) to understand how visitors use the site
   - Data collected: pages visited, session duration, device/browser info, approximate location (country/city level from IP, which is anonymized)
   - Analytics cookies are only set after the user gives consent via the cookie banner (Consent Mode v2)
   - Link to [Google's privacy policy](https://policies.google.com/privacy)
   - How to opt out: re-open cookie settings from the footer, or use [Google Analytics Opt-out Browser Add-on](https://tools.google.com/dlpage/gaoptout)

3. **Add section: "Third-Party Marketing Tools"**
   - We may use additional marketing and analytics tools in the future (e.g., advertising pixels, heatmap tools) to improve our service and marketing efforts
   - Any such tools will only activate after the user has given consent in the appropriate category (analytics or marketing) via the cookie banner
   - The cookie consent banner will always reflect the tools currently in use

4. **Update "Cookies" section** to list GA4-specific cookies:
   - `_ga` — Distinguishes unique users. Duration: 2 years.
   - `_ga_<container-id>` — Maintains session state. Duration: 2 years.
   - Both only set when analytics consent is granted.

5. **Update "Last Updated" date** to 2026-03-31.

All text translated to all 8 supported languages.

### 5. GA4 Configuration in GTM (Manual Step)

After the code changes are deployed, configure GA4 inside the GTM web UI:

1. Create a GA4 Configuration tag with Measurement ID `G-3DYMD8YHDX`
2. Set trigger to "Consent Initialization - All Pages" (or "All Pages" with consent checks enabled)
3. Enable "Send a page view event when this configuration loads"
4. Publish the GTM container

This is a manual step done in the GTM web interface, not in code.

## Files Changed

| File | Change |
|---|---|
| `client/index.html` | Add GTM snippet + Consent Mode v2 defaults |
| `client/src/composables/useAnalytics.js` | **New file** — consent bridge + event tracking |
| `client/src/App.vue` | Initialize `useAnalytics()` |
| `client/src/router/index.js` | Add `page_view` event in `afterEach` |
| `client/src/stores/auth.js` | Add `sign_up` and `login` events |
| `client/src/views/public/CheckoutLanding.vue` | Add `begin_checkout` event |
| `client/src/views/public/CheckoutSuccessView.vue` | Add `purchase` event |
| `client/src/views/dashboard/DevicesView.vue` | Add `device_add` and `device_remove` events |
| `client/src/views/public/VerifyEmailView.vue` | Add `email_verified` event |
| `client/src/views/public/PrivacyPolicy.vue` | Update privacy policy content |
| `client/src/i18n/locales/*.json` (x8) | Add/update analytics and privacy translation keys |

## Out of Scope

- Meta Pixel / Facebook ads tracking (add later via GTM when running ads)
- Server-side event tracking (subscription cancellations tracked in Stripe)
- Heatmap tools like Hotjar or Clarity (add later via GTM)
- E-commerce revenue tracking (purchase event does not include currency/value — can be added later)
