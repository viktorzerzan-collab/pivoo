<template>
  <div class="login-wrapper">
    <transition name="toast-fade">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">
        {{ toast.message }}
      </div>
    </transition>

    <div class="login-card">
      <div class="logo-container">
        <span class="beer-icon">🍻</span>
        <h1 class="logo-text">Pivoo.cz</h1>
      </div>
      <p class="subtitle">Tvůj osobní pivní deníček</p>

      <form @submit.prevent="handleLogin" class="login-form">
        <BaseInput 
          v-model="username" 
          label="Přihlašovací jméno" 
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

        <BaseButton type="submit" variant="submit" class="login-btn" :disabled="isLoading">
          {{ isLoading ? 'Přihlašuji...' : 'Vstoupit do hospody' }}
        </BaseButton>

        <div class="register-link">
          Ještě nemáš účet? <router-link to="/register">Zaregistruj se</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'
import BaseInput from '../components/BaseInput.vue'
import BaseButton from '../components/BaseButton.vue'

const router = useRouter()
const authStore = useAuthStore()

const username = ref('')
const password = ref('')
const isLoading = ref(false)

// Systém notifikací pro chybové hlášky
const toast = ref({ show: false, message: '', type: 'toast-success' })
const showToast = (message, type = 'toast-error') => { 
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 4000) 
}

const handleLogin = async () => {
  // Základní kontrola
  if (!username.value || !password.value) {
    showToast("Vyplňte jméno i heslo.");
    return;
  }

  isLoading.value = true;

  try {
    // Volání reálného PHP backendu
    const response = await fetch('https://www.pivoo.cz/backend/api/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        username: username.value, 
        password: password.value 
      })
    });
    
    const result = await response.json();
    
    if (response.ok && result.status === 'success') {
      // Úspěšné přihlášení - uložíme reálná data uživatele z databáze do Pinie
      authStore.login(result.user);
      router.push('/dashboard');
    } else {
      // Špatné heslo nebo neexistující uživatel - zobrazíme hlášku z PHP
      showToast(result.message);
    }
  } catch (error) {
    showToast("Chyba při komunikaci se serverem.");
  } finally {
    isLoading.value = false;
  }
}
</script>

<style scoped>
/* Styly pro toast notifikace (převzato z RegisterView) */
.toast-notification { position: fixed; top: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: 8px; color: white; font-weight: bold; z-index: 9999; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2); }
.toast-success { background-color: #10b981; }
.toast-error { background-color: #ef4444; }
.toast-fade-enter-active, .toast-fade-leave-active { transition: all 0.3s ease; }
.toast-fade-enter-from, .toast-fade-leave-to { opacity: 0; transform: translateY(-20px); }

.login-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f3f4f6;
  padding: 1rem;
}

.login-card {
  background: white;
  padding: 3rem 2rem;
  border-radius: 12px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
  text-align: center;
  border: 1px solid #e5e7eb;
}

.logo-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.beer-icon {
  font-size: 4rem;
  line-height: 1;
}

.logo-text {
  font-size: 2.5rem;
  font-weight: bold;
  color: #ca8a04;
  margin: 0;
}

.subtitle {
  color: #6b7280;
  font-size: 1.1rem;
  margin-bottom: 2rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  text-align: left;
}

.login-btn {
  margin-top: 0.5rem;
  font-size: 1.2rem;
}

/* Vzhled zablokovaného tlačítka při načítání */
.login-btn:disabled { 
  opacity: 0.7; 
  cursor: not-allowed; 
}

.register-link {
  margin-top: 1rem;
  text-align: center;
  color: #4b5563;
  font-size: 0.95rem;
}

.register-link a {
  color: #ca8a04;
  text-decoration: none;
  font-weight: bold;
}

.register-link a:hover {
  text-decoration: underline;
}

@media (max-width: 600px) {
  .login-card { padding: 2rem 1.5rem; }
}
</style>