<template>
  <div class="landing-page">
    <!-- Hero Section -->
    <section class="hero text-center pt-24 pb-16">
      <div class="container">
        <AppBadge variant="primary" class="mb-6 mx-auto">{{ $t('landing.hero_badge') }}</AppBadge>
        <h1 class="hero-title mb-6">
          {{ $t('landing.hero_title_1') }}<br />
          <span class="highlight">{{ $t('landing.hero_title_2') }}</span>
        </h1>
        <p class="hero-subtitle mb-8 text-secondary max-w-2xl mx-auto px-4">
          {{ $t('landing.hero_subtitle') }}
        </p>
        <div class="hero-buttons flex justify-center gap-4 mb-12">
          <router-link to="/register" class="btn btn-primary btn-lg rounded-full w-full-mobile">
            {{ $t('common.register') }}
          </router-link>
          <a href="#pricing" class="btn btn-secondary btn-lg rounded-full w-full-mobile">{{ $t('nav.pricing') }}</a>
        </div>

        <div class="download-badges flex flex-wrap justify-center gap-4 mt-8">
            <a :href="appStoreUrl" class="badge-link block">
              <img src="https://tools.applemediaservices.com/api/badges/download-on-the-app-store/black/en-us?size=250x83&amp;releaseDate=1314316800&h=7e5b682945a0de79a4da48608dced948"
                  :alt="$t('landing.download_now')" class="h-12">
            </a>
            <a href="#" class="badge-link block">
              <img src="https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png"
                  :alt="$t('landing.download_now')" class="h-google-play">
            </a>
        </div>
      </div>
    </section>

    <!-- Core Features Section -->
    <section id="features" class="features py-24 bg-surface">
      <div class="container text-center">
        <h2 class="section-title mb-16">{{ $t('landing.features_title') }}</h2>
        
        <div class="features-grid">
          <AppCard hoverable>
            <template #header>
              <svg class="icon-feature" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
              {{ $t('landing.feat_tracking_title') }}
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_tracking_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable>
            <template #header>
              <svg class="icon-feature" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
              {{ $t('landing.feat_private_title') }}
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_private_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable>
            <template #header>
              <svg class="icon-feature" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" x2="14" y1="2" y2="2"/><line x1="12" x2="15" y1="14" y2="11"/><circle cx="12" cy="14" r="8"/></svg>
              {{ $t('landing.feat_stopwatch_title') }}
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_stopwatch_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable>
            <template #header>
              <svg class="icon-feature" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
              {{ $t('landing.feat_issf_title') }}
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_issf_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable>
            <template #header>
              <svg class="icon-feature" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
              {{ $t('landing.feat_inventory_title') }}
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_inventory_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable>
            <template #header>
              <svg class="icon-feature" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5C7 4 9 8 12 8s5-4 7.5-4a2.5 2.5 0 0 1 0 5H18"/><path d="M18 15H6"/><path d="M10 22h4"/><path d="M14 22a8 8 0 0 0 1.73-15"/><path d="M10 22a8 8 0 0 1-1.73-15"/></svg>
              {{ $t('landing.feat_competitions_title') }}
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_competitions_desc') }}
            </p>
          </AppCard>
        </div>
      </div>
    </section>

    <!-- Premium Features Section -->
    <section id="premium" class="premium py-24">
      <div class="container text-center">
        <AppBadge variant="warning" class="mb-4">{{ $t('dashboard.upgrade_pro') }}</AppBadge>
        <h2 class="section-title mb-16">{{ $t('landing.premium_title') }}</h2>
        
        <div class="features-grid">
          <AppCard hoverable class="border-warning-subtle">
            <template #header>
              <svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
              {{ $t('landing.prem_timer_title') }}
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_timer_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable class="border-warning-subtle">
            <template #header>
              <svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/></svg>
              {{ $t('landing.prem_excel_title') }}
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_excel_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable class="border-warning-subtle">
            <template #header>
              <svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12c-2-2.67-4-4-6.5-4a3.5 3.5 0 1 0 0 7h.5"/><path d="M12 12c2-2.67 4-4 6.5-4a3.5 3.5 0 1 1 0 7H18"/></svg>
              {{ $t('landing.prem_unlimited_title') }}
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_unlimited_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable class="border-warning-subtle">
            <template #header>
              <svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
              {{ $t('landing.prem_custom_title') }}
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_custom_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable class="border-warning-subtle">
            <template #header>
              <svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
              {{ $t('landing.prem_sync_title') }}
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_sync_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable class="border-warning-subtle">
            <template #header>
              <svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="22" x2="18" y1="12" y2="12"/><line x1="6" x2="2" y1="12" y2="12"/><line x1="12" x2="12" y1="6" y2="2"/><line x1="12" x2="12" y1="22" y2="18"/></svg>
              {{ $t('landing.prem_ammo_title') }}
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_ammo_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable class="border-warning-subtle coming-soon-card">
            <template #header>
              <svg class="icon-feature text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M7 16l4-8 4 5 4-3"/></svg>
              {{ $t('landing.prem_analytics_title') }} <span class="coming-soon-badge">{{ $t('subscription.coming_soon') }}</span>
            </template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_analytics_desc') }}
            </p>
          </AppCard>
        </div>
      </div>
    </section>

    <!-- Pricing / Plans Comparison -->
    <section id="pricing" class="pricing py-24 bg-surface border-t border-subtle">
      <div class="container">
        <h2 class="section-title text-center mb-16">{{ $t('subscription.title') }}</h2>
        
        <div class="pricing-grid">
            <!-- Free Plan -->
            <AppCard class="pricing-card">
              <h3 class="text-2xl mb-2">{{ $t('dashboard.free_plan') }}</h3>
              <div class="price font-heading text-4xl font-bold mb-6">$0 <span class="text-base font-body font-normal text-secondary">/ {{ $t('subscription.forever') }}</span></div>
              <p class="text-secondary mb-8">{{ $t('subscription.free_plan_desc') }}</p>
              
              <ul class="pricing-features mb-8 text-left">
                <li>✓ {{ $t('subscription.free_sessions_limit') }}</li>
                <li>✓ {{ $t('landing.feat_tracking_title') }}</li>
                <li>✓ {{ $t('landing.feat_stopwatch_title') }}</li>
                <li>✓ {{ $t('landing.feat_issf_title') }}</li>
                <li>✓ {{ $t('landing.feat_private_title') }}</li>
              </ul>
              
              <router-link to="/register" class="btn btn-secondary w-full text-center mt-auto">{{ $t('subscription.get_started_free') }}</router-link>
            </AppCard>

            <!-- Pro+ Plan -->
            <AppCard class="pricing-card pro-card relative">
              <div class="best-value absolute top-0 right-8 transform -translate-y-1/2">
                <AppBadge variant="primary">{{ $t('landing.most_popular') }}</AppBadge>
              </div>
              <h3 class="text-2xl mb-2 text-primary">Pro+</h3>
              <div class="price font-heading text-4xl font-bold mb-6">$4.99 <span class="text-base font-body font-normal text-secondary">/ {{ $t('subscription.per_month') }}</span></div>
              <p class="text-secondary mb-8">{{ $t('subscription.pro_plan_desc') }}</p>
              
              <ul class="pricing-features mb-8 text-left">
                <li>✓ {{ $t('landing.prem_unlimited_title') }}</li>
                <li>✓ {{ $t('landing.prem_timer_title') }}</li>
                <li>✓ {{ $t('landing.prem_excel_title') }}</li>
                <li>✓ {{ $t('landing.prem_custom_title') }}</li>
              </ul>
              
              <router-link to="/register" class="btn btn-primary w-full text-center mt-auto">{{ $t('subscription.upgrade_now') }}</router-link>
            </AppCard>

            <!-- Club Pro+ Plan -->
            <AppCard class="pricing-card club-card relative">
              <div class="best-value absolute top-0 right-8 transform -translate-y-1/2">
                <AppBadge variant="neutral">Club</AppBadge>
              </div>
              <h3 class="text-2xl mb-2 text-info">{{ $t('landing.club_pro_title') }}</h3>
              <div class="price font-heading text-4xl font-bold mb-6">$4.99 <span class="text-base font-body font-normal text-secondary">/ {{ $t('subscription.per_month') }} *</span></div>
              <p class="text-secondary mb-8">{{ $t('landing.club_pro_desc') }}</p>

              <ul class="pricing-features mb-8 text-left">
                <li>✓ {{ $t('landing.club_pro_all_features') }}</li>
                <li>✓ {{ $t('landing.club_pro_license_mgmt') }}</li>
                <li>✓ {{ $t('landing.club_pro_admin_dashboard') }}</li>
              </ul>

              <a href="mailto:help@triggertime.es?subject=Club Pro+ Quote" class="btn btn-secondary w-full text-center mt-auto">{{ $t('landing.request_quote') }}</a>
              <div class="club-footnote text-xs text-secondary mt-4 text-center">* {{ $t('landing.club_pro_per_user') }}</div>
            </AppCard>
        </div>
      </div>
    </section>

    <!-- Whitelabel Section -->
    <section id="whitelabel" class="whitelabel py-24">
      <div class="container whitelabel-container">
        <div class="whitelabel-content flex-1">
          <AppBadge variant="neutral" class="mb-4">B2B</AppBadge>
          <h2 class="section-title mb-6 text-left">{{ $t('landing.whitelabel_title') }}</h2>
          <p class="text-secondary mb-6 leading-relaxed">
            {{ $t('landing.whitelabel_desc') }}
          </p>
          <ul class="whitelabel-features mb-8">
            <li>✓ {{ $t('landing.whitelabel_feature_1') }}</li>
            <li>✓ {{ $t('landing.whitelabel_feature_2') }}</li>
            <li>✓ {{ $t('landing.whitelabel_feature_3') }}</li>
            <li>✓ {{ $t('landing.whitelabel_feature_4') }}</li>
          </ul>
          <a href="mailto:help@triggertime.es?subject=Custom Whitelabel Quote" class="btn btn-primary">{{ $t('landing.request_quote') }}</a>
        </div>
        <div class="whitelabel-image flex-1 text-right">
          <div class="glass-card mockup-card inline-block text-left p-8">
            <div class="mockup-club-header mb-5">
              <span class="mockup-club-icon">🏛️</span>
              <div>
                <div class="mockup-club-name">My Shooting Club</div>
                <div class="text-secondary mockup-club-sub">{{ $t('landing.club_pro_title') }}</div>
              </div>
            </div>
            <div class="mockup-section-label mb-3">{{ $t('profile.b2b_licenses') }}</div>
            <div class="bg-elevated p-4 rounded-lg border border-subtle mb-3 flex justify-between">
              <span class="text-secondary">{{ $t('landing.member_a') }}</span>
              <AppBadge variant="success">{{ $t('dashboard.active') }}</AppBadge>
            </div>
            <div class="bg-elevated p-4 rounded-lg border border-subtle mb-3 flex justify-between">
              <span class="text-secondary">{{ $t('landing.member_b') }}</span>
              <AppBadge variant="success">{{ $t('dashboard.active') }}</AppBadge>
            </div>
            <div class="bg-elevated p-4 rounded-lg border border-subtle flex justify-between">
              <span class="text-secondary">{{ $t('landing.member_c') }}</span>
              <AppBadge variant="neutral">{{ $t('dashboard.inactive') }}</AppBadge>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-24 border-t border-subtle bg-surface text-center">
      <div class="container">
        <h2 class="section-title mb-6">{{ $t('landing.contact_title') }}</h2>
        <p class="text-secondary mb-8">{{ $t('landing.contact_subtitle') }}</p>
        <a href="mailto:help@triggertime.es" class="btn btn-secondary btn-lg rounded-full">
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
import AppCard from '@/components/ui/AppCard.vue'

const { locale } = useI18n()

// Apple auto-routes to the user's regional store when no country code is in the path.
// The `l` param controls the preview page language only.
const appStoreUrl = computed(
  () => `https://apps.apple.com/app/triggertime-pro/id6758999145?l=${locale.value}`
)
</script>

<style scoped>
.py-24 { padding-top: 8rem; padding-bottom: 8rem; }
.pt-24 { padding-top: 10rem; }
.pb-16 { padding-bottom: 6rem; }

.hero {
  position: relative;
  background-color: var(--bg-base);
  background-image: linear-gradient(to bottom, rgba(10, 10, 15, 0.75), rgba(10, 10, 15, 0.95)), url('https://images.unsplash.com/photo-1584984647365-18151480f745?q=80&w=2600&auto=format&fit=crop');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
}

.hero-title {
  font-size: 4.5rem;
  font-weight: 800;
  letter-spacing: -1.5px;
  line-height: 1.1;
}

.hero-subtitle {
  font-size: 1.25rem;
  line-height: 1.6;
}

.highlight {
  color: var(--primary);
  text-shadow: 0 0 40px rgba(193, 255, 114, 0.2);
}

.max-w-2xl { max-width: 48rem; }
.mx-auto { margin-left: auto; margin-right: auto; }
.w-full { width: 100%; }
.m-0 { margin: 0; }
.h-12 { height: 3.5rem; }
.h-google-play { height: 5.25rem; margin: -0.875rem 0; }
.text-2xl { font-size: 1.5rem; }
.text-4xl { font-size: 2.5rem; }
.text-base { font-size: 1rem; }
.font-body { font-family: var(--font-body); }
.font-heading { font-family: var(--font-heading); }
.font-normal { font-weight: 400; }
.font-bold { font-weight: 700; }

.rounded-full {
  border-radius: 9999px !important;
  padding-left: 32px !important;
  padding-right: 32px !important;
}

.icon-feature {
  width: 24px;
  height: 24px;
  display: inline-block;
  vertical-align: middle;
  margin-right: 8px;
  flex-shrink: 0;
}

.coming-soon-card {
  opacity: 0.6;
}

.coming-soon-badge {
  font-size: 0.65rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--warning);
  background: rgba(251, 191, 36, 0.15);
  padding: 2px 8px;
  border-radius: 4px;
  margin-left: 8px;
  vertical-align: middle;
}

.bg-surface { background-color: var(--bg-surface); }
.bg-elevated { background-color: var(--bg-elevated); }

.border { border: 1px solid var(--border-subtle); }
.border-t { border-top: 1px solid var(--border-subtle); }
.border-warning-subtle { border-color: rgba(251, 191, 36, 0.2); }
.text-warning { color: var(--warning); }

.rounded-lg { border-radius: 12px; }

.section-title {
  font-size: 3rem;
  letter-spacing: -0.5px;
  margin-bottom: 4rem;
}

.text-left { text-align: left; }
.text-right { text-align: right; }

.leading-relaxed { line-height: 1.75; }

.features-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 40px;
  max-width: 1100px;
  margin: 0 auto;
}

.pricing-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 32px;
  max-width: 1300px;
  margin: 0 auto;
}

.pricing-card {
  display: flex;
  flex-direction: column;
  padding: 48px 40px !important;
}

.pro-card {
  border-color: rgba(193, 255, 114, 0.4);
  background: linear-gradient(180deg, rgba(193, 255, 114, 0.05) 0%, rgba(10, 10, 15, 0) 100%), var(--bg-surface);
  box-shadow: 0 10px 40px rgba(193, 255, 114, 0.05);
}

.club-card {
  border-color: rgba(96, 165, 250, 0.4);
  background: linear-gradient(180deg, rgba(96, 165, 250, 0.05) 0%, rgba(10, 10, 15, 0) 100%), var(--bg-surface);
  box-shadow: 0 10px 40px rgba(96, 165, 250, 0.05);
}

.text-info { color: #60A5FA; }

.whitelabel-features {
  list-style: none;
  padding: 0;
}

.whitelabel-features li {
  padding: 6px 0;
  color: var(--text-secondary);
  font-size: 1.05rem;
}

.mockup-club-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.mockup-club-icon { font-size: 2rem; line-height: 1; }

.mockup-club-name { font-weight: 700; font-size: 1rem; }

.mockup-club-sub { font-size: 0.875rem; }

.mockup-section-label {
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
}

.mockup-card .flex.justify-between span {
  white-space: nowrap;
}

.pricing-features {
  list-style: none;
  padding: 0;
}

.pricing-features li {
  margin-bottom: 16px;
  color: var(--text-secondary);
  font-size: 1.05rem;
}

.absolute { position: absolute; }
.top-0 { top: 0; }
.right-8 { right: 2rem; }
.transform { transform: translateY(-50%); }

.mockup-card {
  width: 100%;
  min-width: 360px;
  max-width: 480px;
}

@media (min-width: 993px) {
  .whitelabel-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 4rem;
  }
}

@media (max-width: 1100px) {
  .pricing-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 992px) {
  .hero-title { font-size: 3rem; }
  .features-grid { grid-template-columns: 1fr; gap: 32px; }
  .pricing-grid { grid-template-columns: 1fr; gap: 32px; }
  .whitelabel { flex-direction: column; text-align: center; }
  
  .whitelabel-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 32px;
  }
  
  .mockup-card {
    margin: 0 auto;
  }
}

@media (max-width: 768px) {
  .pt-24 { padding-top: 6rem; }
  .pb-16 { padding-bottom: 4rem; }
  .py-24 { padding-top: 5rem; padding-bottom: 5rem; }
  
  .hero-title { font-size: 2.5rem; line-height: 1.15; }
  .hero-subtitle { font-size: 1.1rem; }
  
  .hero-buttons {
    flex-direction: column;
    padding: 0 16px;
    gap: 16px;
  }

  .w-full-mobile {
    width: 100%;
  }

  /* Stack download badges or keep them row but wrapped if needed, it seems they fit in a row usually. */
}

@media (max-width: 480px) {
  .hero-title { font-size: 2.25rem; }
  .section-title { font-size: 2rem; margin-bottom: 2rem; }
  
  .pricing-card {
    padding: 32px 24px !important;
  }
}
</style>
