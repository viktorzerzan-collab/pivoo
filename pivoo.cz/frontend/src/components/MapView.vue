<template>
  <div class="map-container-wrapper">
    <div id="map" ref="mapElement"></div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, onUnmounted } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Oprava výchozích ikon Leafletu v produkčním buildu
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

const props = defineProps({
  items: Array, // Seznam pivovarů nebo podniků
  type: { type: String, default: 'brewery' }
})

const emit = defineEmits(['showDetail'])
const mapElement = ref(null)
let map = null
let markersGroup = null

const initMap = () => {
  // Inicializace mapy (střed ČR)
  map = L.map('map').setView([49.8175, 15.4730], 7)

  // Podkladová mapa (OpenStreetMap)
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
      
      // Bublina po kliknutí
      const popupContent = `
        <div class="map-popup">
          <strong>${item.name}</strong><br>
          ${item.city || ''}<br>
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

// Sledování změn v datech (např. při filtrování)
watch(() => props.items, updateMarkers, { deep: true })

onMounted(() => {
  setTimeout(() => {
    initMap()
  }, 100) // Drobný odklad pro správné vykreslení kontejneru
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
}

#map {
  height: 100%;
  width: 100%;
  z-index: 1;
}

/* Stylování bubliny uvnitř mapy */
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