# Privacy Policy & Terms of Service - Design Document

**Date:** 2026-03-02

## Overview

Recreate the privacy policy to reflect TriggerTime's current state (accounts, cloud sync, Stripe payments, device linking, admin panel) and create a new Terms of Service page. Both must be translated to all 8 supported languages. Add a terms acceptance checkbox to the registration form.

## Current State

- Privacy policy exists but describes a local-only app with no accounts
- No Terms of Service page exists (footer links to `/privacy` as placeholder)
- 8 languages: en, es, de, fr, pt, eu, ca, gl
- i18n via vue-i18n with JSON locale files

## Design

### Privacy Policy Sections

1. Introduction
2. Information We Collect (account data, training data, device info, payment info)
3. How We Use Your Information
4. Data Storage & Security (local-first, encrypted connections, password hashing)
5. Cloud Sync & Data Transfer (opt-in via Pro+)
6. Third-Party Services (Stripe only, no analytics/ads)
7. Your Rights (GDPR-aligned: access, correction, deletion, portability)
8. Data Retention
9. Device Permissions
10. Children's Privacy
11. Changes to This Policy
12. Contact

### Terms of Service Sections

1. Acceptance of Terms
2. Description of Service
3. Account Registration
4. Subscriptions & Payments (Pro+ via Stripe, recurring billing, cancellation)
5. Acceptable Use
6. Intellectual Property
7. User Data Ownership
8. Limitation of Liability
9. Termination
10. Governing Law (Spanish law, EU consumer protection)
11. Changes to Terms
12. Contact

### Registration Checkbox

- Add checkbox to registration form: "I accept the Terms of Service and Privacy Policy" (with links)
- Checkbox is required — form cannot submit without it
- Translated in all 8 languages

### Files to Modify/Create

- **Update:** 8 locale JSON files (`client/src/i18n/locales/*.json`) — `privacy` section rewritten, `terms` section added, `auth` section gets acceptance key
- **Update:** `client/src/views/public/PrivacyPolicy.vue` — new template matching new sections
- **Create:** `client/src/views/public/TermsOfService.vue` — same styling pattern
- **Update:** `client/src/router/index.js` — add `/terms` route
- **Update:** `client/src/components/layout/AppFooter.vue` — fix Terms link to `/terms`
- **Update:** Registration view — add terms acceptance checkbox

### Entity

- Use "TriggerTime" as the service provider (no formal legal entity specified)
- Contact: help@triggertime.es
- Governing law: Spain / EU
