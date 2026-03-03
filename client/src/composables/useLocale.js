import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/api/auth'

export function useLocale() {
    const { locale, t } = useI18n()
    const auth = useAuthStore()

    const availableLocales = [
        { code: 'en', name: 'English', flag: '🇺🇸' },
        { code: 'es', name: 'Español', flag: '🇪🇸' },
        { code: 'de', name: 'Deutsch', flag: '🇩🇪' },
        { code: 'fr', name: 'Français', flag: '🇫🇷' },
        { code: 'pt', name: 'Português', flag: '🇵🇹' },
        { code: 'eu', name: 'Euskera', flag: '🇪🇸' }, // Basque flag? Usually ikurriña but Emojis often use ES
        { code: 'ca', name: 'Català', flag: '🇪🇸' },
        { code: 'gl', name: 'Galego', flag: '🇪🇸' }
    ]

    const currentLocale = computed(() => {
        return availableLocales.find(l => l.code === locale.value) || availableLocales[0]
    })

    const setLocale = async (code) => {
        if (!availableLocales.find(l => l.code === code)) return

        locale.value = code
        localStorage.setItem('preferredLanguage', code)
        document.documentElement.lang = code

        // If logged in, update profile on backend
        if (auth.isAuthenticated && auth.user) {
            try {
                await authApi.updateProfile({
                    first_name: auth.user.first_name,
                    last_name: auth.user.last_name,
                    language: code
                })
                // Update local user object
                auth.user.language = code
            } catch (err) {
                console.error('Failed to sync language to profile:', err)
            }
        }
    }

    return {
        locale,
        currentLocale,
        availableLocales,
        setLocale,
        t
    }
}
