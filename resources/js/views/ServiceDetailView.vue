<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import { formatMoney } from '../format'
import { t, tp } from '../i18n'
import { pageSeo } from '../seo'
import BookWizardView from './BookWizardView.vue'

const route = useRoute()
const service = ref(null)
const failed = ref(false)
const billingUnit = ref('')

const minimum = computed(() => service.value?.minimum_hours || 1)
const error = computed(() => (failed.value ? t('detail.missing') : ''))
const priceLabel = computed(() => {
  if (!service.value) return ''
  const money = formatMoney(service.value.charge, service.value.currency)
  if (billingUnit.value === 'hour') return `${money}${t('units.perHour')}`
  if (billingUnit.value === 'day') return `${money}${t('units.perDay')}`
  if (billingUnit.value === 'project') return `${money}${t('units.perProject')}`
  return formatMoney(service.value.charge, service.value.currency)
})

watch(service, (value) => {
  if (!value) return
  pageSeo.value = {
    title: `${value.title} — Five Star House`,
    description: value.summary || value.description || '',
    image: value.image_url || '',
  }
})

onUnmounted(() => {
  pageSeo.value = null
})

onMounted(async () => {
  try {
    const data = await api(`/api/services/${route.params.slug}`)
    service.value = data.service
  } catch (e) {
    failed.value = true
  }
})
</script>

<template>
  <section v-if="service" class="site-wrap py-xl py-lg-3xl">
    <div class="row g-4 g-lg-xl align-items-center">
      <div class="col-lg-6">
        <div class="zoom-frame position-relative aspect-16x10 bg-body-secondary overflow-hidden">
          <div v-if="service.image_url" class="media-cover w-100 h-100" :style="{ backgroundImage: `url('${service.image_url}')` }"></div>
        </div>
      </div>
      <div class="col-lg-6 d-flex flex-column gap-3">
        <div class="d-flex align-items-center gap-1">
          <span class="sq-2 bg-danger d-inline-block"></span>
          <span class="type-overline text-uppercase ls-25 text-danger">{{ service.eyebrow || t('detail.fallbackEyebrow') }}</span>
        </div>
        <h1 class="type-headline-md type-md-headline-lg text-uppercase">{{ service.title }}</h1>
        <p class="type-body-lg text-body-secondary">{{ service.description }}</p>
        <p class="type-headline-sm text-uppercase text-primary">{{ priceLabel }}</p>
        <p v-if="!billingUnit || billingUnit === 'hour'" class="type-body-md text-body-secondary mb-0">{{ tp('detail.minimum', minimum) }}</p>
        <div class="d-flex flex-wrap gap-2">
          <span v-for="tag in service.tags" :key="tag" class="bg-secondary px-12 py-1 type-label-sm text-uppercase ls-wider">{{ tag }}</span>
        </div>
        <label class="d-flex flex-column gap-1 align-self-start">
          <span class="type-label-sm text-uppercase">{{ t('detail.rate') }}</span>
          <select v-model="billingUnit" class="form-select">
            <option value="">{{ t('detail.chooseRate') }}</option>
            <option value="hour">{{ t('detail.perHour') }}</option>
            <option value="project">{{ t('detail.perProject') }}</option>
            <option value="day">{{ t('detail.perDay') }}</option>
          </select>
        </label>
      </div>
    </div>
    <BookWizardView v-if="billingUnit" :billing-unit="billingUnit" embedded />
  </section>
  <section v-else class="site-wrap py-3xl">
    <p class="type-body-md text-danger">{{ error || t('detail.loading') }}</p>
  </section>
</template>
