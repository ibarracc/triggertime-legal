# Privacy Policy, Terms of Service & Registration Checkbox Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the outdated privacy policy with one reflecting the full app (accounts, payments, cloud sync), create a terms of service page, translate both to all 8 languages, and add a terms acceptance checkbox to the registration form.

**Architecture:** i18n-driven content in JSON locale files, rendered by Vue components. Same pattern as existing privacy policy. New `/terms` route added to Vue Router. Registration form gets a required checkbox with links to both legal pages.

**Tech Stack:** Vue 3 (Composition API), vue-i18n, Vue Router, existing AppCard/AppInput/AppButton components

---

### Task 1: Update English locale — Privacy Policy

**Files:**
- Modify: `client/src/i18n/locales/en.json` — replace the `"privacy"` object (lines 192-219)

Replace the entire `"privacy"` section with:

```json
"privacy": {
    "title": "Privacy Policy for TriggerTime",
    "last_updated": "Last updated: March 2, 2026",
    "intro_heading": "1. Introduction",
    "intro_text": "Welcome to TriggerTime. We are committed to protecting your privacy while providing you with the best sport shooting analytics experience. This Privacy Policy explains what information we collect, how we use it, and the choices you have. Our core principle remains: your data is yours, and we collect only what is necessary to provide the service.",
    "collection_heading": "2. Information We Collect",
    "collection_account_heading": "Account Information",
    "collection_account_text": "When you create a TriggerTime account, we collect your email address, first name, last name, and preferred language. Your password is securely hashed and never stored in plain text.",
    "collection_training_heading": "Training & Session Data",
    "collection_training_text": "TriggerTime allows you to record shooting scores, training dates, ammunition types, equipment notes, and other session-related data. By default, this data is stored exclusively on your device. If you subscribe to Pro+ and enable cloud sync, this data is transmitted to our servers to allow multi-device access.",
    "collection_device_heading": "Device Information",
    "collection_device_text": "When you link a device to your account, we store a device identifier to manage your authorized devices and deliver Pro+ features.",
    "collection_payment_heading": "Payment Information",
    "collection_payment_text": "Payments are processed entirely by Stripe. TriggerTime never receives, stores, or has access to your credit card numbers or banking details. We only receive confirmation of your subscription status from Stripe.",
    "usage_heading": "3. How We Use Your Information",
    "usage_text": "We use the information we collect to:",
    "usage_item_1": "Provide, maintain, and improve the TriggerTime service",
    "usage_item_2": "Manage your account and authenticate your sessions",
    "usage_item_3": "Process subscriptions and deliver Pro+ features to your linked devices",
    "usage_item_4": "Respond to your support requests",
    "usage_item_5": "Send essential service communications (e.g., password resets)",
    "storage_heading": "4. Data Storage & Security",
    "storage_text": "We take the security of your data seriously:",
    "storage_item_1": "All data transmission between your devices and our servers uses encrypted HTTPS connections",
    "storage_item_2": "Passwords are cryptographically hashed using industry-standard algorithms",
    "storage_item_3": "Authentication uses secure JWT tokens with limited validity",
    "storage_item_4": "Your training data stays on your device unless you explicitly enable cloud sync via a Pro+ subscription",
    "sync_heading": "5. Cloud Sync & Data Transfer",
    "sync_text": "TriggerTime follows a local-first philosophy:",
    "sync_item_1": "Basic app usage requires no internet connection and no data leaves your device",
    "sync_item_2": "Cloud sync is entirely opt-in and only available to Pro+ subscribers",
    "sync_item_3": "If you enable sync, your training data is transmitted to our servers solely to provide multi-device access",
    "sync_item_4": "You can disable sync at any time; your local data always remains on your device",
    "sync_item_5": "If you delete the app without an active sync, your locally stored data is permanently lost",
    "third_party_heading": "6. Third-Party Services",
    "third_party_text": "We use the following third-party service:",
    "third_party_item_1_label": "Stripe:",
    "third_party_item_1_text": "For secure payment processing of Pro+ subscriptions. Stripe's privacy policy governs the handling of your payment data.",
    "third_party_no_analytics": "We do not use any third-party analytics, advertising networks, or tracking services. Your privacy is not our business model.",
    "rights_heading": "7. Your Rights",
    "rights_text": "In accordance with applicable data protection regulations (including the EU General Data Protection Regulation), you have the right to:",
    "rights_item_1": "Access the personal data we hold about you",
    "rights_item_2": "Request correction of inaccurate data",
    "rights_item_3": "Request deletion of your account and all associated data",
    "rights_item_4": "Export your training data",
    "rights_item_5": "Withdraw consent for data processing at any time",
    "rights_contact": "To exercise any of these rights, contact us at {email}.",
    "retention_heading": "8. Data Retention",
    "retention_text": "We retain your account and associated data for as long as your account is active. If you request account deletion, we will permanently remove all your personal data and training records from our servers within 30 days. Locally stored data on your device is not affected by account deletion.",
    "permissions_heading": "9. Device Permissions",
    "permissions_text": "The TriggerTime mobile application may request the following device permissions, used strictly for app functionality:",
    "permissions_storage_label": "Storage / Files:",
    "permissions_storage_text": "To save the local database of your scores and training sessions on your device.",
    "children_heading": "10. Children's Privacy",
    "children_text": "TriggerTime is not directed at children under 16 years of age. We do not knowingly collect personal information from children. If you believe a child has provided us with personal data, please contact us and we will delete it promptly.",
    "changes_heading": "11. Changes to This Privacy Policy",
    "changes_text": "We may update this Privacy Policy from time to time. We will notify you of significant changes by posting the updated policy on this page with a new \"Last updated\" date. We encourage you to review this policy periodically.",
    "contact_heading": "12. Contact",
    "contact_text": "If you have any questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us at {email}."
}
```

**Step 1:** Replace the `"privacy"` object in `en.json` with the content above.

**Step 2:** Verify the JSON is valid by running:
```bash
cd client && node -e "JSON.parse(require('fs').readFileSync('src/i18n/locales/en.json', 'utf8')); console.log('OK')"
```

---

### Task 2: Update English locale — Terms of Service + Auth key

**Files:**
- Modify: `client/src/i18n/locales/en.json` — add `"terms"` object after `"privacy"`, add `"accept_terms"` to `"auth"` section

Add the `"terms"` section (after the `"privacy"` section, before `"notFound"`):

```json
"terms": {
    "title": "Terms of Service for TriggerTime",
    "last_updated": "Last updated: March 2, 2026",
    "acceptance_heading": "1. Acceptance of Terms",
    "acceptance_text": "By creating an account or using TriggerTime, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use the service.",
    "service_heading": "2. Description of Service",
    "service_text": "TriggerTime is a sport shooting analytics application that allows users to record, track, and analyze their shooting training sessions. The service includes a free tier with local-only data storage and an optional paid Pro+ subscription offering cloud sync, multi-device access, and additional features.",
    "account_heading": "3. Account Registration",
    "account_text": "To access certain features, you must create an account. You agree to:",
    "account_item_1": "Provide accurate and complete registration information",
    "account_item_2": "Maintain the security of your password and account",
    "account_item_3": "Notify us immediately of any unauthorized use of your account",
    "account_item_4": "Be responsible for all activity that occurs under your account",
    "subscription_heading": "4. Subscriptions & Payments",
    "subscription_text": "Pro+ subscriptions are billed on a recurring basis through Stripe. By subscribing, you agree to:",
    "subscription_item_1": "Pay the applicable subscription fees at the current rate",
    "subscription_item_2": "Automatic renewal at the end of each billing period unless cancelled",
    "subscription_item_3": "Cancellation takes effect at the end of the current billing period — you retain access until then",
    "subscription_item_4": "Refunds are handled in accordance with applicable consumer protection laws",
    "use_heading": "5. Acceptable Use",
    "use_text": "You agree not to:",
    "use_item_1": "Use the service for any unlawful purpose",
    "use_item_2": "Attempt to gain unauthorized access to our systems or other users' accounts",
    "use_item_3": "Reverse engineer, decompile, or disassemble the application",
    "use_item_4": "Use automated systems to access the service in a manner that exceeds reasonable use",
    "use_item_5": "Share your account credentials with others or allow third-party access to your account",
    "ip_heading": "6. Intellectual Property",
    "ip_text": "The TriggerTime application, including its design, code, logos, and content, is the intellectual property of TriggerTime. You are granted a limited, non-exclusive, non-transferable license to use the application for its intended purpose.",
    "data_heading": "7. User Data Ownership",
    "data_text": "Your training data belongs to you. TriggerTime does not claim ownership over any data you input into the application. You may export or delete your data at any time. We act only as a custodian of your cloud-synced data to provide the service.",
    "liability_heading": "8. Limitation of Liability",
    "liability_text": "TriggerTime is provided \"as is\" without warranties of any kind. To the maximum extent permitted by law:",
    "liability_item_1": "We do not guarantee uninterrupted or error-free service",
    "liability_item_2": "We are not liable for any data loss resulting from device failure, app deletion, or service interruption",
    "liability_item_3": "Our total liability shall not exceed the amount you have paid for the service in the 12 months preceding the claim",
    "termination_heading": "9. Termination",
    "termination_text": "Either party may terminate this agreement at any time:",
    "termination_item_1": "You may delete your account at any time through the application or by contacting us",
    "termination_item_2": "We may suspend or terminate your account if you violate these terms",
    "termination_item_3": "Upon termination, you may request export of your data before it is deleted from our servers",
    "law_heading": "10. Governing Law",
    "law_text": "These Terms of Service are governed by the laws of Spain and applicable European Union regulations. Any disputes shall be resolved in the competent courts of Spain, without prejudice to any mandatory consumer protection provisions of your country of residence.",
    "changes_heading": "11. Changes to Terms",
    "changes_text": "We may modify these Terms of Service from time to time. We will notify you of significant changes by posting updated terms on this page. Continued use of the service after changes constitutes acceptance of the modified terms.",
    "contact_heading": "12. Contact",
    "contact_text": "For any questions about these Terms of Service, please contact us at {email}."
}
```

Also add to the `"auth"` section:
```json
"accept_terms": "I accept the {terms} and {privacy}"
```

**Step 1:** Add the `"terms"` object and the `"auth.accept_terms"` key to `en.json`.

**Step 2:** Verify JSON validity:
```bash
cd client && node -e "JSON.parse(require('fs').readFileSync('src/i18n/locales/en.json', 'utf8')); console.log('OK')"
```

---

### Task 3: Update Spanish locale (es.json)

**Files:**
- Modify: `client/src/i18n/locales/es.json` — replace `"privacy"`, add `"terms"`, add `"auth.accept_terms"`

Translate all privacy and terms content to Spanish. Replace the existing `"privacy"` section, add a new `"terms"` section, and add `"accept_terms"` to `"auth"`.

**Step 1:** Replace/add the translated content.

**Step 2:** Verify JSON validity.

---

### Task 4: Update German locale (de.json)

Same structure as Task 3, translated to German.

---

### Task 5: Update French locale (fr.json)

Same structure as Task 3, translated to French.

---

### Task 6: Update Portuguese locale (pt.json)

Same structure as Task 3, translated to Portuguese.

---

### Task 7: Update Basque locale (eu.json)

Same structure as Task 3, translated to Basque (Euskara).

---

### Task 8: Update Catalan locale (ca.json)

Same structure as Task 3, translated to Catalan.

---

### Task 9: Update Galician locale (gl.json)

Same structure as Task 3, translated to Galician (Galego).

---

### Task 10: Update PrivacyPolicy.vue

**Files:**
- Modify: `client/src/views/public/PrivacyPolicy.vue` — rewrite the template to match the new 12-section structure

The template should follow the same styling pattern (AppCard, prose, sections with headings + paragraphs + lists) but with the new sections:

1. Introduction
2. Information We Collect (with 4 sub-sections: Account, Training, Device, Payment)
3. How We Use Your Information (5-item list)
4. Data Storage & Security (4-item list)
5. Cloud Sync & Data Transfer (5-item list)
6. Third-Party Services (Stripe item + no-analytics statement)
7. Your Rights (5-item list + contact paragraph with i18n-t email interpolation)
8. Data Retention
9. Device Permissions (same as before)
10. Children's Privacy
11. Changes to This Privacy Policy
12. Contact (with i18n-t email interpolation)

Keep the same `<script setup>` and `<style scoped>` blocks. Use the same CSS classes and patterns.

**Step 1:** Rewrite the template section of `PrivacyPolicy.vue`.

**Step 2:** Verify it renders by running `npm run dev` from `client/` and visiting `/privacy`.

---

### Task 11: Create TermsOfService.vue

**Files:**
- Create: `client/src/views/public/TermsOfService.vue`

Create a new component following the exact same pattern as `PrivacyPolicy.vue` (AppCard wrapper, prose layout, sections). The template renders the 12 sections from the `"terms"` i18n keys:

1. Acceptance of Terms
2. Description of Service
3. Account Registration (4-item list)
4. Subscriptions & Payments (4-item list)
5. Acceptable Use (5-item list)
6. Intellectual Property
7. User Data Ownership
8. Limitation of Liability (3-item list)
9. Termination (3-item list)
10. Governing Law
11. Changes to Terms
12. Contact (with i18n-t email interpolation)

Copy the `<style scoped>` block from `PrivacyPolicy.vue` exactly.

---

### Task 12: Add /terms route and fix footer

**Files:**
- Modify: `client/src/router/index.js` — add `/terms` route after the `/privacy` route (line 27)
- Modify: `client/src/components/layout/AppFooter.vue` — change terms link from `/privacy` to `/terms`

**Step 1:** In `router/index.js`, add after the Privacy route (line 27):
```javascript
{
    path: '/terms',
    name: 'Terms',
    component: () => import('../views/public/TermsOfService.vue'),
    meta: { requiresAuth: false }
},
```

**Step 2:** In `AppFooter.vue`, change line 12 from:
```vue
<router-link to="/privacy" class="nav-link">{{ $t('footer.terms') }}</router-link>
```
to:
```vue
<router-link to="/terms" class="nav-link">{{ $t('footer.terms') }}</router-link>
```

---

### Task 13: Add terms acceptance checkbox to RegisterView

**Files:**
- Modify: `client/src/views/public/RegisterView.vue`

**Step 1:** Add a `termsAccepted` ref (after `confirmPassword` ref):
```javascript
const termsAccepted = ref(false)
```

**Step 2:** Add a checkbox between the confirm password input and the error message div (after line 57, before line 59). Use `i18n-t` for the label with router-link slots:

```vue
<div class="terms-checkbox mb-4">
  <label class="flex items-start gap-2 cursor-pointer text-sm text-secondary">
    <input
      v-model="termsAccepted"
      type="checkbox"
      class="terms-input mt-0.5"
      required
    />
    <span>
      <i18n-t keypath="auth.accept_terms">
        <template #terms>
          <router-link to="/terms" target="_blank" class="text-primary hover-underline">{{ $t('footer.terms') }}</router-link>
        </template>
        <template #privacy>
          <router-link to="/privacy" target="_blank" class="text-primary hover-underline">{{ $t('footer.privacy') }}</router-link>
        </template>
      </i18n-t>
    </span>
  </label>
</div>
```

**Step 3:** Add validation in `handleRegister` (before the password mismatch check):
```javascript
if (!termsAccepted.value) {
  errorMsg.value = t('auth.accept_terms_required')
  return
}
```

Also add the `auth.accept_terms_required` key to ALL 8 locale files:
- en: `"accept_terms_required": "You must accept the Terms of Service and Privacy Policy to create an account"`
- es: `"accept_terms_required": "Debes aceptar los Términos de Servicio y la Política de Privacidad para crear una cuenta"`
- (and so on for all languages)

**Step 4:** Add scoped styles for the checkbox:
```css
.terms-checkbox input[type="checkbox"] {
  accent-color: var(--primary);
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.flex { display: flex; }
.items-start { align-items: flex-start; }
.gap-2 { gap: 0.5rem; }
.cursor-pointer { cursor: pointer; }
.mt-0\.5 { margin-top: 0.125rem; }
```

---

### Task 14: Build and verify

**Step 1:** Run the Vue dev server and manually verify:
```bash
cd client && npm run dev
```
- Visit `/privacy` — verify new 12-section privacy policy renders
- Visit `/terms` — verify new 12-section terms page renders
- Visit `/register` — verify checkbox appears and form cannot submit without it
- Switch languages and verify translations work
- Check footer links go to correct pages

**Step 2:** Run production build:
```bash
cd client && npm run build
```

**Step 3:** Commit all changes.
