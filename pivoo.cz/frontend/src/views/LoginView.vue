<template>
  <div class="login-wrapper">
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

        <BaseButton type="submit" variant="submit" class="login-btn">
          Vstoupit do hospody
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

// Připojíme náš nový Pinia sklad a komponenty
import { useAuthStore } from '../stores/auth'
import BaseInput from '../components/BaseInput.vue'
import BaseButton from '../components/BaseButton.vue'

const router = useRouter()
const authStore = useAuthStore()

const username = ref('')
const password = ref('')

const handleLogin = () => {
  // POZNÁMKA PRO VÁS: Toto je zatím "hloupé" přihlášení z naší prototypové fáze.
  // Jakmile tohle odsouhlasíte, ve Fázi 2 sem přidáme skutečné ověření přes PHP a databázi.
  // Nyní pouze uložíme jméno do Pinie a pustíme uživatele dál.
  
  if (username.value) {
    authStore.login({ id: 1, name: username.value })
    router.push('/dashboard')
  }
}
</script>

<style scoped>
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

/* NOVÉ STYLY PRO ODKAZ NA REGISTRACI */
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
</style>