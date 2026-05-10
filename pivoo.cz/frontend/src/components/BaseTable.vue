<template>
  <div class="table-wrapper">
    <table class="base-table">
      <slot name="thead"></slot>
      <tbody>
        <slot name="tbody"></slot>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.table-wrapper { overflow-x: auto; flex: 1; margin-bottom: 1.5rem; }
.base-table { width: 100%; border-collapse: collapse; }

:deep(th) { 
  text-align: left; padding: 1rem 0.75rem; border-bottom: 2px solid var(--border); 
  color: var(--text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; 
  letter-spacing: 0.05em; background: transparent;
}

:deep(td) { padding: 1.25rem 0.75rem; border-bottom: 1px solid var(--border); vertical-align: middle; color: var(--text-main); }
:deep(tr:hover td) { background-color: var(--card-hover-bg); }

@media (max-width: 768px) {
  :deep(thead) { display: none; }
  .base-table, :deep(tbody), :deep(tr), :deep(td) { display: block; width: 100%; }
  
  :deep(tr) { margin-bottom: 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); background: var(--bg-app); padding: 1rem; }
  :deep(td) { text-align: left; padding: 0.5rem 0; border: none; display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
  :deep(td::before) { content: attr(data-label); font-weight: 700; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; flex-shrink: 0; }
}
</style>