<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../../api'

const stats = ref(null)

onMounted(async () => {
  stats.value = await api('/api/admin/dashboard')
})
</script>

<template>
  <div class="d-flex flex-column gap-4">
    <div>
      <p class="type-overline text-uppercase ls-20 text-danger">STUDIO DESK</p>
      <h1 class="type-headline-md text-uppercase">DASHBOARD</h1>
    </div>
    <div v-if="stats" class="row g-3">
      <div class="col-md-4 col-lg">
        <RouterLink class="admin-card d-block h-100" to="/admin/services">
          <span class="type-overline text-uppercase text-body-tertiary">SERVICES</span>
          <div class="stat-value">{{ stats.services }}</div>
        </RouterLink>
      </div>
      <div class="col-md-4 col-lg">
        <RouterLink class="admin-card d-block h-100" to="/admin/home-images">
          <span class="type-overline text-uppercase text-body-tertiary">HOME IMAGES</span>
          <div class="stat-value">{{ stats.home_images }}</div>
        </RouterLink>
      </div>
      <div class="col-md-4 col-lg">
        <RouterLink class="admin-card d-block h-100" to="/admin/booking-images">
          <span class="type-overline text-uppercase text-body-tertiary">BOOKING IMAGES</span>
          <div class="stat-value">{{ stats.booking_images }}</div>
        </RouterLink>
      </div>
      <div class="col-md-4 col-lg">
        <RouterLink class="admin-card d-block h-100" to="/admin/bookings">
          <span class="type-overline text-uppercase text-body-tertiary">BOOKINGS</span>
          <div class="stat-value">{{ stats.bookings }}</div>
        </RouterLink>
      </div>
      <div class="col-md-4 col-lg">
        <RouterLink class="admin-card d-block h-100" to="/admin/payments">
          <span class="type-overline text-uppercase text-body-tertiary">PAID</span>
          <div class="stat-value">{{ stats.payments }}</div>
        </RouterLink>
      </div>
      <div class="col-md-4 col-lg">
        <RouterLink class="admin-card d-block h-100" to="/admin/customers">
          <span class="type-overline text-uppercase text-body-tertiary">CUSTOMERS</span>
          <div class="stat-value">{{ stats.customers }}</div>
        </RouterLink>
      </div>
    </div>
  </div>
</template>
