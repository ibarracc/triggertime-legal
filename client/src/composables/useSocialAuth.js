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

    async function loginWithGoogle() {
        isLoading.value = true
        error.value = ''

        try {
            await initGoogle()

            const idToken = await new Promise((resolve, reject) => {
                /* global google */
                google.accounts.id.initialize({
                    client_id: import.meta.env.VITE_GOOGLE_CLIENT_ID,
                    callback: (response) => {
                        if (response.credential) {
                            resolve(response.credential)
                        } else {
                            reject(new Error('No credential received'))
                        }
                    },
                })

                google.accounts.id.prompt((notification) => {
                    if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
                        // Fallback: use the button-based flow with popup
                        const buttonDiv = document.createElement('div')
                        buttonDiv.style.display = 'none'
                        document.body.appendChild(buttonDiv)
                        google.accounts.id.renderButton(buttonDiv, {
                            type: 'standard',
                            click_listener: () => {},
                        })
                        const btn = buttonDiv.querySelector('[role="button"]')
                        if (btn) btn.click()
                        else reject(new Error('Google Sign-In not available'))
                        buttonDiv.remove()
                    }
                })
            })

            const result = await authStore.socialLogin('google', idToken)
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

    async function loginWithApple() {
        isLoading.value = true
        error.value = ''

        try {
            await loadScript('https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js')

            /* global AppleID */
            AppleID.auth.init({
                clientId: import.meta.env.VITE_APPLE_SERVICE_ID,
                scope: 'name email',
                redirectURI: window.location.origin + '/auth/apple-callback',
                usePopup: true,
            })

            const response = await AppleID.auth.signIn()

            const idToken = response.authorization.id_token
            const firstName = response.user?.name?.firstName || null
            const lastName = response.user?.name?.lastName || null

            const result = await authStore.socialLogin('apple', idToken, firstName, lastName)
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
        loginWithGoogle,
        loginWithApple,
    }
}
