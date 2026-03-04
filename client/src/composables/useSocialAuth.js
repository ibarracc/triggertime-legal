import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function useSocialAuth() {
    const authStore = useAuthStore()
    const isLoading = ref(false)
    const error = ref('')

    let googleInitialized = false

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) {
                resolve()
                return
            }
            const script = document.createElement('script')
            script.src = src
            script.async = true
            script.onload = resolve
            script.onerror = reject
            document.head.appendChild(script)
        })
    }

    async function initGoogle() {
        if (googleInitialized) return
        await loadScript('https://accounts.google.com/gsi/client')
        googleInitialized = true
    }

    async function getGoogleIdToken() {
        await initGoogle()

        return new Promise((resolve, reject) => {
            /* global google */
            google.accounts.id.initialize({
                client_id: import.meta.env.VITE_GOOGLE_CLIENT_ID,
                use_fedcm_for_button: true,
                callback: (response) => {
                    if (response.credential) {
                        resolve(response.credential)
                    } else {
                        reject(new Error('No credential received'))
                    }
                },
            })

            // Use renderButton for FedCM-compatible click-based sign-in
            const wrapper = document.createElement('div')
            wrapper.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0'
            document.body.appendChild(wrapper)

            google.accounts.id.renderButton(wrapper, {
                type: 'standard',
                size: 'large',
            })

            requestAnimationFrame(() => {
                const btn = wrapper.querySelector('[role="button"]')
                if (btn) {
                    btn.click()
                } else {
                    wrapper.remove()
                    reject(new Error('Google Sign-In not available'))
                }
                setTimeout(() => wrapper.remove(), 500)
            })
        })
    }

    async function getAppleIdToken() {
        await loadScript('https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js')

        /* global AppleID */
        AppleID.auth.init({
            clientId: import.meta.env.VITE_APPLE_SERVICE_ID,
            scope: 'name email',
            redirectURI: window.location.origin + '/auth/apple-callback',
            usePopup: true,
        })

        const response = await AppleID.auth.signIn()

        return {
            idToken: response.authorization.id_token,
            firstName: response.user?.name?.firstName || null,
            lastName: response.user?.name?.lastName || null,
        }
    }

    async function loginWithGoogle(marketingOptin = false) {
        isLoading.value = true
        error.value = ''

        try {
            const idToken = await getGoogleIdToken()

            const result = await authStore.socialLogin('google', idToken, null, null, marketingOptin)
            if (!result.success) {
                error.value = result.error
            }
            return result
        } catch (e) {
            error.value = e.message || 'Google sign-in failed'
            return { success: false, error: error.value }
        } finally {
            isLoading.value = false
        }
    }

    async function loginWithApple(marketingOptin = false) {
        isLoading.value = true
        error.value = ''

        try {
            const { idToken, firstName, lastName } = await getAppleIdToken()

            const result = await authStore.socialLogin('apple', idToken, firstName, lastName, marketingOptin)
            if (!result.success) {
                error.value = result.error
            }
            return result
        } catch (e) {
            if (e.error === 'popup_closed_by_user') {
                error.value = ''
                return { success: false, error: 'canceled' }
            }
            error.value = e.message || 'Apple sign-in failed'
            return { success: false, error: error.value }
        } finally {
            isLoading.value = false
        }
    }

    return {
        isLoading,
        error,
        getGoogleIdToken,
        getAppleIdToken,
        loginWithGoogle,
        loginWithApple,
    }
}
