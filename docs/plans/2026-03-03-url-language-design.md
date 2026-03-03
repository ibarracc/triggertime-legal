# Design: URL-based Language Setting

**Date:** 2026-03-03
**Status:** Approved

## Problem

When external tools (email campaigns, app deep-links, marketing materials) link to TriggerTime pages, there is no way to specify the desired language in the URL. Users arriving from a Spanish email campaign see the page in their browser's default language rather than Spanish.

## Goal

Allow any page URL to carry a `?lang=es` query parameter that sets the page language for the session, with the following priority rules (lowest to highest):

1. Default `'en'`
2. Browser language
3. `localStorage.preferredLanguage`
4. URL `?lang=` param (sets localStorage; only applied for unauthenticated users)
5. User profile language (applied on login or fetchUser — always wins)

## Supported Locales

`en`, `es`, `de`, `fr`, `pt`, `eu`, `ca`, `gl`

## What Already Exists (No Changes Needed)

- `users.language` DB column (migration `20260226000006_AddLanguageToUsers`)
- `AuthController::register()` — already saves `language` field
- `AuthController::updateProfile()` — already saves `language` field
- `AuthController::login()` + `me()` — already return `user.language`
- `useLocale.setLocale()` — handles dropdown changes (locale + localStorage + profile API call)
- `RegisterView.vue` — already reads `locale.value` and passes it to `register()`

## What Changes

### 1. `client/src/router/index.js`

Add logic in the `beforeEach` guard:

- Read `to.query.lang`
- If the value is a supported locale:
  - If user is **not authenticated**: apply to `i18n.global.locale.value`, `localStorage.preferredLanguage`, and `document.documentElement.lang`
  - In both cases (auth or not): strip `?lang=` from the URL by returning the same route with `lang` removed from query and `replace: true`
- This avoids infinite loops because the returned route won't have `?lang=`

### 2. `client/src/App.vue`

Add a reactive watcher on `auth.user`:

- When `auth.user` is set (covers both login and fetchUser completion), if `user.language` is a supported locale, apply it to `i18n.global.locale.value`, `localStorage.preferredLanguage`, and `document.documentElement.lang`
- This ensures profile language always overrides any URL param or localStorage value for authenticated users

## Behavior Summary

| Scenario | Result |
|---|---|
| Guest visits `/privacy?lang=es` | Page renders in Spanish, localStorage saves `es`, URL becomes `/privacy` |
| Authenticated user visits `/privacy?lang=fr` | URL cleaned silently, profile language unchanged, fetchUser applies profile lang |
| Guest registers after visiting `?lang=es` | Registration sends `es` as language (RegisterView already reads current locale) |
| User logs in | Profile `language` field applied to locale + localStorage |
| User changes dropdown | `setLocale()` updates locale + localStorage + backend profile (already works) |

## Files Changed

| File | Change |
|---|---|
| `client/src/router/index.js` | Add URL param detection + URL cleanup in `beforeEach` |
| `client/src/App.vue` | Add `watch(auth.user)` to apply profile language on login/fetchUser |
