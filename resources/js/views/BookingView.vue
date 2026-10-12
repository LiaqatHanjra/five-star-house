<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '../api'
import { formatMoney } from '../format'
import { hourUnit, t } from '../i18n'

const settings = ref({})

const options = computed(() => {
  const currency = settings.value.currency || 'CAD'
  const studioMin = settings.value.studio_minimum_hours || 1
  const eventMin = settings.value.minimum_hours || 2
  return [
    {
      to: '/book/studio-time',
      kicker: t('book.studioKicker'),
      title: t('book.studioTitle'),
      copy: t('book.studioCopy', { n: studioMin, unit: hourUnit(studioMin) }),
      rate: settings.value.studio_hourly_rate,
      currency,
      fixedRate: true,
    },
    {
      to: '/book/studio-event',
      kicker: t('book.eventKicker'),
      title: t('book.eventTitle'),
      copy: t('book.eventCopy', { n: eventMin, unit: hourUnit(eventMin) }),
      rate: settings.value.event_hourly_rate,
      currency,
      fixedRate: false,
    },
    {
      to: '/book/owner-event',
      kicker: t('book.ownerKicker'),
      title: t('book.ownerTitle'),
      copy: t('book.ownerCopy', { n: eventMin, unit: hourUnit(eventMin) }),
      rate: settings.value.owner_hourly_rate,
      currency,
      fixedRate: false,
    },
  ]
})

onMounted(async () => {
  const data = await api('/api/studio')
  settings.value = data.settings || {}
})
</script>

<template>
  <div class="site-wrap py-xl py-lg-3xl">
    <div class="d-flex flex-column gap-2 mb-2xl max-w-3xl">
      <div class="d-flex align-items-center gap-1">
        <span class="sq-2 bg-danger d-inline-block"></span>
        <span class="type-overline text-uppercase ls-25 text-danger">{{ t('book.kicker') }}</span>
      </div>
      <h1 class="type-headline-mobile type-md-headline-lg text-uppercase">{{ t('book.title') }}</h1>
      <p class="type-body-lg text-body-secondary max-w-xl">
        {{ t('book.lead') }}
      </p>
    </div>
    <div class="row g-3">
      <div v-for="option in options" :key="option.to" class="col-md-4">
        <RouterLink class="choice-card" :to="option.to">
          <span class="type-overline text-uppercase text-danger">{{ option.kicker }}</span>
          <h2 class="type-headline-sm text-uppercase">{{ option.title }}</h2>
          <p class="type-body-md text-body-secondary mb-0">{{ option.copy }}</p>
          <span class="type-label-sm text-uppercase text-primary">{{ option.fixedRate ? '' : t('book.from') }}{{ formatMoney(option.rate, option.currency) }}{{ t('units.perHour') }}</span>
        </RouterLink>
      </div>
    </div>
    <p class="type-body-sm text-body-tertiary mt-4 mb-0">
      {{ t('book.serviceHint') }}
    </p>
  </div>
</template>
