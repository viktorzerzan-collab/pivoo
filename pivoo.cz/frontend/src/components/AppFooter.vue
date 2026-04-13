<template>
  <footer class="app-footer">
    <div class="footer-content">
      <div class="copyright">
        &copy; 2026 <strong>Pivoo.cz</strong>. Všechna práva rezervována.
      </div>
      <div class="app-version">
        <span>Verze {{ appVersion }}</span>
        <span class="version-dot"></span>
        <span>Build: {{ buildDate }}</span>
      </div>
    </div>
  </footer>
</template>

<script setup>
import packageJson from '../../package.json'
const appVersion = packageJson.version
const buildDate = new Date().toISOString().split('T')[0]
</script>

<style scoped>
.app-footer {
  background-color: var(--bg-panel);
  border-top: 1px solid var(--border);
  padding: 1.5rem 2rem;
  color: var(--text-muted);
  font-size: 0.9rem;
  transition: background-color 0.5s ease, border-color 0.5s ease, color 0.5s ease;
}

.footer-content {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.copyright strong {
  color: var(--text-main);
  transition: color 0.5s ease;
}

.app-version {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-family: 'JetBrains Mono', 'Courier New', monospace;
  font-size: 0.8rem;
}

.version-dot {
  width: 4px;
  height: 4px;
  background-color: var(--border);
  border-radius: 50%;
  transition: background-color 0.5s ease;
}

@media (max-width: 600px) {
  .footer-content {
    flex-direction: column;
    gap: 0.75rem;
    text-align: center;
  }
}
</style>