<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import { formatCharge } from '../format'

const route = useRoute()
const service = ref(null)
const error = ref('')

const minimum = computed(() => service.value?.minimum_hours || 2)

onMounted(async () => {
  try {
    const data = await api(`/api/services/${route.params.slug}`)
    service.value = data.service
  } catch (e) {
    error.value = 'This service is not available.'
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
          <span class="type-overline text-uppercase ls-25 text-danger">{{ service.eyebrow || 'SERVICE' }}</span>
        </div>
        <h1 class="type-headline-md type-md-headline-lg text-uppercase">{{ service.title }}</h1>
        <p class="type-body-lg text-body-secondary">{{ service.description }}</p>
        <p class="type-headline-sm text-uppercase text-primary">{{ formatCharge(service) }}</p>
        <p class="type-body-md text-body-secondary mb-0">Minimum booking is {{ minimum }} hours. The calendar shows times that are already taken.</p>
        <div class="d-flex flex-wrap gap-2">
          <span v-for="tag in service.tags" :key="tag" class="bg-secondary px-12 py-1 type-label-sm text-uppercase ls-wider">{{ tag }}</span>
        </div>
        <RouterLink class="btn btn-danger btn-pad align-self-start" :to="`/book/service/${service.slug}`">
          <span>BOOK THIS SERVICE</span>
          <span class="material-symbols-outlined icon-18">arrow_forward</span>
        </RouterLink>
      </div>
    </div>
  </section>
  <section v-else class="site-wrap py-3xl">
    <p class="type-body-md text-danger">{{ error || 'Loading service...' }}</p>
  </section>
</template>
