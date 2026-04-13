import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'

function trackEvent(eventName, params = {}) {
    window.dataLayer = window.dataLayer || []
    window.dataLayer.push({ event: eventName, ...params })
}

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('tt_token') || null)
    const user = ref(null)
    const subscription = ref(null)

    const isAuthenticated = computed(() => !!token.value)
    const isAdmin = computed(() => user.value?.role === 'admin')
    const isClubAdmin = computed(() => user.value?.role === 'club_admin')
    const isProPlus = computed(() => {
        const sub = subscription.value
        if (sub?.plan !== 'pro' || sub?.status !== 'active') return false
        if (sub.cancel_at_period_end && sub.current_period_end) {
            return new Date(sub.current_period_end) > new Date()
        }

        return true
    })
    const isFree = computed(() => subscription.value?.plan === 'free')
    const isVerified = computed(() => !!user.value?.email_verified_at)

    let _fetchPromise = null

    async function ensureUser() {
        if (user.value || !token.value) return
        if (!_fetchPromise) {
            _fetchPromise = fetchUser()
        }
        await _fetchPromise
    }

    async function login(email, password) {
        try {
            const response = await authApi.login(email, password)
            if (response.success) {
                setAuthData(response.token, response.user, response.user.subscriptions?.[0])
                trackEvent('login', { method: 'email' })
                return { success: true }
            }
        } catch (error) {
            return { success: false, error: error.response?.data?.error?.message || error.response?.data?.message || 'Login failed' }
        }
    }

    async function register(email, password, firstName, lastName, language, marketingOptin = false, upgradeToken = null) {
        try {
            const response = await authApi.register(email, password, firstName, lastName, language, marketingOptin, upgradeToken)
            if (response.success) {
                setAuthData(response.token, response.user, response.user.subscriptions?.[0])
                trackEvent('sign_up', { method: 'email' })
                return { success: true }
            }
        } catch (error) {
            return { success: false, error: error.response?.data?.error?.message || error.response?.data?.message || 'Registration failed' }
        }
    }

    async function socialLogin(provider, idToken, firstName, lastName, marketingOptin = false) {
        try {
            const response = await authApi.socialLogin(provider, idToken, firstName, lastName, marketingOptin)
            if (response.success) {
                setAuthData(response.token, response.user, response.user.subscriptions?.[0])
                trackEvent('login', { method: provider })
                return { success: true }
            }
        } catch (error) {
            return { success: false, error: error.response?.data?.error?.message || 'Social login failed' }
        }
    }

    async function fetchUser() {
        if (!token.value) return
        try {
            const response = await authApi.getMe()
            if (response.success) {
                user.value = response.user
                if (response.instances) {
                    user.value.instances = response.instances
                }
                subscription.value = response.user.subscriptions?.[0]
            }
        } catch (error) {
            if (error.response?.status === 401) {
                logout()
            }
        }
    }

    function setAuthData(newToken, userData, subData) {
        token.value = newToken
        user.value = userData
        subscription.value = subData
        if (newToken) {
            localStorage.setItem('tt_token', newToken)
        }
    }

    function logout() {
        token.value = null
        user.value = null
        subscription.value = null
        localStorage.removeItem('tt_token')
        localStorage.removeItem('tt_token_stash')
    }

    return {
        token,
        user,
        subscription,
        isAuthenticated,
        isAdmin,
        isClubAdmin,
        isProPlus,
        isFree,
        isVerified,
        login,
        register,
        socialLogin,
        fetchUser,
        ensureUser,
        setAuthData,
        logout
    }
})
