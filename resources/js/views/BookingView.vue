<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../api'
import { formatMoney } from '../format'

const options = ref([])

onMounted(async () => {
  const data = await api('/api/studio')
  const settings = data.settings || {}
  const currency = settings.currency || 'CAD'
  options.value = [
    {
      to: '/book/studio-time',
      kicker: 'STUDIO TIME',
      title: 'Book the studio',
      copy: 'Hold the room for a shoot, a session, or any open hours. Minimum 2 hours.',
      rate: settings.studio_hourly_rate,
      currency,
    },
    {
      to: '/book/studio-event',
      kicker: 'AN EVENT',
      title: 'Book the studio for an event',
      copy: 'A launch, a listening party, a dinner, or a private screening in the house.',
      rate: settings.event_hourly_rate,
      currency,
    },
    {
      to: '/book/owner-event',
      kicker: 'THE OWNER',
      title: 'Book the owner for an event',
      copy: 'The owner directs, shoots, or hosts. The clock starts at two hours.',
      rate: settings.owner_hourly_rate,
      currency,
    },
  ]
})
</script>

<template>
  <div class="site-wrap py-xl py-lg-3xl">
    <div class="d-flex flex-column gap-2 mb-2xl max-w-3xl">
      <div class="d-flex align-items-center gap-1">
        <span class="sq-2 bg-danger d-inline-block"></span>
        <span class="type-overline text-uppercase ls-25 text-danger">BOOK THE HOUSE</span>
      </div>
      <h1 class="type-headline-mobile type-md-headline-lg text-uppercase">CHOOSE A BOOKING</h1>
      <p class="type-body-lg text-body-secondary max-w-xl">
        Book the studio for a block of time, book it for an event, or book the owner. Every booking starts at two hours. Taken hours show on the calendar.
      </p>
    </div>
    <div class="row g-3">
      <div v-for="option in options" :key="option.to" class="col-md-4">
        <RouterLink class="choice-card" :to="option.to">
          <span class="type-overline text-uppercase text-danger">{{ option.kicker }}</span>
          <h2 class="type-headline-sm text-uppercase">{{ option.title }}</h2>
          <p class="type-body-md text-body-secondary mb-0">{{ option.copy }}</p>
          <span class="type-label-sm text-uppercase text-primary">FROM {{ formatMoney(option.rate, option.currency) }} / HOUR</span>
        </RouterLink>
      </div>
    </div>
    <p class="type-body-sm text-body-tertiary mt-4 mb-0">
      Looking for a listed service? Open it from the services page and use Book this service.
    </p>
  </div>
</template>
