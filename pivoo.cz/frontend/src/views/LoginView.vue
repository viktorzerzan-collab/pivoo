<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="logo-container">
        <BeerIcon :size="56" color="var(--primary)" stroke-width="1.5" />
        <h1 class="logo-text">Pivoo.cz</h1>
      </div>
      <p class="auth-subtitle">Tvůj osobní pivní deníček</p>

      <div v-if="errorMessage" class="auth-error-banner">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleLogin" class="auth-form">
        <BaseInput 
          v-model="username" 
          label="Přihlašovací jméno nebo E-mail" 
          placeholder="Např. Karel" 
          required 
        />

        <BaseInput 
          v-model="password" 
          type="password" 
          label="Heslo" 
          placeholder="••••••••" 
          required 
        />

        <BaseButton type="submit" variant="primary" style="margin-top: 1rem; width: 100%;" :disabled="isLoading">
          <template #icon>
            <LogInIcon :size="18" />
          </template>
          {{ isLoading ? 'Ověřuji...' : 'Vstoupit do hospody' }}
        </BaseButton>

        <div class="auth-footer-link">
          Ještě nemáš účet? <router-link to="/register">Zaregistruj se</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { BeerIcon, LogInIcon } from 'lucide-vue-next'

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
    const response = await fetch('https://www.pivoo.cz/backend/api/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: username.value, password: password.value })
    })
    const result = await response.json()
    if (result.status === 'success') {
      authStore.login(result.user)
      router.push('/dashboard')
    } else {
      errorMessage.value = result.message || 'Přihlášení se nezdařilo.'
    }
  } catch (error) {
    errorMessage.value = 'Chyba při komunikaci se serverem.'
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
  background: var(--bg-panel);
  padding: 3rem 2.5rem;
  border-radius: 16px; /* Modernější zaoblení */
  box-shadow: var(--shadow-md);
  width: 100%;
  max-width: 420px;
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
  font-size: 2.25rem;
  font-weight: 800;
  color: var(--text-main);
  letter-spacing: -0.025em;
  margin: 0;
}

.auth-subtitle {
  color: var(--text-muted);
  font-size: 1rem;
  margin-bottom: 2rem;
  margin-top: 0.25rem;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
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
  margin-top: 1.5rem;
  text-align: center;
  color: var(--text-muted);
  font-size: 0.95rem;
}

.auth-footer-link a {
  color: var(--primary-hover);
  text-decoration: none;
  font-weight: 700;
  transition: color 0.2s;
}

.auth-footer-link a:hover {
  color: var(--primary);
  text-decoration: underline;
}
</style>