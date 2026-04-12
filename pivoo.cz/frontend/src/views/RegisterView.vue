<template>
  <div class="login-wrapper">
    <transition name="toast-fade"><div v-if="toast.show" class="toast-notification" :class="toast.type">{{ toast.message }}</div></transition>

    <div class="login-card register-card">
      <div class="logo-container">
        <span class="beer-icon">🍻</span>
        <h1 class="logo-text">Nová registrace</h1>
      </div>
      <p class="subtitle">Přidej se k pivní komunitě Pivoo.cz</p>

      <form @submit.prevent="handleRegister" class="login-form">
        <div class="form-row">
          <div class="half"><BaseInput v-model="form.first_name" label="Křestní jméno" required /></div>
          <div class="half"><BaseInput v-model="form.last_name" label="Příjmení" required /></div>
        </div>

        <BaseInput v-model="form.username" label="Přezdívka (Username)" required />
        <BaseInput v-model="form.email" type="email" label="E-mail" placeholder="karel@novak.cz" required />
        <BaseInput v-model="form.birthdate" type="date" label="Datum narození (Pro kontrolu 18+)" required />

        <BaseInput v-model="form.password" type="password" label="Heslo" placeholder="Min. 8 znaků, číslo a spec. znak" required />
        <BaseInput v-model="form.password_confirm" type="password" label="Potvrzení hesla" required />

        <BaseButton type="submit" variant="submit" class="login-btn" :disabled="isLoading">
          {{ isLoading ? 'Zpracovávám...' : 'Vytvořit účet' }}
        </BaseButton>

        <div class="register-link">
          Už máš účet? <router-link to="/">Přihlas se zde</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseInput from '../components/BaseInput.vue'
import BaseButton from '../components/BaseButton.vue'

const router = useRouter()
const isLoading = ref(false)

const toast = ref({ show: false, message: '', type: 'toast-success' })
const showToast = (message, type = 'toast-error') => { 
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 4000) 
}

const form = ref({
  first_name: '',
  last_name: '',
  username: '',
  email: '',
  birthdate: '',
  password: '',
  password_confirm: ''
})

const handleRegister = async () => {
  // 1. Rychlá kontrola na frontendu: Jsou hesla stejná?
  if (form.value.password !== form.value.password_confirm) {
    showToast('Zadaná hesla se neshodují.')
    return
  }

  // 2. Rychlá kontrola na frontendu: Je uživateli 18 let?
  const bday = new Date(form.value.birthdate)
  const today = new Date()
  let age = today.getFullYear() - bday.getFullYear()
  const m = today.getMonth() - bday.getMonth()
  if (m < 0 || (m === 0 && today.getDate() < bday.getDate())) { age-- }
  
  if (age < 18) {
    showToast('Aplikace je přístupná pouze od 18 let.')
    return
  }

  isLoading.value = true

  // 3. Odeslání dat na náš PHP backend
  try {
    const response = await fetch('https://www.pivoo.cz/backend/api/register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form.value)
    })
    
    const result = await response.json()
    
    if (result.status === 'success') {
      showToast(result.message, 'toast-success')
      // Počkáme 2 vteřiny, ať si uživatel přečte úspěšnou hlášku, a přesměrujeme ho na přihlášení
      setTimeout(() => {
        router.push('/')
      }, 2000)
    } else {
      showToast(result.message, 'toast-error')
    }
  } catch (error) {
    showToast('Chyba při komunikaci se serverem.')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.toast-notification { position: fixed; top: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: 8px; color: white; font-weight: bold; z-index: 9999; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2); }
.toast-success { background-color: #10b981; }
.toast-error { background-color: #ef4444; }
.toast-fade-enter-active, .toast-fade-leave-active { transition: all 0.3s ease; }
.toast-fade-enter-from, .toast-fade-leave-to { opacity: 0; transform: translateY(-20px); }

.login-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; background-color: #f3f4f6; padding: 2rem 1rem; }
.login-card { background: white; padding: 3rem 2rem; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; text-align: center; border: 1px solid #e5e7eb; }
.register-card { max-width: 500px; /* Formulář je širší kvůli jménu a příjmení vedle sebe */ }

.logo-container { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.beer-icon { font-size: 3rem; line-height: 1; }
.logo-text { font-size: 2rem; font-weight: bold; color: #ca8a04; margin: 0; }
.subtitle { color: #6b7280; font-size: 1rem; margin-bottom: 2rem; }

.login-form { display: flex; flex-direction: column; gap: 1.2rem; text-align: left; }
.form-row { display: flex; gap: 1rem; }
.half { flex: 1; }

.login-btn { margin-top: 1rem; font-size: 1.2rem; }
.login-btn:disabled { opacity: 0.7; cursor: not-allowed; }

.register-link { margin-top: 1rem; text-align: center; color: #4b5563; font-size: 0.95rem; }
.register-link a { color: #ca8a04; text-decoration: none; font-weight: bold; }
.register-link a:hover { text-decoration: underline; }

@media (max-width: 600px) {
  .form-row { flex-direction: column; gap: 1.2rem; }
  .login-card { padding: 2rem 1.5rem; }
}
</style>