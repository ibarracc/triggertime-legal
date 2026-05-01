<template>
  <Transition name="slide-up">
    <div v-if="consent.state.showBanner" class="cookie-banner">
      <div class="cookie-content">
        <div class="cookie-text">
          <h3 class="cookie-title">{{ $t('cookies.banner_title') }}</h3>
          <p class="cookie-desc">{{ $t('cookies.banner_text') }}</p>
        </div>

        <div v-if="showPreferences" class="cookie-categories">
          <div class="cookie-category">
            <div class="category-header">
              <div>
                <span class="category-name">{{ $t('cookies.essential_title') }}</span>
                <p class="category-desc">{{ $t('cookies.essential_desc') }}</p>
              </div>
              <span class="always-on-badge">{{ $t('cookies.always_on') }}</span>
            </div>
          </div>

          <div class="cookie-category">
            <div class="category-header">
              <div>
                <span class="category-name">{{ $t('cookies.analytics_title') }}</span>
                <p class="category-desc">{{ $t('cookies.analytics_desc') }}</p>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="prefs.analytics" />
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>

          <div class="cookie-category">
            <div class="category-header">
              <div>
                <span class="category-name">{{ $t('cookies.marketing_title') }}</span>
                <p class="category-desc">{{ $t('cookies.marketing_desc') }}</p>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="prefs.marketing" />
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <div class="cookie-actions">
          <template v-if="!showPreferences">
            <button class="cookie-btn cookie-btn-primary" @click="consent.acceptAll()">
              {{ $t('cookies.accept_all') }}
            </button>
            <button class="cookie-btn cookie-btn-secondary" @click="consent.rejectAll()">
              {{ $t('cookies.reject_all') }}
            </button>
            <button class="cookie-btn cookie-btn-link" @click="showPreferences = true">
              {{ $t('cookies.manage') }}
            </button>
          </template>
          <template v-else>
            <button class="cookie-btn cookie-btn-primary" @click="handleSavePreferences">
              {{ $t('cookies.save_preferences') }}
            </button>
            <button class="cookie-btn cookie-btn-secondary" @click="consent.acceptAll()">
              {{ $t('cookies.accept_all') }}
            </button>
          </template>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useCookieConsent } from '@/composables/useCookieConsent'

const consent = useCookieConsent()
const showPreferences = ref(false)
const prefs = reactive({
  analytics: consent.state.analytics,
  marketing: consent.state.marketing
})

const handleSavePreferences = () => {
  consent.savePreferences({
    analytics: prefs.analytics,
    marketing: prefs.marketing
  })
  showPreferences.value = false
}
</script>

<style scoped>
.cookie-banner {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  background: var(--bg-surface);
  border-top: 1px solid var(--border-subtle);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
  padding: 24px;
}

.cookie-content {
  max-width: 960px;
  margin: 0 auto;
}

.cookie-title {
  font-family: var(--font-heading);
  font-size: 1.125rem;
  font-weight: 700;
  margin-bottom: 8px;
  color: var(--text-primary);
}

.cookie-desc {
  font-size: 0.875rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin-bottom: 16px;
}

.cookie-categories {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.cookie-category {
  background: var(--bg-elevated);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 16px;
}

.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.category-name {
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--text-primary);
}

.category-desc {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-top: 4px;
  margin-bottom: 0;
  line-height: 1.4;
}

.always-on-badge {
  font-size: 0.75rem;
  color: var(--primary);
  background: var(--primary-a10);
  padding: 4px 10px;
  border-radius: 20px;
  white-space: nowrap;
  font-weight: 500;
}

.toggle {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
  flex-shrink: 0;
}

.toggle input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: var(--bg-base);
  border: 1px solid var(--border-subtle);
  border-radius: 24px;
  transition: all 0.2s ease;
}

.toggle-slider::before {
  content: '';
  position: absolute;
  height: 18px;
  width: 18px;
  left: 2px;
  bottom: 2px;
  background: var(--text-secondary);
  border-radius: 50%;
  transition: all 0.2s ease;
}

.toggle input:checked + .toggle-slider {
  background: var(--primary);
  border-color: var(--primary);
}

.toggle input:checked + .toggle-slider::before {
  transform: translateX(20px);
  background: var(--bg-base);
}

.cookie-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.cookie-btn {
  padding: 10px 20px;
  border-radius: 12px;
  font-family: var(--font-body);
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.cookie-btn-primary {
  background: var(--primary);
  color: var(--bg-base);
}

.cookie-btn-primary:hover {
  background: var(--primary-hover);
}

.cookie-btn-secondary {
  background: var(--bg-elevated);
  color: var(--text-primary);
  border-color: var(--border-subtle);
}

.cookie-btn-secondary:hover {
  border-color: rgba(255, 255, 255, 0.3);
}

.cookie-btn-link {
  background: none;
  color: var(--text-secondary);
  padding: 10px 12px;
}

.cookie-btn-link:hover {
  color: var(--primary);
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

@media (max-width: 600px) {
  .cookie-banner {
    padding: 16px;
  }

  .cookie-actions {
    flex-direction: column;
  }

  .cookie-btn {
    width: 100%;
    text-align: center;
  }

  .category-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}
</style>
