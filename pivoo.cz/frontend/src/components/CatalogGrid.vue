<template>
  <div class="catalog-grid">
    <slot></slot>
  </div>
</template>

<style scoped>
.catalog-grid {
  display: grid;
  /* Použito auto-fill pro korektní chování u malého počtu karet (nerozšíří se přes celou obrazovku) */
  grid-template-columns: repeat(auto-fill, minmax(max(300px, calc((100% - 1.5rem) / 2)), 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}
</style>