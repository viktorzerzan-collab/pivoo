<template>
  <div class="distance-display">
    <template v-if="lat && lng">
      <button v-if="!userLoc" class="distance-btn" @click.stop="getUserLocation(true)">
        <NavigationIcon :size="14" /> Zjistit vzdálenost
      </button>
      <span v-else class="distance-text">
        <NavigationIcon :size="14" /> {{ calculateDistance(userLoc.lat, userLoc.lng, lat, lng).toFixed(1) }} km od vás
      </span>
    </template>
    <template v-else>
      <span class="distance-text text-muted">
        <NavigationIcon :size="14" /> Poloha neznámá
      </span>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { NavigationIcon } from 'lucide-vue-next'

defineProps({
  lat: [Number, String],
  lng: [Number, String]
})

// Sdílený stav pro polohu uživatele
const userLoc = window.__pivooUserLoc || (window.__pivooUserLoc = ref(null))

// Parametr showErrors určuje, zda chceme uživatele upozornit alertem při chybě (např. při kliknutí to chceme, při automatickém načtení ne)
const getUserLocation = (showErrors = false) => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        userLoc.value = { lat: pos.coords.latitude, lng: pos.coords.longitude }
      },
      (err) => {
        console.error(err)
        if (showErrors) alert("Nepodařilo se zjistit vaši polohu. Zkontrolujte oprávnění v prohlížeči.")
      }
    )
  } else {
    if (showErrors) alert("Váš prohlížeč nepodporuje zjišťování polohy.")
  }
}

// Kontrola při načtení komponenty, zda už náhodou nemáme oprávnění přiděleno z minula
onMounted(() => {
  if (!userLoc.value && navigator.permissions) {
    navigator.permissions.query({ name: 'geolocation' }).then((result) => {
      if (result.state === 'granted') {
        // Uživatel už polohu povolil v minulosti, zjistíme ji automaticky a potichu
        getUserLocation(false);
      }
    });
  }
})

const calculateDistance = (lat1, lon1, lat2, lon2) => {
  const R = 6371; // Poloměr Země v km
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon/2) * Math.sin(dLon/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}
</script>

<style scoped>
.distance-display {
  display: inline-flex;
  align-items: center;
}

.distance-btn {
  background: none;
  border: none;
  color: var(--blue);
  font-size: 0.85rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 0;
  cursor: pointer;
  transition: color 0.2s;
  box-shadow: none;
}

.distance-btn:hover {
  color: var(--blue-hover);
  text-decoration: underline;
}

.distance-text {
  color: var(--blue);
  font-size: 0.85rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 4px;
}

.text-muted {
  color: var(--text-muted) !important;
  font-style: italic;
  font-weight: 600;
}
</style>