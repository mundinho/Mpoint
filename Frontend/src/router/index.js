import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'game'
  },
  {
    path: '/admin',
    name: 'admin'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router