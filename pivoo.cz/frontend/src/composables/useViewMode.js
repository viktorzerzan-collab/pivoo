import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { LayoutGridIcon, MapIcon } from 'lucide-vue-next'

export function useViewMode(defaultMode = 'list') {
  const { t } = useI18n()
  
  const viewMode = ref(defaultMode)

  const viewModeOptions = computed(() => [
    { value: 'list', label: t('catalog.view_cards'), icon: LayoutGridIcon },
    { value: 'map', label: t('catalog.view_map'), icon: MapIcon }
  ])

  return {
    viewMode,
    viewModeOptions
  }
}
