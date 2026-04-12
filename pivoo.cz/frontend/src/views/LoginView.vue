<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="logo-container">
        <BeerIcon :size="64" color="var(--primary)" stroke-width="1.5" />
        <h1 class="logo-text">Pivoo.cz</h1>
      </div>
      <p class="auth-subtitle">Tvůj osobní pivní deníček</p>

      <div v-if="errorMessage" class="auth-error-banner">
        {{ errorMessage }}
      </div>

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
import BaseInput from '../components/BaseInput.vue'
import BaseButton from '../components/BaseButton.vue'

const router = useRouter()
const authStore = useAuthStore()

const username = ref('')
const password = ref('')
const isLoading = ref(false)
const errorMessage = ref('')

const handleLogin = async () => {
  isLoading.value = true
  errorMessage.value = ''
  
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
      errorMessage.value = result.message || 'Chyba přihlášení.'
    }
  } catch (error) {
    errorMessage.value = 'Server není dostupný. Zkuste to později.'
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.auth-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--bg-app);
  padding: 1rem;
}

.auth-card {
  background: white;
  padding: 3.5rem 2.5rem;
  border-radius: 16px;
  box-shadow: var(--shadow-lg);
  width: 100%;
  max-width: 450px;
  text-align: center;
  border: 1px solid var(--border);
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
  color: #1e293b;
  letter-spacing: -0.05em;
  margin: 0;
}

.auth-subtitle {
  color: #64748b;
  font-size: 1.1rem;
  margin-bottom: 2.5rem;
  margin-top: 0.25rem;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  text-align: left;
}

.auth-error-banner {
  background-color: #fee2e2;
  color: #ef4444;
  padding: 0.75rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
  border: 1px solid #fca5a5;
  font-weight: 600;
}

.auth-footer-link {
  margin-top: 2rem;
  text-align: center;
  color: #64748b;
  font-size: 0.95rem;
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