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
      <BaseLoader :show="isLoading || isUsersLoading" />

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
              <tr v-if="activeTab === 'users'">
                <th>Uživatel</th>
                <th>E-mail</th>
                <th>Role</th>
                <th class="w-100">Akce</th>
              </tr>
              <tr v-else>
                <th>Název</th>
                <th v-if="activeTab !== 'styles'">Info</th>
                <th class="w-100">Akce</th>
              </tr>
            </thead>
            <tbody>
              <template v-if="activeTab === 'users'">
                <tr v-for="u in allUsers" :key="u?.id" :class="{ 'banned-row': u.is_banned }">
                  <td data-label="Uživatel">
                    <div class="td-content user-cell">
                      <div class="table-avatar">
                        <img v-if="u.avatar" :src="'https://www.pivoo.cz/backend/uploads/avatars/' + u.avatar" alt="Avatar" />
                        <div v-else class="table-avatar-placeholder">
                          <UserIcon :size="20" stroke-width="1.5" />
                        </div>
                      </div>
                      <div class="user-info">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                          <strong :style="u.is_banned ? 'text-decoration: line-through; color: var(--text-muted);' : ''">
                            {{ u.username }}
                          </strong>
                          <span v-if="u.is_banned" class="badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; font-size: 0.65rem;">BANNED</span>
                        </div>
                        <small>{{ u.first_name }} {{ u.last_name }}</small>
                      </div>
                    </div>
                  </td>
                  <td data-label="E-mail">
                    <div class="td-content email-text">{{ u.email }}</div>
                  </td>
                  <td data-label="Role">
                    <div class="td-content">
                      <span class="badge" :class="u.role">{{ u.role }}</span>
                    </div>
                  </td>
                  <td data-label="Akce">
                    <div class="td-content action-buttons">
                      <button class="btn-edit is-icon-only" @click="openEditModal(u, 'users')" title="Upravit údaje">
                        <PencilIcon />
                      </button>
                      
                      <button v-if="u?.id !== user?.id" class="btn-primary is-icon-only" style="background-color: var(--blue);" @click="openPasswordModal(u)" title="Změnit heslo">
                        <KeyIcon />
                      </button>
                      
                      <button v-if="u?.id !== user?.id" class="is-icon-only" :style="u.is_banned ? 'background-color: #10b981; color: white; border: none;' : 'background-color: #64748b; color: white; border: none;'" @click="openBanModal(u)" :title="u.is_banned ? 'Odblokovat uživatele' : 'Zablokovat (Ban)'">
                        <UnlockIcon v-if="u.is_banned" />
                        <BanIcon v-else />
                      </button>

                      <button v-if="u?.id !== user?.id" class="btn-danger is-icon-only" @click="confirmDelete(u.id, activeTab)" title="Smazat uživatele">
                        <Trash2Icon />
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <template v-else>
                <tr v-for="item in currentItems" :key="item?.id">
                  <td data-label="Název">
                    <div class="td-content">
                      <strong>{{ item.name }}</strong>
                    </div>
                  </td>
                  <td v-if="activeTab === 'beers'" data-label="Info">
                    <div class="td-content">
                      <small>{{ item.brewery_name }} • {{ item.style }}</small>
                    </div>
                  </td>
                  <td v-if="['breweries', 'locations'].includes(activeTab)" data-label="Město">
                    <div class="td-content">
                      {{ item.city || '-' }}
                    </div>
                  </td>
                  <td data-label="Akce">
                    <div class="td-content action-buttons">
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
    <AddBeerModal :show="modals.beer" :isEditing="isEditing" :breweries="breweries" :styles="styles" :form="formData.beer" @close="modals.beer = false" @submit="submitForm('beer')" />
    <AddBreweryModal :show="modals.brewery" :isEditing="isEditing" :form="formData.brewery" @close="modals.brewery = false" @submit="submitForm('brewery')" />
    <AddLocationModal :show="modals.location" :isEditing="isEditing" :form="formData.location" @close="modals.location = false" @submit="submitForm('location')" />
    
    <EditUserModal 
      :show="modals.user" 
      :form="formData.user" 
      :is-current-user="formData.user.id === user?.id"
      @close="modals.user = false" 
      @submit="submitForm('user')" 
      @remove-avatar="handleRemoveAvatar"
    />
    
    <ChangePasswordModal 
      :show="modals.password" 
      :user="selectedUserForPassword" 
      @close="modals.password = false" 
      @submit="handlePasswordChange" 
    />

    <BanConfirmModal 
      :show="modals.ban" 
      :user="selectedUserForBan" 
      @close="modals.ban = false" 
      @confirm="handleBanConfirm" 
    />
    
    <BaseModal :show="modals.style" @close="modals.style = false">
      <template #header><h2>{{ isEditing ? 'Upravit' : 'Přidat' }} styl</h2></template>
      <template #body>
        <form @submit.prevent="submitForm('style')" style="display: flex; flex-direction: column; gap: 1.5rem;">
          <BaseInput v-model="formData.style.name" label="Název stylu *" required />
          <button type="submit" class="btn-add" style="padding: 1rem;"><SaveIcon /> Uložit styl</button>
        </form>
      </template>
    </BaseModal>

    <transition name="toast-fade"><div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div></transition>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { PlusIcon, PencilIcon, Trash2Icon, SaveIcon, KeyIcon, BanIcon, UnlockIcon, UserIcon } from 'lucide-vue-next'
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
import EditUserModal from '../components/modals/EditUserModal.vue'
import ChangePasswordModal from '../components/modals/ChangePasswordModal.vue'
import BanConfirmModal from '../components/modals/BanConfirmModal.vue'

const authStore = useAuthStore()
const catalogStore = useCatalogStore()
const { user } = storeToRefs(authStore)
const { beers, breweries, locations, styles, isLoading } = storeToRefs(catalogStore)

const activeTab = ref('users')
const allUsers = ref([])
const isUsersLoading = ref(false)
const toast = ref({ show: false, message: '', type: 'toast-success' })
const deleteModal = ref({ show: false, id: null, type: '' })
const modals = ref({ beer: false, brewery: false, location: false, style: false, user: false, password: false, ban: false })
const isEditing = ref(false)
const selectedUserForPassword = ref(null)
const selectedUserForBan = ref(null)

const tabs = [
  { id: 'users', label: 'Uživatelé' },
  { id: 'beers', label: 'Piva' },
  { id: 'breweries', label: 'Pivovary' },
  { id: 'locations', label: 'Podniky' },
  { id: 'styles', label: 'Styly' }
]

const currentLabelSingle = computed(() => ({ beers: 'pivo', breweries: 'pivovar', locations: 'podnik', styles: 'styl', users: 'uživatele' }[activeTab.value]))
const currentItems = computed(() => ({ beers: beers.value, breweries: breweries.value, locations: locations.value, styles: styles.value }[activeTab.value] || []))

const formData = ref({
  beer: { 
    id: null, name: '', brewery_id: '', style_id: '', epm: '', abv: '', 
    ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '',
    is_unfiltered: false, is_unpasteurized: false 
  },
  brewery: { id: null, name: '', city: '', zip_code: '', country: 'Česká republika', address: '', street_number: '', email: '', phone: '', website: '', logoFile: null },
  location: { id: null, name: '', type: 'hospoda', city: '', zip_code: '', country: 'Česká republika', address: '', street_number: '', email: '', phone: '', website: '', opening_hours: '' },
  style: { id: null, name: '' },
  user: { id: null, first_name: '', last_name: '', username: '', email: '', role: 'user', avatar: null }
})

const showToast = (message, type = 'toast-success') => { 
  toast.value = { show: true, message, type }; setTimeout(() => { toast.value.show = false }, 3000) 
}

const fetchUsers = async () => {
  isUsersLoading.value = true
  try {
    const res = await apiFetch('/users.php')
    if (res.status === 'success') allUsers.value = res.data
  } finally { isUsersLoading.value = false }
}

onMounted(() => { 
  catalogStore.fetchAllData()
  fetchUsers() 
})

const openAddModal = (t) => { 
  isEditing.value = false
  Object.keys(modals.value).forEach(m => modals.value[m] = false)
  const key = t === 'breweries' ? 'brewery' : (t === 'beers' ? 'beer' : (t === 'locations' ? 'location' : (t === 'styles' ? 'style' : 'user')))
  
  if (key === 'beer') {
    formData.value.beer = { 
      id: null, name: '', brewery_id: '', style_id: '', epm: '', abv: '', 
      ibu: '', ebc: '', hops: '', malts: '', fermentation: '', tags: '',
      is_unfiltered: false, is_unpasteurized: false 
    }
  }
  
  if (key === 'brewery') formData.value.brewery.logoFile = null
  modals.value[key] = true 
}

const openEditModal = (item, t) => { 
  isEditing.value = true
  const key = t === 'styles' ? 'style' : (t === 'beers' ? 'beer' : (t === 'locations' ? 'location' : (t === 'breweries' ? 'brewery' : 'user')))
  
  if (key === 'beer') {
    formData.value.beer = { 
      ...item, 
      is_unfiltered: !!item.is_unfiltered, 
      is_unpasteurized: !!item.is_unpasteurized 
    }
  } else {
    formData.value[key] = { ...item, logoFile: null }
  }
  
  modals.value[key] = true 
}

const openPasswordModal = (u) => {
  selectedUserForPassword.value = u
  modals.value.password = true
}

const handlePasswordChange = async (payload) => {
  try {
    const res = await apiFetch('/admin_change_password.php', {
      method: 'POST',
      body: JSON.stringify(payload)
    })
    
    if (res.status === 'success') {
      showToast(res.message)
      modals.value.password = false
    } else {
      showToast(res.message || 'Nepodařilo se změnit heslo.', 'toast-error')
    }
  } catch (e) {
    showToast('Chyba serveru při změně hesla.', 'toast-error')
  }
}

const handleRemoveAvatar = async (userId) => {
  if (!confirm('Opravdu chcete tomuto uživateli smazat profilovou fotku?')) return;

  try {
    const res = await apiFetch('/admin_remove_avatar.php', {
      method: 'POST',
      body: JSON.stringify({ user_id: userId })
    })
    
    if (res.status === 'success') {
      showToast(res.message)
      modals.value.user = false 
      fetchUsers() 
    } else {
      showToast(res.message || 'Nepodařilo se smazat fotku.', 'toast-error')
    }
  } catch (e) {
    showToast('Chyba serveru při mazání fotky.', 'toast-error')
  }
}

const openBanModal = (u) => {
  selectedUserForBan.value = u
  modals.value.ban = true
}

const handleBanConfirm = async (u) => {
  const newStatus = u.is_banned ? 0 : 1;

  try {
    const res = await apiFetch('/admin_toggle_ban.php', {
      method: 'POST',
      body: JSON.stringify({ user_id: u.id, is_banned: newStatus })
    })
    
    if (res.status === 'success') {
      showToast(res.message)
      modals.value.ban = false
      fetchUsers()
    } else {
      showToast(res.message || 'Nepodařilo se změnit stav blokace.', 'toast-error')
    }
  } catch (e) {
    showToast('Chyba serveru při změně blokace.', 'toast-error')
  }
}

const submitForm = async (t) => {
  try {
    const endpoint = isEditing.value ? `update_${t}.php` : `add_${t}.php`
    let bodyData;
    if (t === 'brewery') {
      bodyData = new FormData();
      Object.keys(formData.value[t]).forEach(key => {
         if (formData.value[t][key] !== null && formData.value[t][key] !== undefined && formData.value[t][key] !== '') {
           bodyData.append(key, formData.value[t][key])
         }
      });
    } else {
      bodyData = JSON.stringify(formData.value[t])
    }

    const res = await apiFetch(`/${endpoint}`, { method: 'POST', body: bodyData })
    if (res.status === 'success') { 
      showToast(res.message); modals.value[t] = false; 
      t === 'user' ? fetchUsers() : catalogStore.fetchAllData() 
    } else {
      showToast(res.message || 'Chyba při ukládání.', 'toast-error')
    }
  } catch (e) { showToast('Chyba serveru.', 'toast-error') }
}

const confirmDelete = (id, t) => {
  const typeMap = { users: 'user', beers: 'beer', breweries: 'brewery', locations: 'location', styles: 'style' }
  deleteModal.value = { show: true, id, type: typeMap[t] } 
}

const handleDelete = async () => {
  try {
    const res = await apiFetch(`/delete_${deleteModal.value.type}.php`, { 
      method: 'POST', 
      body: JSON.stringify({ id: deleteModal.value.id }) 
    })
    
    if (res.status === 'success') {
      showToast("Smazáno")
      deleteModal.value.type === 'user' ? fetchUsers() : catalogStore.fetchAllData()
    } else {
      showToast(res.message || 'Nepodařilo se smazat.', 'toast-error')
    }
  } catch(e) {
    showToast('Chyba při mazání.', 'toast-error')
  } finally { 
    deleteModal.value.show = false 
  }
}
</script>

<style scoped>
.admin-page { flex: 1; display: flex; flex-direction: column; }
.admin-header { margin-bottom: 2rem; }
.admin-tabs { display: flex; gap: 0.5rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-top: 1rem; overflow-x: auto; }
.admin-tabs button { padding: 0.6rem 1.2rem; border: none; background: none; color: var(--text-muted); cursor: pointer; font-weight: 600; border-radius: 8px; white-space: nowrap; box-shadow: none; }
.admin-tabs button.active { background: var(--primary); color: #1e293b; }
.admin-layout { position: relative; flex: 1; min-height: 400px; }
.admin-section { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm); transition: background-color 0.5s ease; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.admin-table-wrapper { overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th { text-align: left; padding: 0.75rem; border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; background: var(--bg-app); transition: background-color 0.5s ease; }
.admin-table td { padding: 0.75rem; border-bottom: 1px solid var(--border); vertical-align: middle; color: var(--text-main); }
.action-buttons { display: flex; gap: 0.5rem; }
.w-100 { width: 100px; }

/* VYLEPŠENÉ STYLY PRO MINIATURU UŽIVATELE (Orámování s mezerou) */
.user-cell { display: flex; align-items: center; gap: 0.8rem; text-align: left; }
.user-info { display: flex; flex-direction: column; align-items: flex-start; }

.table-avatar { 
  width: 44px; 
  height: 44px; 
  border-radius: 50%; 
  border: 2px solid var(--border); /* Silnější vnější rámeček */
  padding: 2px; /* Vytvoří tu průhlednou "mezeru" mezi fotkou a rámečkem */
  flex-shrink: 0; 
  background: var(--bg-app); 
}
.table-avatar img { 
  width: 100%; 
  height: 100%; 
  object-fit: cover; 
  border-radius: 50%; /* Zakulacení samotné fotky uvnitř */
}
.table-avatar-placeholder { 
  width: 100%; 
  height: 100%; 
  background: var(--bg-panel); 
  border-radius: 50%; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  color: var(--text-muted); 
}

.banned-row td { opacity: 0.85; background-color: rgba(0, 0, 0, 0.02); }
html.dark-mode .banned-row td { background-color: rgba(255, 255, 255, 0.01); }

@media (max-width: 768px) {
  .section-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .section-header .btn-add { width: 100%; padding: 1rem; font-size: 1.05rem; }
  .admin-section { padding: 1rem; }

  .admin-table-wrapper { 
    border: none; 
    box-shadow: none; 
    background: transparent; 
    padding: 0; 
    overflow-x: visible; 
  }
  
  .admin-table thead { display: none; }
  
  .admin-table, .admin-table tbody, .admin-table tr, .admin-table td { 
    display: block; 
    width: 100%; 
  }
  
  .admin-table tr { 
    margin-bottom: 1.25rem; 
    border: 1px solid var(--border); 
    border-radius: 12px; 
    padding: 1.25rem; 
    background: var(--bg-panel); 
    box-shadow: var(--shadow-sm); 
  }
  
  .admin-table tr.banned-row {
    background-color: var(--bg-app);
    border-color: rgba(239, 68, 68, 0.3);
  }

  .admin-table td { 
    border-bottom: 1px solid var(--border); 
    padding: 0.75rem 0; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    text-align: right;
  }
  
  .td-content.user-cell { flex-direction: row-reverse; justify-content: flex-end; }
  .user-info { align-items: flex-end; }

  .admin-table td:last-child { border-bottom: none; padding-bottom: 0; }
  .admin-table td:first-child { padding-top: 0; }
  
  .admin-table td::before { 
    content: attr(data-label); 
    font-weight: 800; 
    color: var(--text-muted); 
    font-size: 0.75rem; 
    text-transform: uppercase; 
    margin-right: 1rem;
    flex-shrink: 0;
  }
  
  .td-content { 
    display: flex; 
    flex-direction: column; 
    align-items: flex-end; 
  }
  
  .td-content.action-buttons { 
    flex-direction: row; 
    align-items: center; 
    flex-wrap: wrap; 
    justify-content: flex-end;
  }
  
  .email-text { 
    word-break: break-all; 
  }
}
</style>