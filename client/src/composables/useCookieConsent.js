import { reactive, readonly } from 'vue'

const STORAGE_KEY = 'tt_cookie_consent'

const state = reactive({
  essential: true,
  analytics: false,
  marketing: false,
  timestamp: null,
  showBanner: false
})

function loadConsent() {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored) {
      const parsed = JSON.parse(stored)
      state.essential = true
      state.analytics = parsed.analytics || false
      state.marketing = parsed.marketing || false
      state.timestamp = parsed.timestamp || null
      state.showBanner = false
    } else {
      state.showBanner = true
    }
  } catch {
    state.showBanner = true
  }
}

function saveConsent() {
  const data = {
    essential: true,
    analytics: state.analytics,
    marketing: state.marketing,
    timestamp: new Date().toISOString()
  }
  localStorage.setItem(STORAGE_KEY, JSON.stringify(data))
  state.timestamp = data.timestamp
  state.showBanner = false
}

function acceptAll() {
  state.analytics = true
  state.marketing = true
  saveConsent()
}

function rejectAll() {
  state.analytics = false
  state.marketing = false
  saveConsent()
}

function savePreferences(prefs) {
  state.analytics = prefs.analytics || false
  state.marketing = prefs.marketing || false
  saveConsent()
}

function openBanner() {
  state.showBanner = true
}

function isAllowed(category) {
  if (category === 'essential') return true
  return state[category] || false
}

loadConsent()

export function useCookieConsent() {
  return {
    state: readonly(state),
    acceptAll,
    rejectAll,
    savePreferences,
    openBanner,
    isAllowed
  }
}
