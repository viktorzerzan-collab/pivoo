import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'login',
      component: () => import('../views/LoginView.vue')
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterView.vue')
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { requiresAuth: true }
    },
    // PŘIDÁNO: Nová cesta pro statistiky
    {
      path: '/statistics',
      name: 'statistics',
      component: () => import('../views/StatisticsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/beers',
      name: 'beers',
      component: () => import('../views/BeersView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/breweries',
      name: 'breweries',
      component: () => import('../views/BreweriesView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/locations',
      name: 'locations',
      component: () => import('../views/LocationsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/profile', 
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/admin',
      name: 'admin',
      component: () => import('../views/AdminView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    }
  ]
})

router.beforeEach((to, from) => {
  const titleMap = {
    'dashboard': 'Nástěnka',
    'statistics': 'Moje statistiky', // Přidáno do mapy titulků
    'beers': 'Katalog piv',
    'breweries': 'Pivovary',
    'locations': 'Podniky',
    'profile': 'Můj profil',
    'admin': 'Administrace',
    'login': 'Přihlášení',
    'register': 'Registrace'
  }

  const pageTitle = titleMap[to.name] || 'Tvůj pivní deníček'
  document.title = `Pivoo.cz | ${pageTitle}`

  const authStore = useAuthStore()
  const isAuthenticated = !!authStore.token 
  const isAdmin = authStore.user?.role === 'admin'

  if (to.meta.requiresAuth && !isAuthenticated) {
    return { path: '/' }
  } 
  else if (to.meta.requiresAdmin && !isAdmin) {
    return { path: '/dashboard' }
  } 
  else if ((to.name === 'login' || to.name === 'register') && isAuthenticated) {
    return { path: '/dashboard' }
  } 
  
  return true
})

export default router