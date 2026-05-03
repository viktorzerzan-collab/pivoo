import { createI18n } from 'vue-i18n'
import cs from './locales/cs.json'
import en from './locales/en.json'
import de from './locales/de.json'
import pl from './locales/pl.json'

function getStartingLocale() {
  // 1. Zkusíme načíst uloženou preferenci z předchozí návštěvy
  const savedLocale = localStorage.getItem('pivoo_lang')
  if (savedLocale) {
    return savedLocale
  }
  
  // 2. Pokud uživatel preferenci nemá, zjistíme jazyk jeho prohlížeče
  const browserLang = navigator.language || navigator.userLanguage
  const shortLang = browserLang.split('-')[0].toLowerCase()

  // 3. Zkontrolujeme, zda zjištěný jazyk podporujeme
  const supportedLangs = ['cs', 'en', 'de', 'pl']
  if (supportedLangs.includes(shortLang)) {
    return shortLang
  }

  // 4. Pokud má prohlížeč v jazyce, který neumíme (např. španělsky), hodíme ho do češtiny
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