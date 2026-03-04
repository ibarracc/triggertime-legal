# Marketing Opt-in & Account Deletion Design

**Date:** 2026-03-04
**Status:** Approved

## Overview

Three related features:
1. Marketing communications opt-in during registration (GDPR-compliant)
2. Marketing opt-out toggle in profile
3. Account deletion with subscription guard and 30-day purge

## GDPR Compliance

Per EU GDPR (Art. 7, Recital 32):
- Marketing consent must be **explicit opt-in** (unchecked by default)
- **Pre-checked boxes are NOT allowed**
- Consent must be **separate** from Terms & Privacy acceptance
- Applies equally to all registration methods including SSO (Google/Apple)
- Users must be able to withdraw consent at any time (profile toggle)

## Database Changes

### New migration: `AddMarketingOptinToUsers`

Add to `users` table:
- `marketing_optin` BOOLEAN, default `false`, NOT NULL

## Backend Changes

### AuthController

**`register()`** — Accept optional `marketing_optin` boolean, persist to user entity.

**`socialLogin()`** — Accept optional `marketing_optin` for new user creation only.

**`updateProfile()`** — Accept `marketing_optin` to allow toggling.

**`deleteAccount()` (new)** — New endpoint `DELETE /api/v1/web/me`:
1. Validate request body contains `email` matching authenticated user's email
2. Check subscription: block if user has active paid subscription (plan != 'free' AND status == 'active' AND subscription hasn't expired)
3. Soft-delete user (sets `deleted_at` via SoftDelete behavior)
4. Return success with message about 30-day deletion

**Subscription eligibility for deletion — allow if:**
- No subscription exists, OR
- Plan is 'free', OR
- Status is not 'active', OR
- `cancel_at_period_end` is true AND `current_period_end` < now (expired)

**Block deletion if:**
- Active paid subscription that hasn't expired

### PurgeDeletedUsersCommand (new)

CakePHP console command: `bin/cake purge_deleted_users`
- Finds users where `deleted_at` < 30 days ago
- Hard-deletes: user, subscriptions, devices, social_accounts, activation_licenses
- Intended to run as a daily cron job
- Logs deletions for audit

### getMe() response

Add `marketing_optin` to the user data returned by `getMe()`.
Add `subscription` object (or at least `has_active_paid_subscription` boolean) so frontend can determine delete eligibility.

## Frontend Changes

### RegisterView.vue

- Add unchecked checkbox below Terms & Privacy: translatable marketing consent text
- Pass `marketing_optin` boolean to both `auth.register()` and `auth.socialLogin()`
- Checkbox is optional — does not block form submission

### ProfileView.vue

**Communications section:**
- Toggle switch (not checkbox) for marketing opt-in/opt-out
- Saves via `authApi.updateProfile({ marketing_optin })`
- Reflects current value from `getMe()` response

**Delete Account section (bottom of page):**
- Red-styled danger zone section
- If active paid subscription → disabled state with message: "Cancel your subscription and wait for it to expire before deleting your account"
- If eligible → "Delete Account" button opens confirmation modal
- Modal: "Type your email to confirm. Account deactivated immediately, permanently deleted after 30 days."
- Text input must match user's email exactly
- On success: clear auth token, redirect to login with message

### LoginView.vue

- Display "account deactivated" message when redirected from deletion (via query param)

### auth.js API module

- Update `register()` to accept `marketing_optin`
- Update `socialLogin()` to accept `marketing_optin`
- Add `deleteAccount(email)` — `DELETE /api/v1/web/me`

### i18n

Add keys for all 8 languages:
- `register.marketingOptin` — marketing consent checkbox label
- `profile.communications` — section title
- `profile.marketingOptin` — toggle label
- `profile.deleteAccount` — section title
- `profile.deleteAccountDescription` — explanation text
- `profile.deleteAccountDisabled` — subscription warning
- `profile.deleteAccountConfirm` — modal title
- `profile.deleteAccountConfirmText` — modal body
- `profile.deleteAccountSuccess` — success message
- `profile.typeEmailToConfirm` — input placeholder
- `login.accountDeactivated` — deactivation notice

## API Endpoints Summary

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/v1/web/register` | None | Updated: accepts `marketing_optin` |
| POST | `/api/v1/web/social-login` | None | Updated: accepts `marketing_optin` |
| PUT | `/api/v1/web/me` | JWT | Updated: accepts `marketing_optin` |
| DELETE | `/api/v1/web/me` | JWT | New: delete account |
| GET | `/api/v1/web/me` | JWT | Updated: returns `marketing_optin` + subscription info |
