import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

// OSTRANĚNO: import './assets/styles.css' 
// Veškeré globální styly a resety teď máme pod kontrolou v App.vue

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

app.mount('#app')