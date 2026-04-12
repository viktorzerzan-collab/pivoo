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

// Globální háček před každou změnou stránky
router.beforeEach((to, from) => {
  // 1. DYNAMICKÁ ZMĚNA TITULKU STRÁNKY
  const titleMap = {
    'dashboard': 'Nástěnka',
    'beers': 'Katalog piv',
    'breweries': 'Pivovary',
    'locations': 'Podniky',
    'profile': 'Můj profil',
    'admin': 'Administrace',
    'login': 'Přihlášení',
    'register': 'Registrace'
  }

  // Nastavení titulku: buď z mapy výše, nebo základní název
  const pageTitle = titleMap[to.name] || 'Tvůj pivní deníček'
  document.title = `Pivoo.cz | ${pageTitle}`


  // 2. BEZPEČNOSTNÍ LOGIKA (AUTH A ROLE)
  const authStore = useAuthStore()
  const isAuthenticated = !!authStore.token // Kontrolujeme přítomnost tokenu
  const isAdmin = authStore.user?.role === 'admin'

  // Pokud stránka vyžaduje přihlášení a uživatel není přihlášen, šup na login
  if (to.meta.requiresAuth && !isAuthenticated) {
    return { path: '/' }
  } 
  // Pokud stránka vyžaduje admina a uživatel jím není, šup na dashboard
  else if (to.meta.requiresAdmin && !isAdmin) {
    return { path: '/dashboard' }
  } 
  // Pokud uživatel je přihlášen a leze na login/registraci, hodíme ho na dashboard
  else if ((to.name === 'login' || to.name === 'register') && isAuthenticated) {
    return { path: '/dashboard' }
  } 
  
  return true
})

export default router