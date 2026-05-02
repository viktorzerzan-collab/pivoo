import { createI18n } from 'vue-i18n'
import cs from './locales/cs.json'
import en from './locales/en.json'
import de from './locales/de.json'
import pl from './locales/pl.json'

function getStartingLocale() {
  const savedLocale = localStorage.getItem('pivoo_lang')
  if (savedLocale) {
    return savedLocale
  }
  return 'cs'
}

const i18n = createI18n({
  legacy: false, 
  globalInjection: true,
  locale: getStartingLocale(), 
  fallbackLocale: 'cs',
  messages: {
    cs,
    en,
    de,
    pl
  }
})

export default i18n