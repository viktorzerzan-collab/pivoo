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
// Importujeme verzi přímo z package.json projektu
import packageJson from '../../package.json'

const appVersion = packageJson.version
// Automaticky vygenerujeme dnešní datum jako Build datum
const buildDate = new Date().toISOString().split('T')[0]
</script>

<style scoped>
.app-footer {
  background-color: #f8fafc;
  border-top: 1px solid #e2e8f0;
  padding: 1.5rem 2rem;
  color: #64748b;
  font-size: 0.9rem;
}

.footer-content {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.copyright strong {
  color: #334155;
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
  background-color: #cbd5e1;
  border-radius: 50%;
}

@media (max-width: 600px) {
  .footer-content {
    flex-direction: column;
    gap: 0.75rem;
    text-align: center;
  }
}
</style>