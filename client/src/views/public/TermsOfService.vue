<template>
  <div class="terms-container container py-24">
    <AppCard>
      <div class="prose max-w-3xl mx-auto">
        <div class="mb-12 border-b border-subtle pb-8">
          <h1 class="font-heading mb-4 text-4xl">{{ $t('terms.title') }}</h1>
          <p class="text-secondary">{{ $t('terms.last_updated') }}</p>
        </div>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('terms.acceptance_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('terms.acceptance_text') }}
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('terms.service_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('terms.service_text') }}
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('terms.account_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('terms.account_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li>{{ $t('terms.account_item_1') }}</li>
            <li>{{ $t('terms.account_item_2') }}</li>
            <li>{{ $t('terms.account_item_3') }}</li>
            <li>{{ $t('terms.account_item_4') }}</li>
          </ul>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('terms.subscription_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('terms.subscription_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li>{{ $t('terms.subscription_item_1') }}</li>
            <li>{{ $t('terms.subscription_item_2') }}</li>
            <li>{{ $t('terms.subscription_item_3') }}</li>
            <li>{{ $t('terms.subscription_item_4') }}</li>
          </ul>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('terms.use_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('terms.use_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li>{{ $t('terms.use_item_1') }}</li>
            <li>{{ $t('terms.use_item_2') }}</li>
            <li>{{ $t('terms.use_item_3') }}</li>
            <li>{{ $t('terms.use_item_4') }}</li>
            <li>{{ $t('terms.use_item_5') }}</li>
          </ul>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('terms.ip_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('terms.ip_text') }}
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('terms.data_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('terms.data_text') }}
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('terms.liability_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('terms.liability_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li>{{ $t('terms.liability_item_1') }}</li>
            <li>{{ $t('terms.liability_item_2') }}</li>
            <li>{{ $t('terms.liability_item_3') }}</li>
          </ul>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('terms.termination_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('terms.termination_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li>{{ $t('terms.termination_item_1') }}</li>
            <li>{{ $t('terms.termination_item_2') }}</li>
            <li>{{ $t('terms.termination_item_3') }}</li>
          </ul>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('terms.law_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('terms.law_text') }}
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('terms.changes_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('terms.changes_text') }}
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('terms.contact_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            <i18n-t keypath="terms.contact_text">
              <template #email>
                <a href="mailto:help@triggertime.es" class="text-primary hover:underline">help@triggertime.es</a>
              </template>
            </i18n-t>
          </p>
          <p class="text-secondary mt-8 text-sm">
            © {{ new Date().getFullYear() }} TriggerTime. All rights reserved.
          </p>
        </section>
      </div>
    </AppCard>
  </div>
</template>

<script setup>
import AppCard from '@/components/ui/AppCard.vue'
</script>

<style scoped>
.py-24 { padding-top: 6rem; padding-bottom: 6rem; }
.max-w-3xl { max-width: 48rem; }
.mx-auto { margin-left: auto; margin-right: auto; }
.pb-8 { padding-bottom: 2rem; }
.mb-12 { margin-bottom: 3rem; }
.mb-8 { margin-bottom: 2rem; }
.mb-4 { margin-bottom: 1rem; }
.border-b { border-bottom: 1px solid var(--border-subtle); }
.text-4xl { font-size: 2.25rem; }
.text-xl { font-size: 1.25rem; }
.text-sm { font-size: 0.875rem; }
.leading-relaxed { line-height: 1.75; }
.font-heading { font-family: var(--font-heading); font-weight: 700; }
.text-primary { color: var(--primary); }
.text-white { color: #fff; }
.list-disc { list-style-type: disc; }
.pl-6 { padding-left: 1.5rem; }
.space-y-2 > * + * { margin-top: 0.5rem; }
.hover\:underline:hover { text-decoration: underline; }
</style>
