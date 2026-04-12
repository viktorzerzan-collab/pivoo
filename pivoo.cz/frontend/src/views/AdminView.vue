<template>
  <div class="admin-page">
    <div class="admin-header">
      <h2 class="section-title">Administrace</h2>
      <nav class="admin-tabs">
        <button v-for="t in tabs" :key="t.id" @click="activeTab = t.id" :class="{ active: activeTab === t.id }">
          {{ t.label }}
        </button>
      </nav>
    </div>

    <div class="admin-layout">
      <BaseLoader :show="isLoading || isUsersLoading" message="Načítám správu..." />

      <div class="admin-section">
        <div class="section-header">
          <h3>{{ tabs.find(t => t.id === activeTab).label }}</h3>
          <button v-if="activeTab !== 'users'" class="btn-add" @click="openAddModal(activeTab)">
            <PlusIcon /> Přidat {{ currentLabelSingle }}
          </button>
        </div>

        <div class="admin-table-wrapper">
          <table class="admin-table">
            <thead>
              <tr v-if="activeTab === 'users'"><th>Uživatel</th><th>Role</th><th class="w-100">Akce</th></tr>
              <tr v-else><th>Název</th><th v-if="activeTab !== 'styles'">Info</th><th class="w-100">Akce</th></tr>
            </thead>
            <tbody>
              <template v-if="activeTab === 'users'">
                <tr v-for="u in allUsers" :key="u.id">
                  <td><strong>{{ u.username }}</strong><br><small>{{ u.email }}</small></td>
                  <td><span class="badge" :class="u.role">{{ u.role }}</span></td>
                  <td>
                    <button v-if="u.id !== user.id" class="btn-danger is-icon-only" @click="confirmDelete(u.id, 'user')">
                      <UserMinusIcon />
                    </button>
                  </td>
                </tr>
              </template>
              <template v-else>
                <tr v-for="item in currentItems" :key="item.id">
                  <td><strong>{{ item.name }}</strong></td>
                  <td v-if="activeTab === 'beers'"><small>{{ item.brewery_name }}</small></td>
                  <td v-if="['breweries', 'locations'].includes(activeTab)">{{ item.city || '-' }}</td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-edit is-icon-only" @click="openEditModal(item, activeTab)">
                        <PencilIcon />
                      </button>
                      <button class="btn-danger is-icon-only" @click="confirmDelete(item.id, activeTab)">
                        <Trash2Icon />
                      </button>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <DeleteConfirmModal :show="deleteModal.show" @close="deleteModal.show = false" @confirm="handleDelete" />
    <AddBeerModal :show="modals.beer" :breweries="breweries" :styles="styles" :form="formData.beer" @close="modals.beer = false" @submit="submitForm('beer')" />
    <AddBreweryModal :show="modals.brewery" :form="formData.brewery" @close="modals.brewery = false" @submit="submitForm('brewery')" />
    <AddLocationModal :show="modals.location" :form="formData.location" @close="modals.location = false" @submit="submitForm('location')" />
    <BaseModal :show="modals.style" @close="modals.style = false">
      <template #header><h2>{{ isEditing ? 'Upravit' : 'Přidat' }} styl</h2></template>
      <template #body>
        <form @submit.prevent="submitForm('style')" style="display:flex; flex-direction:column; gap:1.5rem">
          <BaseInput v-model="formData.style.name" label="Název stylu *" required />
          <button type="submit" class="btn-add"><SaveIcon /> Uložit styl</button>
        </form>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, PencilIcon, Trash2Icon, UserMinusIcon, SaveIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useCatalogStore } from '../stores/catalog'
import BaseInput from '../components/BaseInput.vue'
import BaseModal from '../components/BaseModal.vue'
import BaseLoader from '../components/BaseLoader.vue'
import DeleteConfirmModal from '../components/modals/DeleteConfirmModal.vue'
import AddBeerModal from '../components/modals/AddBeerModal.vue'
import AddBreweryModal from '../components/modals/AddBreweryModal.vue'
import AddLocationModal from '../components/modals/AddLocationModal.vue'

const authStore = useAuthStore(); const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore); const { beers, breweries, locations, styles, isLoading } = storeToRefs(catalogStore)

const activeTab = ref('users'); const allUsers = ref([]); const isUsersLoading = ref(false)
const deleteModal = ref({ show: false, id: null, type: '' }); const isEditing = ref(false)
const modals = ref({ beer: false, brewery: false, location: false, style: false })
const tabs = [{id:'users', label:'Uživatelé'}, {id:'beers', label:'Piva'}, {id:'breweries', label:'Pivovary'}, {id:'locations', label:'Podniky'}, {id:'styles', label:'Styly'}]

const currentLabelSingle = computed(() => ({ beers: 'pivo', breweries: 'pivovar', locations: 'podnik', styles: 'styl' }[activeTab.value]))
const currentItems = computed(() => ({ beers: beers.value, breweries: breweries.value, locations: locations.value, styles: styles.value }[activeTab.value] || []))

const formData = ref({
  beer: { id: null, name: '', brewery_id: '', style: '', epm: '', abv: '' },
  brewery: { id: null, name: '', city: '', zip_code: '', country: 'Česká republika', address: '', street_number: '', email: '', phone: '', website: '' },
  location: { id: null, name: '', type: 'hospoda', city: '', zip_code: '', country: 'Česká republika', address: '', street_number: '', email: '', phone: '', website: '', opening_hours: '' },
  style: { id: null, name: '' }
})

const fetchUsers = async () => {
  isUsersLoading.value = true
  try { const res = await apiFetch('/users.php'); if(res.status==='success') allUsers.value = res.data } finally { isUsersLoading.value = false }
}

onMounted(() => { catalogStore.fetchAllData(); fetchUsers() })

const openAddModal = (t) => { 
  isEditing.value = false; Object.keys(modals.value).forEach(m => modals.value[m] = false)
  const key = t === 'breweries' ? 'brewery' : (t === 'beers' ? 'beer' : (t === 'locations' ? 'location' : 'style'))
  modals.value[key] = true 
}

const openEditModal = (item, t) => { 
  isEditing.value = true; 
  const key = t === 'styles' ? 'style' : (t === 'beers' ? 'beer' : (t === 'locations' ? 'location' : 'brewery'))
  formData.value[key] = {...item}; modals.value[key] = true 
}

const submitForm = async (t) => {
  try { 
    const res = await apiFetch(`/${isEditing.value?'update':'add'}_${t}.php`, { method:'POST', body: formData.value[t] })
    if(res.status==='success') { modals.value[t] = false; catalogStore.fetchAllData() }
  } catch(e) { console.error(e) }
}

const confirmDelete = (id, t) => { deleteModal.value = { show: true, id, type: t === 'user' ? 'user' : t.slice(0,-1) } }
const handleDelete = async () => {
  try { await apiFetch(`/delete_${deleteModal.value.type}.php`, { method:'POST', body: { id: deleteModal.value.id } })
    deleteModal.value.type === 'user' ? fetchUsers() : catalogStore.fetchAllData()
  } finally { deleteModal.value.show = false }
}
</script>

<style scoped>
.admin-page { flex: 1; display: flex; flex-direction: column; }
.admin-tabs { display: flex; gap: 0.5rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-top: 1rem; overflow-x: auto; }
.admin-tabs button { padding: 0.6rem 1.2rem; border: none; background: none; color: var(--text-muted); cursor: pointer; font-weight: 600; border-radius: 8px; box-shadow: none; }
.admin-tabs button.active { background: var(--primary); color: #1e293b; }
.admin-layout { position: relative; min-height: 400px; }
.admin-section { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm); }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th { text-align: left; padding: 0.75rem; border-bottom: 2px solid var(--border); font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; }
.admin-table td { padding: 0.75rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
.action-buttons { display: flex; gap: 0.5rem; }
.w-100 { width: 100px; }
</style>