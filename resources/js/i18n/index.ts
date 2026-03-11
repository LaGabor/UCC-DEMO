import { createI18n } from 'vue-i18n'
import en from './messages/en'
import hu from './messages/hu'

export type AppLocale = 'en' | 'hu'
const LOCALE_STORAGE_KEY = 'app_locale'

function normalizeLocale(value: string | null | undefined): AppLocale {
    return value === 'hu' ? 'hu' : 'en'
}

const initialLocale = normalizeLocale(window.localStorage.getItem(LOCALE_STORAGE_KEY))

const i18n = createI18n({
    legacy: false,
    locale: initialLocale,
    fallbackLocale: 'en',
    messages: {
        en,
        hu,
    },
})

export function setAppLocale(locale: string): void {
    const normalized = normalizeLocale(locale)
    i18n.global.locale.value = normalized
    window.localStorage.setItem(LOCALE_STORAGE_KEY, normalized)
}

export default i18n
