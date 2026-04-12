<template>
  <div class="admin-page">
    <div class="admin-header">
      <h2 class="section-title">Centrální administrace</h2>
      <p class="auth-subtitle">Správa uživatelů, piv, pivovarů, podniků a stylů.</p>
    </div>

    <div class="admin-tabs">
      <button @click="activeTab = 'users'" :class="{ active: activeTab === 'users' }">
        <UsersIcon :size="18" /> Uživatelé
      </button>
      <button @click="activeTab = 'beers'" :class="{ active: activeTab === 'beers' }">
        <BeerIcon :size="18" /> Piva
      </button>
      <button @click="activeTab = 'breweries'" :class="{ active: activeTab === 'breweries' }">
        <FactoryIcon :size="18" /> Pivovary
      </button>
      <button @click="activeTab = 'locations'" :class="{ active: activeTab === 'locations' }">
        <MapIcon :size="18" /> Podniky
      </button>
      <button @click="activeTab = 'styles'" :class="{ active: activeTab === 'styles' }">
        <PaletteIcon :size="18" /> Styly
      </button>
    </div>

    <div v-if="isLoading || isUsersLoading" class="loading-state">Načítám data... ⏳</div>

    <div v-else class="admin-content">
      
      <section v-if="activeTab === 'users'" class="admin-section">
        <div class="section-header"><h3>Registrovaní pivaři</h3></div>
        <div class="admin-table-wrapper">
          <table class="admin-table">
            <thead><tr><th>Uživatel</th><th>Role</th><th style="width: 80px;">Akce</th></tr></thead>
            <tbody>
              <tr v-for="u in allUsers" :key="u.id">
                <td><strong>{{ u.username }}</strong><br><small>{{ u.email }}</small></td>
                <td><span class="badge" :class="u.role">{{ u.role }}</span></td>
                <td>
                  <div class="action-buttons" v-if="u.id !== user.id">
                    <BaseButton variant="danger" isIconOnly @click="confirmDelete(u.id, 'user')">
                      <template #icon><UserMinusIcon :size="18" /></template>
                    </BaseButton>
                  </div>
                  <span v-else style="font-size: 0.85rem; color: #94a3b8; font-style: italic;">To jsi ty</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="activeTab === 'beers'" class="admin-section">
        <div class="section-header">
          <h3>Katalog piv</h3>
          <BaseButton variant="add" @click="openAddModal('beer')"><template #icon><PlusIcon :size="18" /></template>Přidat pivo</BaseButton>
        </div>
        <div class="admin-table-wrapper">
          <table class="admin-table">
            <thead><tr><th>Název</th><th>Pivovar</th><th>Styl</th><th style="width: 100px;">Akce</th></tr></thead>
            <tbody>
              <tr v-for="beer in beers" :key="beer.id">
                <td><strong>{{ beer.name }}</strong></td>
                <td>{{ beer.brewery_name }}</td>
                <td>{{ beer.style }}</td>
                <td>
                  <div class="action-buttons">
                    <BaseButton variant="edit" isIconOnly @click="openEditModal(beer, 'beer')"><template #icon><PencilIcon :size="18" /></template></BaseButton>
                    <BaseButton variant="danger" isIconOnly @click="confirmDelete(beer.id, 'beer')"><template #icon><Trash2Icon :size="18" /></template></BaseButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="activeTab === 'breweries'" class="admin-section">
        <div class="section-header">
          <h3>Pivovary</h3>
          <BaseButton variant="add" @click="openAddModal('brewery')"><template #icon><PlusIcon :size="18" /></template> Přidat pivovar</BaseButton>
        </div>
        <div class="admin-table-wrapper">
          <table class="admin-table">
            <thead><tr><th>Název</th><th>Město</th><th style="width: 100px;">Akce</th></tr></thead>
            <tbody>
              <tr v-for="brew in breweries" :key="brew.id">
                <td><strong>{{ brew.name }}</strong></td>
                <td>{{ brew.city || '-' }}</td>
                <td>
                  <div class="action-buttons">
                    <BaseButton variant="edit" isIconOnly @click="openEditModal(brew, 'brewery')"><template #icon><PencilIcon :size="18" /></template></BaseButton>
                    <BaseButton variant="danger" isIconOnly @click="confirmDelete(brew.id, 'brewery')"><template #icon><Trash2Icon :size="18" /></template></BaseButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="activeTab === 'locations'" class="admin-section">
        <div class="section-header">
          <h3>Hospody a místa</h3>
          <BaseButton variant="add" @click="openAddModal('location')"><template #icon><PlusIcon :size="18" /></template>Nové místo</BaseButton>
        </div>
        <div class="admin-table-wrapper">
          <table class="admin-table">
            <thead><tr><th>Název</th><th>Typ</th><th>Město</th><th style="width: 100px;">Akce</th></tr></thead>
            <tbody>
              <tr v-for="loc in locations" :key="loc.id">
                <td><strong>{{ loc.name }}</strong></td>
                <td><span class="badge" :class="loc.type === 'hospoda' ? 'user' : 'admin'">{{ loc.type === 'hospoda' ? 'Veřejné' : 'Soukromé' }}</span></td>
                <td>{{ loc.city || '-' }}</td>
                <td>
                  <div class="action-buttons">
                    <BaseButton variant="edit" isIconOnly @click="openEditModal(loc, 'location')"><template #icon><PencilIcon :size="18" /></template></BaseButton>
                    <BaseButton variant="danger" isIconOnly @click="confirmDelete(loc.id, 'location')"><template #icon><Trash2Icon :size="18" /></template></BaseButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="activeTab === 'styles'" class="admin-section">
        <div class="section-header">
          <h3>Pivní styly</h3>
          <BaseButton variant="add" @click="openAddModal('style')"><template #icon><PlusIcon :size="18" /></template> Nový styl</BaseButton>
        </div>
        <div class="admin-table-wrapper">
          <table class="admin-table">
            <thead><tr><th>Název stylu</th><th style="width: 100px;">Akce</th></tr></thead>
            <tbody>
              <tr v-for="style in styles" :key="style.id">
                <td><strong>{{ style.name }}</strong></td>
                <td>
                  <div class="action-buttons">
                    <BaseButton variant="edit" isIconOnly @click="openEditModal(style, 'style')"><template #icon><PencilIcon :size="18" /></template></BaseButton>
                    <BaseButton variant="danger" isIconOnly @click="confirmDelete(style.id, 'style')"><template #icon><Trash2Icon :size="18" /></template></BaseButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <DeleteConfirmModal :show="deleteModal.show" @close="deleteModal.show = false" @confirm="handleDelete" />
    
    <AddBeerModal :show="modals.beer" :breweries="breweries" :styles="styles" :form="formData.beer" @close="modals.beer = false" @submit="submitForm('beer')" />
    <AddBreweryModal :show="modals.brewery" :form="formData.brewery" @close="modals.brewery = false" @submit="submitForm('brewery')" />
    <AddLocationModal :show="modals.location" :form="formData.location" @close="modals.location = false" @submit="submitForm('location')" />
    
    <BaseModal :show="modals.style" @close="modals.style = false">
      <template #header><h2>{{ isEditing ? 'Upravit' : 'Nový' }} styl</h2></template>
      <template #body>
        <form @submit.prevent="submitForm('style')" style="display: flex; flex-direction: column; gap: 1.25rem;">
          <BaseInput v-model="formData.style.name" label="Název stylu *" required />
          <BaseButton type="submit" variant="add"><template #icon><SaveIcon :size="18" /></template>Uložit</BaseButton>
        </form>
      </template>
    </BaseModal>

    <transition name="toast-fade"><div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div></transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { UsersIcon, BeerIcon, FactoryIcon, MapIcon, PaletteIcon, PlusIcon, PencilIcon, Trash2Icon, UserMinusIcon, SaveIcon } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import BaseButton from '../components/BaseButton.vue'
import BaseInput from '../components/BaseInput.vue'
import BaseModal from '../components/BaseModal.vue'
import DeleteConfirmModal from '../components/modals/DeleteConfirmModal.vue'
import AddBeerModal from '../components/modals/AddBeerModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'
import AddLocationModal from '../components/modals/AddLocationModal.vue'

const authStore = useAuthStore(); const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore); const { beers, breweries, locations, styles, isLoading } = storeToRefs(catalogStore)

const activeTab = ref('locations'); const allUsers = ref([]); const isUsersLoading = ref(false)
const toast = ref({ show: false, message: '', type: 'toast-success' }); const deleteModal = ref({ show: false, id: null, type: '' })
const modals = ref({ beer: false, brewery: false, location: false, style: false }); const isEditing = ref(false)

const formData = ref({
  beer: { id: null, name: '', brewery_id: '', style: '', epm: '', abv: '' },
  brewery: { id: null, name: '', city: '', zip_code: '', country: 'Česká republika', address: '', street_number: '', email: '', phone: '', website: '' },
  location: { id: null, name: '', type: 'hospoda', city: '', zip_code: '', country: 'Česká republika', address: '', street_number: '', email: '', phone: '', website: '', opening_hours: '' },
  style: { id: null, name: '' }
})

const showToast = (message, type = 'toast-success') => { toast.value = { show: true, message, type }; setTimeout(() => { toast.value.show = false }, 3000) }

const fetchUsers = async () => {
    isUsersLoading.value = true; try {
        const res = await fetch('https://www.pivoo.cz/backend/api/users.php', { headers: { 'Authorization': `Bearer ${authStore.token}` } })
        const result = await res.json(); if (result.status === 'success') allUsers.value = result.data
    } catch (e) { showToast('Chyba uživatelů.', 'toast-error') } finally { isUsersLoading.value = false }
}

onMounted(() => { if (user.value) { catalogStore.fetchAllData(user.value.id); fetchUsers() } })

const openAddModal = (type) => {
  isEditing.value = false
  Object.keys(modals.value).forEach(m => modals.value[m] = false)
  modals.value[type] = true
}

const openEditModal = (item, type) => {
  isEditing.value = true
  formData.value[type] = { ...item }
  modals.value[type] = true
}

const submitForm = async (type) => {
  const data = formData.value[type]
  let endpoint = `add_${type}.php`
  if (isEditing.value) endpoint = `update_${type}.php`

  try {
    const res = await fetch(`https://www.pivoo.cz/backend/api/${endpoint}`, { 
      method: 'POST', 
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${authStore.token}` }, 
      body: JSON.stringify(data) 
    })
    const result = await res.json()
    if (result.status === 'success') { showToast(result.message); modals.value[type] = false; await catalogStore.fetchAllData(user.value.id) }
    else { showToast(result.message, 'toast-error') }
  } catch (e) { showToast('Chyba serveru.', 'toast-error') }
}

const confirmDelete = (id, type) => { deleteModal.value = { show: true, id, type } }
const handleDelete = async () => {
  const { id, type } = deleteModal.value; let endpoint = `delete_${type}.php`
  try {
    const res = await fetch(`https://www.pivoo.cz/backend/api/${endpoint}`, { 
      method: 'POST', 
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${authStore.token}` }, 
      body: JSON.stringify({ id }) 
    })
    const result = await res.json()
    if (result.status === 'success') { showToast(result.message); if (type === 'user') await fetchUsers(); else await catalogStore.fetchAllData(user.value.id) }
  } finally { deleteModal.value.show = false }
}
</script>