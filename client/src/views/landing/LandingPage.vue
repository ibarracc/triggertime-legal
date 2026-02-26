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
            <a href="#" class="badge-link block">
              <img src="https://tools.applemediaservices.com/api/badges/download-on-the-app-store/black/en-us?size=250x83&amp;releaseDate=1314316800&h=7e5b682945a0de79a4da48608dced948"
                  :alt="$t('landing.download_now')" class="h-12">
            </a>
            <a href="#" class="badge-link block">
              <img src="https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png"
                  :alt="$t('landing.download_now')" class="h-12">
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
            <template #header><span class="text-2xl mr-2">🎯</span> {{ $t('landing.feat_tracking_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_tracking_desc') }}
            </p>
          </AppCard>
          
          <AppCard hoverable>
            <template #header><span class="text-2xl mr-2">🔒</span> {{ $t('landing.feat_private_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_private_desc') }}
            </p>
          </AppCard>
          
          <AppCard hoverable>
            <template #header><span class="text-2xl mr-2">⏱️</span> {{ $t('landing.feat_stopwatch_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_stopwatch_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable>
            <template #header><span class="text-2xl mr-2">🏆</span> {{ $t('landing.feat_issf_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.feat_issf_desc') }}
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
            <template #header><span class="text-2xl mr-2 text-warning">⚡</span> {{ $t('landing.prem_timer_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_timer_desc') }}
            </p>
          </AppCard>
          
          <AppCard hoverable class="border-warning-subtle">
            <template #header><span class="text-2xl mr-2 text-warning">📊</span> {{ $t('landing.prem_excel_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_excel_desc') }}
            </p>
          </AppCard>
          
          <AppCard hoverable class="border-warning-subtle">
            <template #header><span class="text-2xl mr-2 text-warning">💎</span> {{ $t('landing.prem_unlimited_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_unlimited_desc') }}
            </p>
          </AppCard>

          <AppCard hoverable class="border-warning-subtle">
            <template #header><span class="text-2xl mr-2 text-warning">🛠️</span> {{ $t('landing.prem_custom_title') }}</template>
            <p class="text-secondary text-left m-0">
              {{ $t('landing.prem_custom_desc') }}
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
              <div class="price font-heading text-4xl font-bold mb-6">$0<span class="text-base font-body font-normal text-secondary">/ {{ $t('subscription.forever') }}</span></div>
              <p class="text-secondary mb-8">{{ $t('subscription.free_plan_desc') }}</p>
              
              <ul class="pricing-features mb-8 text-left">
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
                <AppBadge variant="primary">Pro+</AppBadge>
              </div>
              <h3 class="text-2xl mb-2 text-primary">Pro+</h3>
              <div class="price font-heading text-4xl font-bold mb-6">$4.99<span class="text-base font-body font-normal text-secondary">/ {{ $t('subscription.per_month') }}</span></div>
              <p class="text-secondary mb-8">{{ $t('subscription.pro_plan_desc') }}</p>
              
              <ul class="pricing-features mb-8 text-left">

                <li>✓ {{ $t('landing.prem_timer_title') }}</li>
                <li>✓ {{ $t('landing.prem_excel_title') }}</li>
                <li>✓ {{ $t('landing.prem_unlimited_title') }}</li>
                <li>✓ {{ $t('subscription.advanced_analytics') }}</li>
              </ul>
              
              <router-link to="/register" class="btn btn-primary w-full text-center mt-auto">{{ $t('subscription.upgrade_now') }}</router-link>
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
          <p class="text-secondary mb-8 leading-relaxed">
            {{ $t('landing.whitelabel_desc') }}
          </p>
          <a href="mailto:help@triggertime.es?subject=Custom Whitelabel Quote" class="btn btn-secondary">{{ $t('landing.request_quote') }}</a>
        </div>
        <div class="whitelabel-image flex-1 text-right">
          <div class="glass-card mockup-card inline-block text-left p-8">
            <h3 class="mb-4">{{ $t('profile.b2b_licenses') }}</h3>
            <div class="bg-elevated p-4 rounded-lg border border-subtle mb-4 flex justify-between">
              <span class="text-secondary">{{ $t('landing.member_a') }}</span>
              <AppBadge variant="success">{{ $t('dashboard.active') }}</AppBadge>
            </div>
            <div class="bg-elevated p-4 rounded-lg border border-subtle flex justify-between">
              <span class="text-secondary">{{ $t('landing.member_b') }}</span>
              <AppBadge variant="neutral">{{ $t('common.no_data') }}</AppBadge>
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
import AppBadge from '@/components/ui/AppBadge.vue'
import AppCard from '@/components/ui/AppCard.vue'
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
.h-12 { height: 3rem; }
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
  grid-template-columns: repeat(2, 1fr);
  gap: 40px;
  max-width: 900px;
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
  max-width: 450px;
}

@media (min-width: 993px) {
  .whitelabel-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 4rem;
  }
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
