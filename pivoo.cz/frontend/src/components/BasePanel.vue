<template>
  <div class="panel-card">
    <div class="panel-header" v-if="title">
      <h3>
        <component :is="icon" v-if="icon" class="panel-icon" :size="20" /> 
        {{ title }}
      </h3>
      <slot name="header-actions"></slot>
    </div>
    <div class="panel-body">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: false
  },
  icon: {
    type: [Object, Function],
    required: false
  }
})
</script>

<style scoped>
.panel-card { 
  background: var(--bg-panel); 
  border-radius: var(--radius-md); 
  border: 1px solid var(--border); 
  /* Zmenšený základní okraj z 1.5rem na 1.25rem */
  padding: 1.25rem; 
  transition: background-color 0.3s ease, border-color 0.3s ease; 
  height: 100%;
  display: flex;
  flex-direction: column;
}
.panel-header { 
  border-bottom: 1px solid var(--border); 
  /* Zmenšené mezery pod hlavičkou z 1rem a 1.5rem */
  padding-bottom: 0.75rem; 
  margin-bottom: 1rem; 
  transition: border-color 0.3s ease; 
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.panel-header h3 { 
  margin: 0; 
  display: flex; 
  align-items: center; 
  gap: 0.5rem; 
  /* Mírně kompaktnější velikost nadpisu */
  font-size: 1.15rem; 
  color: var(--text-main); 
  transition: color 0.3s ease; 
}
.panel-icon { color: var(--primary); }
.panel-body { flex-grow: 1; display: flex; flex-direction: column; }

@media (max-width: 600px) {
  /* Na telefonech jdeme na naprosté minimum */
  .panel-card { padding: 1rem; }
  .panel-header { 
    margin-bottom: 0.75rem; 
    padding-bottom: 0.5rem; 
  }
}
</style>