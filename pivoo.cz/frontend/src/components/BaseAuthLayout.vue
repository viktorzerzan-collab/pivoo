<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="logo-container">
        <slot name="icon"></slot>
        <h1 class="logo-text">{{ title }}</h1>
      </div>
      <p class="auth-subtitle">{{ subtitle }}</p>

      <form @submit.prevent="$emit('submit')" class="auth-form">
        <slot></slot>
      </form>

      <div class="auth-footer-link" v-if="$slots.footer">
        <slot name="footer"></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    required: true
  }
})

defineEmits(['submit'])
</script>

<style scoped>
.auth-wrapper {
  width: 100%;
  min-height: 100vh;
  display: flex;
  /* flex-start a margin: auto zvládá perfektně centrování i na mobilech s dlouhým obsahem */
  align-items: flex-start;
  justify-content: center;
  background-color: var(--bg-app);
  padding: 4rem 1rem;
  transition: background-color 0.3s ease;
}

.auth-card {
  margin: auto;
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

/* Ošetření prvků dodaných ze slotu */
.auth-footer-link :deep(a) {
  color: var(--primary-hover);
  text-decoration: none;
  font-weight: 700;
  transition: color 0.2s;
}

.auth-footer-link :deep(a:hover) {
  color: var(--primary);
  text-decoration: underline;
}

@media (max-width: 600px) {
  .auth-wrapper { padding: 2rem 1rem; }
  .auth-card { padding: 2rem 1.5rem; }
}
</style>