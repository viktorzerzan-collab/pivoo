export function useDetailFormatters(t, te, locale) {
  const translateDynamic = (val) => {
    if (!val) return val
    const key = `dynamic.locations.${val}`
    return te(key) ? t(key) : val
  }

  const formatLocationType = (type) => {
    if (!type) return ''
    const key = `dynamic.location_types.${type}`
    return te(key) ? t(key) : type
  }

  const translateStyle = (val) => {
    if (!val) return t('cards.no_style')
    const key = `dynamic.styles.${val}`
    return te(key) ? t(key) : val
  }

  const translateFermentation = (val) => {
    if (!val) return ''
    const key = `dynamic.fermentation.${val}`
    return te(key) ? t(key) : `${val} ${t('cards.fermentation_suffix')}`
  }

  const getCountryName = (code, fallback) => {
    if (!code) return fallback;
    try { 
      return new Intl.DisplayNames([locale.value], { type: 'region' }).of(code.toUpperCase()); 
    } catch (e) { 
      return fallback; 
    }
  }

  return {
    translateDynamic,
    formatLocationType,
    translateStyle,
    translateFermentation,
    getCountryName
  }
}
