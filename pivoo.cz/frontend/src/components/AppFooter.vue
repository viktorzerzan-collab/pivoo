<template>
  <footer class="app-footer">
    <div class="footer-content">
      <div class="copyright">
        &copy; 2026 <strong>Pivoo.cz</strong>. {{ $t('footer.rights_reserved') }}
      </div>
      <div class="app-version">
        <span>{{ $t('footer.version') }} {{ appVersion }}</span>
      </div>
    </div>
  </footer>
</template>

<script setup>
import packageJson from '../../package.json'
const appVersion = packageJson.version
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
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.5rem;
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
</style>