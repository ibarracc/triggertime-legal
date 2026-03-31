import { watch } from 'vue'
import { useCookieConsent } from './useCookieConsent'

function gtag() {
  window.dataLayer = window.dataLayer || []
  window.dataLayer.push(arguments)
}

function updateConsentState(analyticsAllowed, marketingAllowed) {
  gtag('consent', 'update', {
    'analytics_storage': analyticsAllowed ? 'granted' : 'denied',
    'ad_storage': marketingAllowed ? 'granted' : 'denied',
    'ad_user_data': marketingAllowed ? 'granted' : 'denied',
    'ad_personalization': marketingAllowed ? 'granted' : 'denied',
  })
}

function trackEvent(eventName, params = {}) {
  window.dataLayer = window.dataLayer || []
  window.dataLayer.push({ event: eventName, ...params })
}

let initialized = false

export function useAnalytics() {
  if (!initialized) {
    initialized = true
    const { state } = useCookieConsent()

    // Sync current consent state on load (handles returning visitors)
    if (state.timestamp) {
      updateConsentState(state.analytics, state.marketing)
    }

    // Watch for consent changes (user interacts with cookie banner)
    watch(
      () => [state.analytics, state.marketing],
      ([analytics, marketing]) => {
        updateConsentState(analytics, marketing)
      }
    )
  }

  return { trackEvent }
}
