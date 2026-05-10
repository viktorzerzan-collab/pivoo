<template>
  <div class="map-container-wrapper">
    <label v-if="label" class="form-label d-block mb-2">{{ label }}</label>
    <div ref="mapContainerRef" class="admin-map-element"></div>
    <div class="coords-display">
      GPS: {{ lat || '???' }}, {{ lng || '???' }}
      <slot name="append-coords"></slot>
    </div>
  </div>
</template>

<script setup>
import { ref, shallowRef, watch, nextTick, onBeforeUnmount } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'

const props = defineProps({
  lat: [Number, String],
  lng: [Number, String],
  label: String,
  show: Boolean
})

const emit = defineEmits(['update:lat', 'update:lng', 'location-changed'])

// Oprava 1: Používáme shallowRef pro Leaflet objekty, aby do nich Vue zbytečně nezasahovalo
const map = shallowRef(null)
const marker = shallowRef(null)
const mapContainerRef = ref(null)
let resizeObserver = null

const destroyMap = () => {
  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }
  if (map.value) {
    map.value.remove()
    map.value = null
    marker.value = null
  }
}

const initMap = () => {
  if (!mapContainerRef.value) return;

  destroyMap()

  const initialLat = parseFloat(props.lat) || 49.8175
  const initialLng = parseFloat(props.lng) || 15.4730
  const zoom = props.lat ? 16 : 6

  map.value = L.map(mapContainerRef.value).setView([initialLat, initialLng], zoom)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(map.value)

  const customIcon = L.icon({
    iconUrl: markerIconUrl,
    shadowUrl: markerShadowUrl,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34]
  })

  marker.value = L.marker([initialLat, initialLng], { icon: customIcon, draggable: true }).addTo(map.value)

  const updateCoordinates = (latlng) => {
    const newLat = latlng.lat.toFixed(8)
    const newLng = latlng.lng.toFixed(8)
    emit('update:lat', newLat)
    emit('update:lng', newLng)
    emit('location-changed', { lat: newLat, lng: newLng })
  }

  marker.value.on('dragend', (e) => updateCoordinates(e.target.getLatLng()))
  map.value.on('click', (e) => {
    marker.value.setLatLng(e.latlng)
    updateCoordinates(e.latlng)
  })

  // Oprava 2: Ultimátní fix pro překreslování mapy v modálních oknech
  resizeObserver = new ResizeObserver(() => {
    if (map.value) {
      map.value.invalidateSize()
    }
  })
  resizeObserver.observe(mapContainerRef.value)
}

// Oprava 3: immediate: true zaručí, že pokud se komponenta načte a show už je true, mapa se vykreslí
watch(() => props.show, (isVisible) => {
  if (isVisible) {
    nextTick(() => initMap())
  } else {
    destroyMap()
  }
}, { immediate: true })

watch([() => props.lat, () => props.lng], ([newLat, newLng]) => {
  if (props.show && map.value && marker.value) {
    const l = parseFloat(newLat)
    const lg = parseFloat(newLng)
    if (l && lg && (marker.value.getLatLng().lat !== l || marker.value.getLatLng().lng !== lg)) {
      marker.value.setLatLng([l, lg])
      map.value.setView([l, lg])
    }
  }
})

onBeforeUnmount(() => destroyMap())
</script>

<style scoped>
.map-container-wrapper { margin: 0.5rem 0; width: 100%; }
.form-label { font-size: 0.9rem; font-weight: 600; color: var(--text-main); }
.mb-2 { margin-bottom: 0.5rem; }
.d-block { display: block; }
.admin-map-element {
  height: 250px;
  width: 100%;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  z-index: 1;
}
.coords-display { font-family: monospace; font-size: 0.8rem; margin-top: 5px; color: var(--text-muted); display: flex; align-items: center; }
</style>