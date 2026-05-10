<template>
  <div class="action-group">
    <slot></slot>
  </div>
</template>

<style scoped>
.action-group { 
  display: flex; 
  gap: 0.5rem; 
  justify-content: flex-end; 
  align-items: center;
}

/* Sjednocení všech akčních tlačítek v seznamech */
:deep(button) { 
  width: 32px !important; 
  height: 32px !important; 
  display: inline-flex !important; 
  align-items: center !important; 
  justify-content: center !important;
  border-radius: var(--radius-sm); 
  border: none; 
  cursor: pointer; 
  transition: all 0.2s ease; 
  color: white !important;
  padding: 0 !important;
  margin: 0;
  line-height: 0 !important;
  gap: 0 !important;
  flex-shrink: 0;
}

:deep(button:hover) { 
  transform: scale(1.1); 
  filter: brightness(1.1);
}

:deep(button svg) {
  display: block;
  flex-shrink: 0;
  margin: 0 !important;
}

@media (max-width: 768px) {
  .action-group { 
    width: 100%; 
    justify-content: flex-start; 
  }
  /* V seznamech (ne v admin tabulce) nechceme na mobilu vždy full-width tlačítka */
  .action-group:not(.full-mobile) :deep(button) {
    flex: 0 0 32px;
  }
}
</style>