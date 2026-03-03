import { createI18n } from 'vue-i18n'

/**
 * Load locale messages
 * 
 * The messages are structured as follows:
 * {
 *   "en": { "common": { "login": "Login" }, ... },
 *   "es": { "common": { "login": "Iniciar Sesión" }, ... }
 * }
 */
import en from './locales/en.json'
import es from './locales/es.json'
import de from './locales/de.json'
import fr from './locales/fr.json'
import pt from './locales/pt.json'
import eu from './locales/eu.json'
import ca from './locales/ca.json'
import gl from './locales/gl.json'

const messages = {
    en, es, de, fr, pt, eu, ca, gl
}

// Detect language
const savedLang = localStorage.getItem('preferredLanguage')
const browserLang = navigator.language.split('-')[0]
const fallbackLocale = 'en'

const locale = savedLang || (messages[browserLang] ? browserLang : fallbackLocale)

const i18n = createI18n({
    legacy: false, // Use Composition API
    locale: locale,
    fallbackLocale: fallbackLocale,
    messages,
    globalInjection: true
})

export default i18n
