<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../../api'

const router = useRouter()
const user = computed(() => {
  try {
    return JSON.parse(localStorage.getItem('admin_user') || '{}')
  } catch {
    return {}
  }
})

async function logout() {
  try {
    await api('/api/admin/logout', { method: 'POST' })
  } catch {
    // The local session is cleared either way.
  }
  localStorage.removeItem('admin_token')
  localStorage.removeItem('admin_user')
  router.push('/admin/login')
}
</script>

<template>
  <div class="admin-shell">
    <aside class="admin-side">
      <div>
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="sq-25 bg-danger d-inline-block"></span>
          <span class="type-label-sm text-uppercase ls-wider">FIVE STAR HOUSE</span>
        </div>
        <p class="type-body-sm text-body-secondary mb-0">{{ user.name || 'Studio Admin' }}</p>
      </div>
      <nav class="admin-nav">
        <RouterLink to="/admin">Dashboard</RouterLink>
        <RouterLink to="/admin/home-images">Home images</RouterLink>
        <RouterLink to="/admin/services">Services</RouterLink>
        <RouterLink to="/admin/bookings">Bookings</RouterLink>
        <RouterLink to="/admin/payments">Payments</RouterLink>
        <RouterLink to="/admin/customers">Customers</RouterLink>
        <RouterLink to="/admin/settings">Site settings</RouterLink>
        <RouterLink to="/admin/booking-images">Booking images</RouterLink>
      </nav>
      <div class="mt-auto d-flex flex-column gap-2">
        <RouterLink class="type-label-sm text-uppercase ls-wider text-body-secondary" to="/">View site</RouterLink>
        <button class="btn btn-secondary btn-quote" type="button" @click="logout">Sign out</button>
      </div>
    </aside>
    <main class="admin-main">
      <RouterView />
    </main>
  </div>
</template>
