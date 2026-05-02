<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="logo-container">
        <BeerIcon :size="64" color="var(--primary)" stroke-width="1.5" />
        <h1 class="logo-text">Pivoo.cz</h1>
      </div>
      <p class="auth-subtitle">Tvůj osobní pivní deníček</p>

      <form @submit.prevent="handleLogin" class="auth-form">
        <BaseInput 
          v-model="username" 
          label="Uživatelské jméno / Email" 
          placeholder="Tvůj login" 
          required 
        />
        
        <BaseInput 
          v-model="password" 
          type="password" 
          label="Heslo" 
          placeholder="••••••••" 
          required 
        />

        <BaseButton 
          type="submit" 
          variant="primary" 
          style="margin-top: 1rem; width: 100%;" 
          :disabled="isLoading"
        >
          <template #icon><LogInIcon :size="18" /></template>
          {{ isLoading ? 'Přihlašuji...' : 'Vstoupit' }}
        </BaseButton>

        <div class="auth-footer-link">
          Nemáš účet? <router-link to="/register">Registruj se zde</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { LogInIcon, BeerIcon } from 'lucide-vue-next'
import { apiFetch } from '../api'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast' 
import BaseInput from '../components/BaseInput.vue'
import BaseButton from '../components/BaseButton.vue'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore() 

const username = ref('')
const password = ref('')
const isLoading = ref(false)

const handleLogin = async () => {
  isLoading.value = true
  
  try {
    const result = await apiFetch('/login.php', { 
      method: 'POST', 
      body: JSON.stringify({ 
        username: username.value, 
        password: password.value 
      }) 
    })
    
    if (result.status === 'success') {
      authStore.login(result.user, result.token)
      router.push('/dashboard')
    } else {
      toastStore.showToast(result.message || 'Chyba přihlášení.', 'toast-error')
    }
  } catch (error) {
    toastStore.showToast('Server není dostupný. Zkuste to později.', 'toast-error')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.auth-wrapper {
  width: 100%;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--bg-app);
  padding: 1rem;
  transition: background-color 0.3s ease;
}

.auth-card {
  background: var(--bg-panel);
  padding: 3.5rem 2.5rem;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-floating);
  width: 100%;
  max-width: 700px;
  text-align: center;
  border: 1px solid var(--border);
  transition: background-color 0.3s ease, border-color 0.3s ease;
}

.logo-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.logo-text {
  font-size: 2.5rem;
  font-weight: 800;
  color: var(--text-main);
  letter-spacing: -0.05em;
  margin: 0;
  transition: color 0.3s ease;
}

.auth-subtitle {
  color: var(--text-muted);
  font-size: 1.1rem;
  margin-bottom: 2.5rem;
  margin-top: 0.25rem;
  transition: color 0.3s ease;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  text-align: left;
}

.auth-footer-link {
  margin-top: 2rem;
  text-align: center;
  color: var(--text-muted);
  font-size: 0.95rem;
  transition: color 0.3s ease;
}

.auth-footer-link a {
  color: var(--primary-hover);
  text-decoration: none;
  font-weight: 700;
}

.auth-footer-link a:hover {
  text-decoration: underline;
}
</style>