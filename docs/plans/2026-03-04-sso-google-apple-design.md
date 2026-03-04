# SSO Sign-In/Sign-Up with Google and Apple

## Summary

Add Google and Apple SSO to TriggerTime's existing email+password auth. Users can link SSO to existing accounts or create new SSO-only accounts. Backend verifies provider ID tokens directly against JWKS endpoints (no third-party OAuth libraries).

## Decisions

- **Account linking:** Users can have both SSO and password. Existing users matched by email get their social account linked automatically.
- **Frontend flow:** Popup-based (Google GIS + Apple JS SDK). No full-page redirects.
- **Token verification:** Backend-only. Frontend sends raw ID token, backend verifies against provider JWKS.
- **Approach:** Direct provider verification using `firebase/php-jwt` (already installed) + JWKS fetching. No League OAuth or Firebase Auth.

## Database Schema

### New table: `social_accounts`

| Column | Type | Notes |
|---|---|---|
| `id` | UUID | PK |
| `user_id` | UUID | FK → users.id |
| `provider` | string | `google` or `apple` |
| `provider_uid` | string | Provider's unique user ID |
| `created_at` | timestamp | |

- **Unique constraint** on `(provider, provider_uid)`
- **Index** on `(provider, provider_uid)` for fast login lookups

### Migration: `users.password_hash` becomes nullable

Existing users unaffected. SSO-only users will have `NULL` password_hash.

## Backend

### New endpoint: `POST /api/v1/web/auth/social-login`

**Request:**
```json
{
  "provider": "google" | "apple",
  "id_token": "<token from provider>",
  "first_name": "...",
  "last_name": "..."
}
```

`first_name`/`last_name` are optional fallbacks — needed for Apple which only sends name on first authorization.

**Flow:**

1. Validate ID token against provider's JWKS:
   - **Google:** Keys from `https://www.googleapis.com/oauth2/v3/certs`, verify `iss` = `accounts.google.com`, `aud` = configured client ID
   - **Apple:** Keys from `https://appleid.apple.com/auth/keys`, verify `iss` = `https://appleid.apple.com`, `aud` = configured service ID
2. Extract claims: `sub` (provider UID), `email`, `name` (if present)
3. Lookup `social_accounts` by `(provider, provider_uid)`:
   - **Found:** Load user, issue JWT
   - **Not found, email matches existing user:** Link social account to user, issue JWT
   - **Not found, no email match:** Create user (null password_hash), create social account, create free subscription, link B2B licenses
4. Return `{ token, user, subscription }` — same shape as existing login/register

### New service: `src/Service/SocialAuthService.php`

Responsibilities:
- Fetch and cache JWKS keys (CakePHP `Cache`, `social_auth` config, 24h TTL)
- Verify ID tokens using `firebase/php-jwt`
- Lookup/create users and social accounts

### Configuration: `config/app.php`

```php
'SocialAuth' => [
    'google' => [
        'clientId' => env('GOOGLE_CLIENT_ID', ''),
    ],
    'apple' => [
        'serviceId' => env('APPLE_SERVICE_ID', ''),
    ],
],
```

No client secrets needed — we only verify ID tokens, not exchange authorization codes.

## Frontend

### Provider SDKs

- **Google:** Google Identity Services (`accounts.google.com/gsi/client`) — handles popup, returns ID token via callback
- **Apple:** Apple JS SDK (`appleid.cdn-apple.com`) — popup-based, returns ID token. Name only sent on first authorization — frontend must capture and forward.

### New/modified files

| File | Change |
|---|---|
| `client/src/api/auth.js` | Add `socialLogin(provider, idToken, firstName, lastName)` |
| `client/src/stores/auth.js` | Add `socialLogin()` action |
| `client/src/views/public/LoginView.vue` | Add SSO buttons below form with "or" divider |
| `client/src/views/public/RegisterView.vue` | Add SSO buttons below form with "or" divider |
| `client/src/composables/useSocialAuth.js` | New composable: init SDKs, handle popups, expose `loginWithGoogle()` and `loginWithApple()` |

### Button placement

Below the email/password form, separated by an "or" divider. Google first, Apple second.

## Security

- **Token expiry:** Provider ID tokens expire in ~5-10 min. Backend rejects expired tokens.
- **Audience verification:** Backend checks `aud` matches configured client/service ID.
- **Email trust:** Both Google and Apple guarantee `email_verified: true`.
- **Apple privacy relay:** Accept whatever email Apple provides (real or relay) — stable per user.
- **No changes needed to:** JwtMiddleware, CSRF config, CORS config.

## Testing

### Backend

- **`SocialAuthServiceTest`:** Token verification with mocked JWKS, user creation, account linking, duplicate handling
- **`AuthControllerTest` (extend):** `social-login` endpoint — valid tokens, invalid/expired → 401, missing fields → 400, email linking, new user creation with free subscription

### Key scenarios

1. New user via Google → user + social account + free subscription
2. New user via Apple (hidden email) → same
3. Existing email user via Google → links account, no duplicate
4. Already-linked user logs in → returns existing user
5. Invalid/expired token → 401
6. Missing provider or id_token → 400
7. `password_hash` nullable migration doesn't break existing users

### Frontend

Manual testing of popup flows against real provider credentials.
