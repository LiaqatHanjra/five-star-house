<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../../api'
import { formatMoney } from '../../format'

const bookings = ref([])

async function load() {
  const data = await api('/api/admin/bookings')
  bookings.value = data.bookings
}

async function setStatus(booking, status) {
  const data = await api(`/api/admin/bookings/${booking.id}/status`, {
    method: 'POST',
    body: { status },
  })
  booking.status = data.booking.status
}

onMounted(load)
</script>

<template>
  <div class="d-flex flex-column gap-3">
    <div>
      <p class="type-overline text-uppercase ls-20 text-danger">STUDIO DESK</p>
      <h1 class="type-headline-md text-uppercase">BOOKINGS</h1>
    </div>
    <p v-if="!bookings.length" class="type-body-md text-body-secondary">No bookings yet.</p>
    <article v-for="booking in bookings" :key="booking.id" class="admin-card d-flex flex-column gap-2">
      <div class="d-flex flex-wrap justify-content-between gap-2">
        <div>
          <h2 class="type-headline-sm text-uppercase mb-1">{{ booking.full_name }}</h2>
          <p class="type-body-sm text-body-secondary mb-0">{{ booking.email }} <span v-if="booking.phone">· {{ booking.phone }}</span></p>
        </div>
        <span class="status-pill">{{ booking.status }} · {{ booking.payment_status || 'unpaid' }}</span>
      </div>
      <p class="type-body-md mb-0">{{ booking.project_type }} <span v-if="booking.event_title">· {{ booking.event_title }}</span></p>
      <p class="type-body-md text-body-secondary mb-0">{{ booking.booking_date }} · {{ booking.start_time }}–{{ booking.end_time }} · {{ booking.hours }} hours · {{ formatMoney(booking.amount) }}</p>
      <p v-if="booking.message" class="type-body-sm text-body-tertiary mb-0">{{ booking.message }}</p>
      <div class="d-flex gap-2">
        <button class="btn btn-secondary btn-quote" type="button" @click="setStatus(booking, 'cancelled')">Cancel</button>
      </div>
    </article>
  </div>
</template>
