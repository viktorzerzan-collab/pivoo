import { ref } from 'vue'

export function useCatalogModals() {
  const isAddModalOpen = ref(false)
  const isDetailOpen = ref(false)
  const selectedItem = ref(null)

  const openDetail = (item) => {
    selectedItem.value = item
    isDetailOpen.value = true
  }

  const closeDetail = () => {
    isDetailOpen.value = false
  }

  const openAddModal = () => {
    isAddModalOpen.value = true
  }

  const closeAddModal = () => {
    isAddModalOpen.value = false
  }

  return {
    isAddModalOpen,
    isDetailOpen,
    selectedItem,
    openDetail,
    closeDetail,
    openAddModal,
    closeAddModal
  }
}
