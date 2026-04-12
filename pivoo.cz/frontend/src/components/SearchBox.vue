<template>
  <div class="search-box">
    <input 
      type="text" 
      :value="searchQuery"
      @input="$emit('update:searchQuery', $event.target.value)"
      placeholder="🔍 Hledat pivo, pivovar nebo styl..." 
      class="search-input"
    >
    <select 
      :value="sortBy"
      @change="$emit('update:sortBy', $event.target.value)"
      class="sort-select"
    >
      <option value="name">🔤 Od A do Z</option>
      <option value="rating">⭐ Nejlépe hodnocené</option>
      <option value="abv">💪 Nejsilnější (Alkohol)</option>
      <option value="epm">🌾 Nejvíc stupňů (EPM)</option>
    </select>
  </div>
</template>

<script setup>
defineProps({ searchQuery: String, sortBy: String })
defineEmits(['update:searchQuery', 'update:sortBy'])
</script>

<style scoped>
.search-box { flex-grow: 1; max-width: 600px; margin-left: 2rem; display: flex; gap: 1rem; }
.search-input { flex-grow: 1; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 99px; font-size: 1rem; box-sizing: border-box; transition: border-color 0.2s, box-shadow 0.2s; outline: none; }
.sort-select { padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 99px; font-size: 1rem; box-sizing: border-box; transition: border-color 0.2s, box-shadow 0.2s; outline: none; background-color: white; cursor: pointer; color: #4b5563; font-weight: 500;}
.search-input:focus, .sort-select:focus { border-color: #eab308; box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.2); }
@media (max-width: 600px) {
  .search-box { flex-direction: column; margin-left: 0; max-width: 100%; gap: 0.5rem;}
}
</style>