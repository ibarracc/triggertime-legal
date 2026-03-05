# Email Templates & Account Activation Design

**Date:** 2026-03-05
**Status:** Approved

## Summary

Add branded email templates, email verification workflow for normal signups, and welcome emails for all registration paths. SSO users are auto-verified. Unverified users are blocked from dashboard features but can purchase subscriptions.

## Decisions

- **Activation UX:** Parallel — users can subscribe or activate in any order
- **Restrictions:** Unverified users see only a "verify your email" page (can still purchase subscriptions via Stripe)
- **Email theme:** Light background with brand accent colors
- **Token mechanism:** HMAC-signed URLs (no extra DB table)
- **Token expiry:** 7 days
- **Welcome email:** Combined with activation for normal signup; separate welcome-only for SSO
- **i18n:** Emails translated using user's `language` field

## 1. Database Changes

### Migration: Add `email_verified_at` to `users`

- Column: `email_verified_at` (nullable timestamp)
- `NULL` = unverified, timestamp = when verified
- SSO users: set to `NOW()` at creation
- Existing users: backfill with `created_at`

## 2. Signed URL Verification

**Format:** `{base_url}/verify-email?uid={user_id}&exp={unix_timestamp}&sig={hmac_sha256}`

- Signature: `HMAC-SHA256(user_id + ":" + expiry, Security.salt)`
- Expiry: 7 days from creation
- Verification checks: valid signature + not expired + `email_verified_at IS NULL`
- After verification: sets `email_verified_at = NOW()`, redirects to SPA with `?verified=1`

## 3. Email Templates & Branding

### Shared Layout (`templates/email/html/branded.php`)

- White background, 600px centered container
- **Header:** Dark bar (`#0A0A0F`) with "TriggerTime" in primary green (`#C1FF72`)
- **Content:** White background, `Inter` font (sans-serif fallback), dark text
- **CTA buttons:** Primary green (`#C1FF72`) background, dark text (`#0A0A0F`), 12px radius
- **Footer:** Light gray, contact/unsubscribe links
- All inline CSS for email client compatibility

### Email Types

| Email | Trigger | Content |
|-------|---------|---------|
| Welcome + Activate | Normal signup | Welcome message, "Activate Your Account" button, subscription CTA if from subscription flow |
| Welcome (SSO) | SSO signup | Welcome message, explore dashboard CTA |
| Password Reset | Forgot password | Reset link button, 24h expiry notice (refactored from inline) |

### Mailer Class

`src/Mailer/UserMailer.php` with methods:
- `welcomeActivation(User $user, string $activationUrl)`
- `welcomeSso(User $user)`
- `passwordReset(User $user, string $resetUrl)`

### i18n

Email text uses CakePHP's `__()` function. Translations in `src/Locale/{lang}/default.po` for all 8 supported languages.

## 4. Registration Flows

### Normal Registration

1. User registers → API creates user with `email_verified_at = NULL`
2. Free subscription created (as before)
3. "Welcome + Activate" email sent with signed URL
4. JWT returned (user logged in but unverified)
5. Frontend detects unverified → shows verification page
6. User clicks activation link → `GET /api/v1/web/auth/verify-email`
7. Backend verifies → sets `email_verified_at = NOW()`
8. Redirects to SPA with `?verified=1` → success toast, user data refreshed

### SSO Registration

1. User authenticates via Google/Apple
2. User created with `email_verified_at = NOW()`
3. "Welcome (SSO)" email sent
4. User lands in dashboard immediately

### Subscription During Registration

1. User registers from "Get Pro" path (frontend sends `intent=subscribe`)
2. Normal flow: email sent, user logged in but unverified
3. Verification page shows with "Subscribe to Pro" button → Stripe Checkout
4. User can subscribe without verifying email first
5. Verification page remains until activation link is clicked
6. Activation email includes "Continue to your subscription" if from subscription flow

## 5. API Changes

### New Endpoints

| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| `GET` | `/api/v1/web/auth/verify-email` | None | Verify email via signed URL, redirect to SPA |
| `POST` | `/api/v1/web/auth/resend-verification` | JWT | Resend activation email (1/min rate limit) |

### Modified Endpoints

- `POST /api/v1/web/auth/register` — sends welcome+activation email
- `POST /api/v1/web/auth/social-login` — sets `email_verified_at = NOW()` for new users, sends welcome SSO email
- `GET /api/v1/web/me` — includes `email_verified_at` in response
- `POST /api/v1/web/auth/forgot-password` — refactored to use `UserMailer`

## 6. Backend Components

- `src/Mailer/UserMailer.php` — Mailer class
- `src/Service/EmailVerificationService.php` — `generateSignedUrl()`, `verifySignedUrl()`
- `templates/email/html/branded.php` — Shared branded HTML layout
- `templates/email/html/welcome_activation.php` — Welcome + activate
- `templates/email/html/welcome_sso.php` — Welcome SSO
- `templates/email/html/password_reset.php` — Password reset (refactored)
- `src/Locale/{lang}/default.po` — Email translations (8 languages)

## 7. Frontend Components

- `views/public/VerifyEmailView.vue` — Verification pending page (resend button + optional subscribe)
- `router/index.js` — `requiresVerified` guard on dashboard routes
- `stores/auth.js` — `isVerified` computed property
- `api/auth.js` — `resendVerification()` method
- Handle `?verified=1` on SPA entry → success toast
