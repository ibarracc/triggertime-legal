# Language Dropdown Fix + GDPR Cookie Consent — Design

## 1. Language Dropdown Fix

### Problem
The language `<select>` in RegisterView.vue and ProfileView.vue is a raw HTML element with inline utility classes. It looks different from other form fields that use the AppInput component (different padding, border-radius, font size, focus styles).

### Solution
Add `type="select"` support to the existing AppInput component. When `type="select"`, render a `<select>` element instead of `<input>`, styled identically. Pass options via a new `options` prop (array of `{ value, label }`).

### Usage
```vue
<AppInput
  v-model="language"
  :label="$t('common.language')"
  type="select"
  :options="availableLocales.map(l => ({ value: l.code, label: `${l.flag} ${l.name}` }))"
/>
```

### Changes
- **AppInput.vue**: Add `options` prop, conditionally render `<select>` when `type === 'select'`, same CSS classes as `<input>`.
- **RegisterView.vue**: Replace raw `<select>` block (lines 31-41) with `<AppInput type="select">`.
- **ProfileView.vue**: Replace raw `<select>` block (lines 34-44) with `<AppInput type="select">`.

---

## 2. GDPR Cookie Consent

### Requirements (EU ePrivacy Directive + GDPR)
- Prior consent before setting non-essential cookies
- Accept All / Reject All buttons with equal visual prominence
- Granular category management (essential, analytics, marketing)
- Essential cookies exempt from consent (always on, not toggleable)
- Consent persisted in localStorage
- Ability to change preferences later (footer link)

### Architecture

**Components:**
- `CookieConsent.vue` — Banner with Accept All / Reject All / Manage Preferences. Shows a preferences modal when "Manage Preferences" is clicked, with toggles per category.
- `useCookieConsent.js` — Composable managing state, localStorage persistence (`tt_cookie_consent` key), and helper methods.

**Cookie Categories:**
| Category | Examples | Default | Toggleable |
|----------|----------|---------|------------|
| Essential | Session, auth token, locale | Always on | No |
| Analytics | Google Analytics, Plausible | Off | Yes |
| Marketing | Facebook Pixel, ad tracking | Off | Yes |

**Storage Format (localStorage `tt_cookie_consent`):**
```json
{
  "essential": true,
  "analytics": false,
  "marketing": false,
  "timestamp": "2026-03-02T12:00:00Z"
}
```

**Banner Behavior:**
- Shows on first visit (no consent stored)
- Three buttons: "Accept All" (primary), "Reject All" (secondary), "Manage Preferences" (text link)
- "Manage Preferences" opens inline category toggles within the banner
- Fixed to bottom of viewport, above footer
- Dismissed after any choice; re-openable via footer link

**Footer Integration:**
- Add "Cookie Settings" link to AppFooter.vue
- Clicking re-opens the consent banner

**i18n:**
- ~15 new keys in a `cookies` section across all 8 locale files
- Keys: `banner_title`, `banner_text`, `accept_all`, `reject_all`, `manage`, `save_preferences`, `essential_title`, `essential_desc`, `analytics_title`, `analytics_desc`, `marketing_title`, `marketing_desc`, `always_on`

### Integration Points
- Mount `CookieConsent.vue` in `App.vue`
- No backend changes needed (localStorage only)
- Future: check `useCookieConsent().isAllowed('analytics')` before loading tracking scripts
