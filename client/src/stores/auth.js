import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('tt_token') || null)
    const user = ref(null)
    const subscription = ref(null)

    const isAuthenticated = computed(() => !!token.value)
    const isAdmin = computed(() => user.value?.role === 'admin')
    const isClubAdmin = computed(() => user.value?.role === 'club_admin')
    const isProPlus = computed(() =>
        subscription.value?.plan === 'pro' && subscription.value?.status === 'active'
    )
    const isFree = computed(() => subscription.value?.plan === 'free')

    async function login(email, password) {
        try {
            const response = await authApi.login(email, password)
            if (response.success) {
                setAuthData(response.token, response.user, response.user.subscriptions?.[0])
                return { success: true }
            }
        } catch (error) {
            return { success: false, error: error.response?.data?.error?.message || error.response?.data?.message || 'Login failed' }
        }
    }

    async function register(email, password, firstName, lastName, language) {
        try {
            const response = await authApi.register(email, password, firstName, lastName, language)
            if (response.success) {
                setAuthData(response.token, response.user, response.user.subscriptions?.[0])
                return { success: true }
            }
        } catch (error) {
            return { success: false, error: error.response?.data?.error?.message || error.response?.data?.message || 'Registration failed' }
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
        login,
        register,
        fetchUser,
        setAuthData,
        logout
    }
})
