<template>
  <div class="landing-page">
    <!-- Hero Section -->
    <section class="hero">
      <HeroBackground />
      <div class="container hero-content">
        <AppBadge variant="primary" class="hero-badge">{{ $t('landing.hero_badge') }}</AppBadge>
        <h1 class="hero-title">
          {{ $t('landing.hero_title_1') }}<br />
          <span class="highlight">{{ $t('landing.hero_title_2') }}</span>
        </h1>
        <p class="hero-subtitle text-secondary">
          {{ $t('landing.hero_subtitle') }}
        </p>
        <div class="hero-actions">
          <router-link to="/register" class="btn btn-primary btn-lg">
            {{ $t('common.register') }}
          </router-link>
          <a href="#pricing" class="btn btn-secondary btn-lg">{{ $t('nav.pricing') }}</a>
        </div>

        <div class="download-badges">
            <a :href="appStoreUrl" class="badge-link">
              <img src="https://tools.applemediaservices.com/api/badges/download-on-the-app-store/black/en-us?size=250x83&amp;releaseDate=1314316800&h=7e5b682945a0de79a4da48608dced948"
                  :alt="$t('landing.download_now')" class="store-badge-apple">
            </a>
            <a href="#" class="badge-link">
              <img src="https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png"
                  :alt="$t('landing.download_now')" class="store-badge-google">
            </a>
        </div>
      </div>
    </section>

    <!-- Core Features — compact list, not cards -->
    <section id="features" class="section bg-surface">
      <div class="container">
        <h2 class="section-title">{{ $t('landing.features_title') }}</h2>

        <div class="features-list">
          <div class="feature-item">
            <svg class="feature-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
            <div>
              <h3 class="feature-name">{{ $t('landing.feat_tracking_title') }}</h3>
              <p class="feature-desc text-secondary">{{ $t('landing.feat_tracking_desc') }}</p>
            </div>
          </div>

          <div class="feature-item">
            <svg class="feature-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
            <div>
              <h3 class="feature-name">{{ $t('landing.feat_private_title') }}</h3>
              <p class="feature-desc text-secondary">{{ $t('landing.feat_private_desc') }}</p>
            </div>
          </div>

          <div class="feature-item">
            <svg class="feature-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" x2="14" y1="2" y2="2"/><line x1="12" x2="15" y1="14" y2="11"/><circle cx="12" cy="14" r="8"/></svg>
            <div>
              <h3 class="feature-name">{{ $t('landing.feat_stopwatch_title') }}</h3>
              <p class="feature-desc text-secondary">{{ $t('landing.feat_stopwatch_desc') }}</p>
            </div>
          </div>

          <div class="feature-item">
            <svg class="feature-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
            <div>
              <h3 class="feature-name">{{ $t('landing.feat_issf_title') }}</h3>
              <p class="feature-desc text-secondary">{{ $t('landing.feat_issf_desc') }}</p>
            </div>
          </div>

          <div class="feature-item">
            <svg class="feature-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
            <div>
              <h3 class="feature-name">{{ $t('landing.feat_inventory_title') }}</h3>
              <p class="feature-desc text-secondary">{{ $t('landing.feat_inventory_desc') }}</p>
            </div>
          </div>

          <div class="feature-item">
            <svg class="feature-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5C7 4 9 8 12 8s5-4 7.5-4a2.5 2.5 0 0 1 0 5H18"/><path d="M18 15H6"/><path d="M10 22h4"/><path d="M14 22a8 8 0 0 0 1.73-15"/><path d="M10 22a8 8 0 0 1-1.73-15"/></svg>
            <div>
              <h3 class="feature-name">{{ $t('landing.feat_competitions_title') }}</h3>
              <p class="feature-desc text-secondary">{{ $t('landing.feat_competitions_desc') }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Premium Features — visually distinct with accent tint -->
    <section id="premium" class="section premium-section">
      <div class="container">
        <div class="premium-header">
          <AppBadge variant="warning">{{ $t('dashboard.upgrade_pro') }}</AppBadge>
          <h2 class="section-title">{{ $t('landing.premium_title') }}</h2>
        </div>

        <div class="premium-grid">
          <div class="premium-item">
            <svg class="premium-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
            <h3>{{ $t('landing.prem_timer_title') }}</h3>
            <p class="text-secondary">{{ $t('landing.prem_timer_desc') }}</p>
          </div>

          <div class="premium-item">
            <svg class="premium-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/></svg>
            <h3>{{ $t('landing.prem_excel_title') }}</h3>
            <p class="text-secondary">{{ $t('landing.prem_excel_desc') }}</p>
          </div>

          <div class="premium-item">
            <svg class="premium-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.178 8c5.096 0 5.096 8 0 8-5.095 0-7.133-8-12.739-8-4.585 0-4.585 8 0 8 5.606 0 7.644-8 12.74-8z"/></svg>
            <h3>{{ $t('landing.prem_unlimited_title') }}</h3>
            <p class="text-secondary">{{ $t('landing.prem_unlimited_desc') }}</p>
          </div>

          <div class="premium-item">
            <svg class="premium-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
            <h3>{{ $t('landing.prem_custom_title') }}</h3>
            <p class="text-secondary">{{ $t('landing.prem_custom_desc') }}</p>
          </div>

          <div class="premium-item">
            <svg class="premium-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
            <h3>{{ $t('landing.prem_sync_title') }}</h3>
            <p class="text-secondary">{{ $t('landing.prem_sync_desc') }}</p>
          </div>

          <div class="premium-item">
            <svg class="premium-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="22" x2="18" y1="12" y2="12"/><line x1="6" x2="2" y1="12" y2="12"/><line x1="12" x2="12" y1="6" y2="2"/><line x1="12" x2="12" y1="22" y2="18"/></svg>
            <h3>{{ $t('landing.prem_ammo_title') }}</h3>
            <p class="text-secondary">{{ $t('landing.prem_ammo_desc') }}</p>
          </div>

          <div class="premium-item premium-item--wide">
            <svg class="premium-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M7 16l4-8 4 5 4-3"/></svg>
            <h3>{{ $t('landing.prem_analytics_title') }}</h3>
            <p class="text-secondary">{{ $t('landing.prem_analytics_desc') }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="section bg-surface">
      <div class="container">
        <h2 class="section-title section-title--center">{{ $t('subscription.title') }}</h2>

        <div class="pricing-grid">
            <!-- Free Plan -->
            <div class="pricing-card">
              <h3 class="pricing-plan-name">{{ $t('dashboard.free_plan') }}</h3>
              <div class="pricing-price">$0 <span class="pricing-period">/ {{ $t('subscription.forever') }}</span></div>
              <p class="text-secondary pricing-desc">{{ $t('subscription.free_plan_desc') }}</p>

              <ul class="pricing-features">
                <li>{{ $t('subscription.free_sessions_limit') }}</li>
                <li>{{ $t('landing.feat_tracking_title') }}</li>
                <li>{{ $t('landing.feat_stopwatch_title') }}</li>
                <li>{{ $t('landing.feat_issf_title') }}</li>
                <li>{{ $t('landing.feat_private_title') }}</li>
              </ul>

              <router-link to="/register" class="btn btn-secondary pricing-cta">{{ $t('subscription.get_started_free') }}</router-link>
            </div>

            <!-- Pro+ Plan -->
            <div class="pricing-card pricing-card--featured">
              <AppBadge variant="primary" class="pricing-badge">{{ $t('landing.most_popular') }}</AppBadge>
              <h3 class="pricing-plan-name text-primary">Pro+</h3>
              <div class="pricing-price">$4.99 <span class="pricing-period">/ {{ $t('subscription.per_month') }}</span></div>
              <p class="text-secondary pricing-desc">{{ $t('subscription.pro_plan_desc') }}</p>

              <ul class="pricing-features">
                <li>{{ $t('landing.prem_unlimited_title') }}</li>
                <li>{{ $t('landing.prem_timer_title') }}</li>
                <li>{{ $t('landing.prem_excel_title') }}</li>
                <li>{{ $t('landing.prem_custom_title') }}</li>
                <li>{{ $t('landing.prem_sync_title') }}</li>
                <li>{{ $t('landing.prem_ammo_title') }}</li>
              </ul>

              <router-link to="/register" class="btn btn-primary pricing-cta">{{ $t('subscription.upgrade_now') }}</router-link>
            </div>

            <!-- Club Pro+ Plan -->
            <div class="pricing-card pricing-card--club">
              <AppBadge variant="neutral" class="pricing-badge">Club</AppBadge>
              <h3 class="pricing-plan-name" style="color: #60A5FA;">{{ $t('landing.club_pro_title') }}</h3>
              <div class="pricing-price">$4.99 <span class="pricing-period">/ {{ $t('subscription.per_month') }} *</span></div>
              <p class="text-secondary pricing-desc">{{ $t('landing.club_pro_desc') }}</p>

              <ul class="pricing-features">
                <li>{{ $t('landing.club_pro_all_features') }}</li>
                <li>{{ $t('landing.club_pro_license_mgmt') }}</li>
                <li>{{ $t('landing.club_pro_admin_dashboard') }}</li>
              </ul>

              <a href="mailto:help@triggertime.es?subject=Club Pro+ Quote" class="btn btn-secondary pricing-cta">{{ $t('landing.request_quote') }}</a>
              <div class="pricing-footnote text-secondary">* {{ $t('landing.club_pro_per_user') }}</div>
            </div>
        </div>
      </div>
    </section>

    <!-- Whitelabel Section -->
    <section id="whitelabel" class="section">
      <div class="container whitelabel-layout">
        <div class="whitelabel-content">
          <AppBadge variant="neutral">B2B</AppBadge>
          <h2 class="section-title whitelabel-title">{{ $t('landing.whitelabel_title') }}</h2>
          <p class="text-secondary whitelabel-desc">
            {{ $t('landing.whitelabel_desc') }}
          </p>
          <ul class="check-list">
            <li>{{ $t('landing.whitelabel_feature_1') }}</li>
            <li>{{ $t('landing.whitelabel_feature_2') }}</li>
            <li>{{ $t('landing.whitelabel_feature_3') }}</li>
            <li>{{ $t('landing.whitelabel_feature_4') }}</li>
          </ul>
          <a href="mailto:help@triggertime.es?subject=Custom Whitelabel Quote" class="btn btn-primary">{{ $t('landing.request_quote') }}</a>
        </div>
        <div class="whitelabel-mockup">
          <div class="mockup-card">
            <div class="mockup-header">
              <span class="mockup-icon">🏛️</span>
              <div>
                <div class="mockup-name">My Shooting Club</div>
                <div class="text-secondary mockup-sub">{{ $t('landing.club_pro_title') }}</div>
              </div>
            </div>
            <div class="mockup-label">{{ $t('profile.b2b_licenses') }}</div>
            <div class="mockup-row">
              <span class="text-secondary">{{ $t('landing.member_a') }}</span>
              <AppBadge variant="success">{{ $t('dashboard.active') }}</AppBadge>
            </div>
            <div class="mockup-row">
              <span class="text-secondary">{{ $t('landing.member_b') }}</span>
              <AppBadge variant="success">{{ $t('dashboard.active') }}</AppBadge>
            </div>
            <div class="mockup-row mockup-row--last">
              <span class="text-secondary">{{ $t('landing.member_c') }}</span>
              <AppBadge variant="neutral">{{ $t('dashboard.inactive') }}</AppBadge>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="section bg-surface cta-section">
      <div class="container cta-content">
        <h2 class="section-title section-title--center">{{ $t('landing.contact_title') }}</h2>
        <p class="text-secondary cta-subtitle">{{ $t('landing.contact_subtitle') }}</p>
        <a href="mailto:help@triggertime.es" class="btn btn-secondary btn-lg">
          {{ $t('landing.email_support') }}
        </a>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppBadge from '@/components/ui/AppBadge.vue'
// Hero backgrounds — swap the import to change the hero animation:
// import HeroBackground from '@/components/hero/HeroGridPulse.vue'        // A: dot grid with radial wave
// import HeroBackground from '@/components/hero/HeroFloatingPolygons.vue'  // B: drifting wireframe shapes
// import HeroBackground from '@/components/hero/HeroDiagonalHatching.vue'  // C: engineering cross-hatch
// import HeroBackground from '@/components/hero/HeroIsometricGrid.vue'     // D: pulsing rectangular grid
import HeroBackground from '@/components/hero/HeroIsometricGrid.vue'

const { locale } = useI18n()

const appStoreUrl = computed(
  () => `https://apps.apple.com/app/triggertime-pro/id6758999145?l=${locale.value}`
)
</script>

<style scoped>
/* ─── Sections ─── */
.section {
  padding: 3.5rem 0;
}

.bg-surface {
  background-color: var(--bg-surface);
}

.section-title {
  font-size: clamp(1.75rem, 3.5vw, 2.25rem);
  letter-spacing: -0.5px;
  margin: 0 0 2rem 0;
  text-align: center;
}

.section-title--center {
  text-align: center;
}

/* ─── Hero ─── */
.hero {
  position: relative;
  padding: 7rem 0 3.5rem;
  background-color: var(--bg-base);
  overflow: hidden;
}

.hero-content {
  position: relative;
  z-index: 1;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.hero-badge {
  margin-bottom: 1.5rem;
}

.hero-title {
  font-size: clamp(2.5rem, 6vw, 4rem);
  font-weight: 800;
  letter-spacing: -1.5px;
  line-height: 1.1;
  margin: 0 0 1.5rem 0;
}

.highlight {
  color: var(--primary);
}

.hero-subtitle {
  font-size: 1.2rem;
  line-height: 1.6;
  max-width: 40rem;
  margin: 0 0 2.5rem 0;
}

.hero-actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 3rem;
}

.download-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
}

.store-badge-apple { height: 3rem; }
.store-badge-google { height: 4.5rem; margin: -0.75rem 0; }

/* ─── Features — compact list ─── */
.features-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem 3rem;
  max-width: 900px;
  margin: 0 auto;
}

.feature-item {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.feature-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
  margin-top: 2px;
  color: var(--primary);
}

.feature-name {
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 0.25rem 0;
}

.feature-desc {
  font-size: 0.9rem;
  line-height: 1.5;
  margin: 0;
}

/* ─── Premium — tinted background, 3-col grid ─── */
.premium-section {
  background: var(--primary-a10);
  border-top: 1px solid var(--primary-a15);
  border-bottom: 1px solid var(--primary-a15);
}

.premium-header {
  margin-bottom: 2rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.premium-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
  max-width: 900px;
  margin: 0 auto;
}

.premium-item {
  padding: 1.5rem;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 8px;
}

.premium-item--wide {
  grid-column: span 1;
}

.premium-icon {
  width: 20px;
  height: 20px;
  color: var(--warning);
  margin-bottom: 0.75rem;
}

.premium-item h3 {
  font-size: 0.95rem;
  font-weight: 600;
  margin: 0 0 0.375rem 0;
}

.premium-item p {
  font-size: 0.85rem;
  line-height: 1.5;
  margin: 0;
}

/* ─── Pricing ─── */
.pricing-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  max-width: 1100px;
  margin: 3rem auto 0;
}

.pricing-card {
  display: flex;
  flex-direction: column;
  padding: 2.5rem 2rem;
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
}

.pricing-card--featured {
  border-color: var(--primary-a40);
}

.pricing-card--club {
  border-color: oklch(0.65 0.15 250 / 0.4);
}

.pricing-badge {
  margin-bottom: 1rem;
  align-self: flex-start;
}

.pricing-plan-name {
  font-size: 1.5rem;
  margin: 0 0 0.5rem 0;
}

.pricing-price {
  font-family: var(--font-heading);
  font-size: 2.25rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.pricing-period {
  font-size: 1rem;
  font-family: var(--font-body);
  font-weight: 400;
  color: var(--text-secondary);
}

.pricing-desc {
  margin: 0 0 1.5rem 0;
}

.pricing-features {
  list-style: none;
  padding: 0;
  margin: 0 0 2rem 0;
  flex: 1;
}

.pricing-features li {
  padding: 0.5rem 0;
  font-size: 0.95rem;
  color: var(--text-secondary);
}

.pricing-features li::before {
  content: "✓ ";
  color: var(--primary);
  font-weight: 600;
}

.pricing-cta {
  width: 100%;
  text-align: center;
}

.pricing-footnote {
  font-size: 0.75rem;
  margin-top: 0.75rem;
  text-align: center;
}

/* ─── Whitelabel ─── */
.whitelabel-layout {
  display: flex;
  align-items: center;
  gap: 4rem;
}

.whitelabel-content {
  flex: 1;
}

.whitelabel-title {
  margin-top: 1rem;
}

.whitelabel-desc {
  line-height: 1.75;
  margin: 1.5rem 0;
}

.check-list {
  list-style: none;
  padding: 0;
  margin: 0 0 2rem 0;
}

.check-list li {
  padding: 0.375rem 0;
  color: var(--text-secondary);
  font-size: 1rem;
}

.check-list li::before {
  content: "✓ ";
  color: var(--primary);
  font-weight: 600;
}

.whitelabel-mockup {
  flex: 1;
  display: flex;
  justify-content: flex-end;
}

.mockup-card {
  width: 100%;
  max-width: 400px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 2rem;
}

.mockup-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.mockup-icon { font-size: 2rem; line-height: 1; }
.mockup-name { font-weight: 700; font-size: 1rem; }
.mockup-sub { font-size: 0.875rem; }

.mockup-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
  margin-bottom: 0.75rem;
}

.mockup-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  background: var(--bg-elevated);
  border: 1px solid var(--border-subtle);
  border-radius: 8px;
  margin-bottom: 0.5rem;
}

.mockup-row--last {
  margin-bottom: 0;
}

/* ─── CTA ─── */
.cta-section {
  border-top: 1px solid var(--border-subtle);
}

.cta-content {
  text-align: center;
}

.cta-subtitle {
  margin: 1rem 0 2rem;
}

/* ─── Utilities ─── */
.text-secondary { color: var(--text-secondary); }
.text-primary { color: var(--primary); }

/* ─── Responsive ─── */
@media (max-width: 992px) {
  .hero { text-align: center; }
  .hero-subtitle { margin-left: auto; margin-right: auto; }
  .hero-actions { justify-content: center; }
  .download-badges { justify-content: center; }

  .features-list { grid-template-columns: 1fr; max-width: 500px; margin-left: auto; margin-right: auto; }
  .premium-grid { grid-template-columns: repeat(2, 1fr); }
  .pricing-grid { grid-template-columns: 1fr; max-width: 420px; margin-left: auto; margin-right: auto; }

  .whitelabel-layout {
    flex-direction: column;
    text-align: center;
    gap: 2rem;
  }

  .whitelabel-mockup {
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .section { padding: 3.5rem 0; }
  .hero { padding: 6rem 0 3.5rem; }

  .hero-actions {
    flex-direction: column;
    width: 100%;
    padding: 0 1rem;
  }

  .hero-actions .btn { width: 100%; text-align: center; }

  .premium-grid { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
  .hero { padding: 5rem 0 3rem; }
}
</style>
