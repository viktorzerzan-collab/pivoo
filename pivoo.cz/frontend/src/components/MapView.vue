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
import { NavigationIcon } from 'lucide-vue-next'

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
let userMarker = null
const isLocating = ref(false)

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
  if (!mapElement.value) return
  map = L.map(mapElement.value).setView([49.8175, 15.4730], 7)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  markersGroup = L.layerGroup().addTo(map)
  updateMarkers()
}

const updateMarkers = () => {
  if (!markersGroup || !map) return
  markersGroup.clearLayers()

  const customIcon = L.icon({
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34]
  })

  const bounds = L.latLngBounds()
  let hasValidCoords = false

  props.items.forEach(item => {
    if (item.lat && item.lng) {
      const marker = L.marker([item.lat, item.lng], { icon: customIcon })
      
      const popupContent = `
        <div class="map-popup">
          <strong class="popup-title">${escapeHTML(item.name)}</strong><br>
          <span class="popup-city">${escapeHTML(item.city || '')}</span><br>
          <button class="popup-btn" id="btn-${item.id}">Zobrazit detail</button>
        </div>
      `
      marker.bindPopup(popupContent)
      
      marker.on('popupopen', () => {
        const btn = document.getElementById(`btn-${item.id}`)
        if (btn) {
          btn.onclick = () => emit('showDetail', item)
        }
      })

      markersGroup.addLayer(marker)
      bounds.extend([item.lat, item.lng])
      hasValidCoords = true
    }
  })

  // Automatický zoom podle špendlíků
  if (hasValidCoords) {
    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 })
  }
}

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
        map.setView([lat, lng], 13)
        if (userMarker) map.removeLayer(userMarker)
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
      alert("Nepodařilo se zjistit vaši polohu.")
    }
  )
}

watch(() => props.items, () => {
  updateMarkers()
}, { deep: true })

onMounted(() => {
  setTimeout(initMap, 100)
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

#map { height: 100%; width: 100%; z-index: 1; }

.btn-locate {
  position: absolute;
  top: 15px;
  right: 15px;
  z-index: 400;
  background-color: var(--bg-panel);
  color: var(--text-main);
  border: 2px solid var(--border);
  border-radius: 8px;
  padding: 0.6rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: var(--shadow-md);
}

.btn-locate:hover { border-color: var(--primary); }
.spinning { animation: spin 1.5s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }

:deep(.leaflet-popup-content-wrapper) {
  background: var(--bg-panel);
  color: var(--text-main);
  border-radius: 8px;
  padding: 5px;
}

:deep(.map-popup) {
  text-align: center;
  font-family: 'Inter', sans-serif;
  min-width: 120px;
}

.popup-title { font-size: 1rem; display: block; margin-bottom: 2px; }
.popup-city { color: var(--text-muted); font-size: 0.85rem; }

:deep(.popup-btn) {
  margin-top: 10px;
  background: var(--primary);
  color: #1e293b;
  border: none;
  padding: 6px 14px;
  border-radius: 6px;
  font-weight: 700;
  cursor: pointer;
  font-size: 0.8rem;
  width: 100%;
}
</style>