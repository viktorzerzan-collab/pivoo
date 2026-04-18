<template>
  <div class="map-container-wrapper">
    <div id="map" ref="mapElement"></div>
    
    <button class="btn-locate" @click="locateMe" :disabled="isLocating" title="Moje poloha">
      <NavigationIcon :size="18" :class="{ 'spinning': isLocating }" />
      {{ isLocating ? 'Hledám...' : 'Kde jsem?' }}
    </button>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, onUnmounted } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { NavigationIcon } from 'lucide-vue-next' // PŘIDÁNO

import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

const props = defineProps({
  items: Array,
  type: { type: String, default: 'brewery' }
})

const emit = defineEmits(['showDetail'])
const mapElement = ref(null)
let map = null
let markersGroup = null
let userMarker = null // PŘIDÁNO: Marker pro uživatele
const isLocating = ref(false) // PŘIDÁNO: Stav hledání polohy

const escapeHTML = (str) => {
  if (!str) return '';
  return str.toString().replace(/[&<>'"]/g, 
    tag => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      "'": '&#39;',
      '"': '&quot;'
    }[tag] || tag)
  );
}

const initMap = () => {
  map = L.map('map').setView([49.8175, 15.4730], 7)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  markersGroup = L.layerGroup().addTo(map)
  updateMarkers()
}

const updateMarkers = () => {
  if (!markersGroup) return
  markersGroup.clearLayers()

  const customIcon = L.icon({
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34]
  })

  props.items.forEach(item => {
    if (item.lat && item.lng) {
      const marker = L.marker([item.lat, item.lng], { icon: customIcon })
      
      const popupContent = `
        <div class="map-popup">
          <strong>${escapeHTML(item.name)}</strong><br>
          ${escapeHTML(item.city || '')}<br>
          <button class="popup-btn" id="btn-${item.id}">Zobrazit detail</button>
        </div>
      `
      marker.bindPopup(popupContent)
      
      marker.on('popupopen', () => {
        document.getElementById(`btn-${item.id}`).addEventListener('click', () => {
          emit('showDetail', item)
        })
      })

      markersGroup.addLayer(marker)
    }
  })
}

// PŘIDÁNO: Funkce pro lokalizaci uživatele
const locateMe = () => {
  if (!navigator.geolocation) {
    alert("Váš prohlížeč nepodporuje geolokaci.")
    return
  }

  isLocating.value = true
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      isLocating.value = false
      const lat = pos.coords.latitude
      const lng = pos.coords.longitude
      
      if (map) {
        map.setView([lat, lng], 11) // Vycentrování s vhodným přiblížením
        
        if (userMarker) map.removeLayer(userMarker)
        
        // Využití CircleMarkeru pro čistý moderní vzhled "modré tečky"
        userMarker = L.circleMarker([lat, lng], {
          radius: 8,
          fillColor: "#3b82f6",
          color: "#ffffff",
          weight: 3,
          opacity: 1,
          fillOpacity: 1
        }).addTo(map).bindPopup("<b>Nacházíte se zde</b>").openPopup()
      }
    },
    (err) => {
      isLocating.value = false
      console.error("Geolokace selhala:", err)
      alert("Nepodařilo se zjistit vaši polohu. Zkontrolujte oprávnění v prohlížeči.")
    }
  )
}

watch(() => props.items, updateMarkers, { deep: true })

onMounted(() => {
  setTimeout(() => {
    initMap()
  }, 100)
})

onUnmounted(() => {
  if (map) map.remove()
})
</script>

<style scoped>
.map-container-wrapper {
  height: 600px;
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  position: relative;
}

#map {
  height: 100%;
  width: 100%;
  z-index: 1;
}

/* PŘIDÁNO: Styl tlačítka pro geolokaci */
.btn-locate {
  position: absolute;
  top: 15px;
  right: 15px;
  z-index: 400; /* Mapové prvky mají v Leafletu z-index kolem 400 */
  background-color: var(--bg-panel);
  color: var(--text-main);
  border: 2px solid var(--border);
  border-radius: 8px;
  padding: 0.6rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  box-shadow: var(--shadow-md);
  transition: all 0.2s ease;
}

.btn-locate:hover:not(:disabled) {
  border-color: var(--primary);
  color: var(--primary-hover);
}

.btn-locate:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.spinning {
  animation: spin 1.5s linear infinite;
}

@keyframes spin { 100% { transform: rotate(360deg); } }

:deep(.leaflet-popup-content-wrapper) {
  background: var(--bg-panel);
  color: var(--text-main);
  border-radius: 8px;
}

:deep(.map-popup) {
  text-align: center;
  font-family: 'Inter', sans-serif;
}

:deep(.popup-btn) {
  margin-top: 8px;
  background: var(--primary);
  color: #1e293b;
  border: none;
  padding: 5px 12px;
  border-radius: 4px;
  font-weight: 700;
  cursor: pointer;
  font-size: 0.8rem;
}
</style>