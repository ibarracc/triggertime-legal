# Pricing Display & Feature Accuracy — Design Spec

> **Date**: 2026-03-30
> **Status**: Draft
> **Scope**: Landing page, Dashboard home, Subscription view, i18n (8 locales)

---

## Problem

1. **Spacing**: Price amounts run into `/ month` and `/ forever` with no space (e.g., `$4.99/ month`)
2. **Club Pro+ missing info**: No indication that pricing is per-user with a 10-user minimum
3. **Inaccurate feature lists**: Dashboard subscription view previously listed features that don't exist yet (multi-device sync, advanced analytics) as current Pro+ features
4. **Free plan limit not shown**: The 10-session cap for free users was not displayed on the landing page pricing card

## Decisions Made

- Free plan pricing card explicitly shows "Up to 10 sessions" (option A)
- Club Pro+ displays `from $4.99 / user / month` with "Minimum 10 users" subtitle (option A)
- Price spacing: add space before slash
- Pro+ feature lists use the 4 actual features from iOS App Store doc
- Cloud sync and analytics listed as "(coming soon)" in the Pro+ features detail section only, NOT in pricing cards

## Changes

### 1. Spacing Fix — All Price Displays

**Files**: `LandingPage.vue`, `DashboardHome.vue`, `SubscriptionView.vue`

Add a space before the `/ month` and `/ forever` spans.

### 2. Club Pro+ Per-User Pricing — Landing Page

**File**: `LandingPage.vue`

Change Club Pro+ price from `from $4.99 / month` to `from $4.99 / user / month` with "Minimum 10 users" below.

New i18n keys: `subscription.per_user_month`, `landing.club_pro_min_users`

### 3. Landing Page Feature Lists (Already Done by User)

No further changes needed.

### 4. Dashboard SubscriptionView — Coming Soon Section

Add a "Coming Soon" section below the current features list with cloud sync and analytics.

New i18n keys: `subscription.coming_soon`, `subscription.coming_soon_sync`, `subscription.coming_soon_analytics`

### 5. Dashboard Home — Upsell Price Spacing

Fix spacing in the free-tier upsell price display.

### 6. i18n Updates — All 8 Locales

New keys to add, translations for keys already added by user in en.json, and cleanup of removed keys (`multi_device_sync`, `advanced_analytics`, `priority_support`).

## Files Modified

| File | Change |
|------|--------|
| `client/src/views/landing/LandingPage.vue` | Price spacing, Club Pro+ per-user pricing |
| `client/src/views/dashboard/DashboardHome.vue` | Price spacing fix |
| `client/src/views/dashboard/SubscriptionView.vue` | Price spacing fix, coming soon section |
| `client/src/i18n/locales/*.json` (8 files) | New keys, translations, cleanup |

## Out of Scope

- No backend changes
- No new Vue components
- Cloud sync and analytics features themselves (only "coming soon" labels)
