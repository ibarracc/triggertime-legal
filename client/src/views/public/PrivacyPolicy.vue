<template>
  <div class="privacy-container container py-24">
    <AppCard>
      <div class="prose max-w-3xl mx-auto">
        <div class="mb-12 border-b border-subtle pb-8">
          <h1 class="font-heading mb-4 text-4xl">{{ $t('privacy.title') }}</h1>
          <p class="text-secondary">{{ $t('privacy.last_updated') }}</p>
        </div>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.intro_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.intro_text') }}
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.collection_heading') }}</h3>

          <h4 class="font-heading text-lg mb-2 text-white">{{ $t('privacy.collection_account_heading') }}</h4>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.collection_account_text') }}
          </p>

          <h4 class="font-heading text-lg mb-2 text-white">{{ $t('privacy.collection_training_heading') }}</h4>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.collection_training_text') }}
          </p>

          <h4 class="font-heading text-lg mb-2 text-white">{{ $t('privacy.collection_device_heading') }}</h4>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.collection_device_text') }}
          </p>

          <h4 class="font-heading text-lg mb-2 text-white">{{ $t('privacy.collection_payment_heading') }}</h4>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.collection_payment_text') }}
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.usage_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.usage_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li>{{ $t('privacy.usage_item_1') }}</li>
            <li>{{ $t('privacy.usage_item_2') }}</li>
            <li>{{ $t('privacy.usage_item_3') }}</li>
            <li>{{ $t('privacy.usage_item_4') }}</li>
            <li>{{ $t('privacy.usage_item_5') }}</li>
          </ul>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.storage_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.storage_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li>{{ $t('privacy.storage_item_1') }}</li>
            <li>{{ $t('privacy.storage_item_2') }}</li>
            <li>{{ $t('privacy.storage_item_3') }}</li>
            <li>{{ $t('privacy.storage_item_4') }}</li>
          </ul>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.sync_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.sync_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li>{{ $t('privacy.sync_item_1') }}</li>
            <li>{{ $t('privacy.sync_item_2') }}</li>
            <li>{{ $t('privacy.sync_item_3') }}</li>
            <li>{{ $t('privacy.sync_item_4') }}</li>
            <li>{{ $t('privacy.sync_item_5') }}</li>
          </ul>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.third_party_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.third_party_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li><strong class="text-white">{{ $t('privacy.third_party_item_1_label') }}</strong> {{ $t('privacy.third_party_item_1_text') }}</li>
          </ul>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.third_party_no_analytics') }}
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.rights_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.rights_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li>{{ $t('privacy.rights_item_1') }}</li>
            <li>{{ $t('privacy.rights_item_2') }}</li>
            <li>{{ $t('privacy.rights_item_3') }}</li>
            <li>{{ $t('privacy.rights_item_4') }}</li>
            <li>{{ $t('privacy.rights_item_5') }}</li>
          </ul>
          <p class="text-secondary leading-relaxed mb-4">
            <i18n-t keypath="privacy.rights_contact">
              <template #email>
                <a href="mailto:help@triggertime.es" class="text-primary hover:underline">help@triggertime.es</a>
              </template>
            </i18n-t>
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.retention_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.retention_text') }}
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.permissions_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.permissions_text') }}
          </p>
          <ul class="list-disc pl-6 text-secondary mb-4 space-y-2">
            <li><strong class="text-white">{{ $t('privacy.permissions_storage_label') }}</strong> {{ $t('privacy.permissions_storage_text') }}</li>
          </ul>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.children_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.children_text') }}
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.changes_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            {{ $t('privacy.changes_text') }}
          </p>
        </section>

        <section class="mb-8">
          <h3 class="font-heading text-xl mb-4 text-primary">{{ $t('privacy.contact_heading') }}</h3>
          <p class="text-secondary leading-relaxed mb-4">
            <i18n-t keypath="privacy.contact_text">
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
.mb-2 { margin-bottom: 0.5rem; }
.border-b { border-bottom: 1px solid var(--border-subtle); }
.text-4xl { font-size: 2.25rem; }
.text-xl { font-size: 1.25rem; }
.text-lg { font-size: 1.125rem; }
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
