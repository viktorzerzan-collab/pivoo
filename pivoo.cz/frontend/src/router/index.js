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
      path: '/admin',
      name: 'admin',
      component: () => import('../views/AdminView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    }
  ]
})

// Moderní ochrana rout (Vue Router 4) - bez použití zastaralého next()
router.beforeEach((to, from) => {
  const authStore = useAuthStore()
  const isAuthenticated = !!authStore.user
  const isAdmin = authStore.user?.role === 'admin'

  // Pokud stránka vyžaduje přihlášení a uživatel není přihlášen, vrať ho na login
  if (to.meta.requiresAuth && !isAuthenticated) {
    return { path: '/' }
  } 
  // Pokud stránka vyžaduje admina a uživatel není admin, vrať ho na dashboard
  else if (to.meta.requiresAdmin && !isAdmin) {
    return { path: '/dashboard' }
  } 
  // Pokud je uživatel přihlášený a snaží se jít na login/registraci, pošli ho na nástěnku
  else if ((to.name === 'login' || to.name === 'register') && isAuthenticated) {
    return { path: '/dashboard' }
  } 
  // Ve všech ostatních případech nech uživatele projít
  
  return true
})

export default router