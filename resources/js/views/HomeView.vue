<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '../api'
import { formatCharge } from '../format'
import { locale, t } from '../i18n'

const hero = ref(null)
const works = ref([])
const services = ref([])
const pageCopy = ref(null)
const failed = ref(false)
const error = computed(() => (failed.value ? t('home.loadError') : ''))
const intro = computed(() => {
  locale.value
  const text = pageCopy.value?.[locale.value] || {}
  const lines = String(text.heading || '').split(/\r?\n/).map((line) => line.trim()).filter(Boolean)
  const tags = Array.isArray(text.tags) ? text.tags.map((tag) => String(tag).trim()).filter(Boolean) : []
  return {
    lines: lines.length ? lines : ['FIVE STAR', 'HOUSE'],
    description: text.description || t('home.lead'),
    tags,
    button: text.button || t('home.viewWork'),
    buttonSecondary: text.button_secondary || t('home.startProject'),
  }
})

onMounted(async () => {
  try {
    const banner = await api('/api/home/banner')
    hero.value = banner.hero
    pageCopy.value = banner.page || null
  } catch (e) {
    failed.value = true
  }

  try {
    const data = await api('/api/home')
    works.value = data.works || []
    services.value = data.services || []
  } catch (e) {
    failed.value = true
  }
})
</script>

<template>
  <section class="page-banner">
    <div class="site-wrap">
    <p v-if="error" class="type-body-md text-danger">{{ error }}</p>
    <div class="row g-4 g-lg-xl page-banner-grid">
      <div class="col-lg-6 page-banner-copy home-banner-copy z-1">
        <div class="home-banner-stage">
          <div class="d-flex align-items-center gap-1">
            <span class="sq-2 bg-danger d-inline-block" aria-hidden="true"></span>
            <span class="type-overline text-uppercase ls-25 text-danger">{{ t('home.kicker') }}</span>
          </div>
          <h1 class="page-banner-title">
            <template v-for="(line, index) in intro.lines" :key="index">
              {{ line }}<br v-if="index < intro.lines.length - 1">
            </template>
          </h1>
          <p class="page-banner-lead text-body-secondary">
            {{ intro.description }}
          </p>
          <div v-if="intro.tags.length" class="d-flex flex-wrap align-items-center gap-3">
            <div v-for="tag in intro.tags" :key="tag" class="d-flex align-items-center gap-1 text-body-secondary type-label-sm text-uppercase">
              <span class="material-symbols-outlined text-danger icon-16" aria-hidden="true">check_circle</span>
              <span>{{ tag }}</span>
            </div>
          </div>
          <div class="home-banner-actions">
            <a class="btn btn-danger btn-pad" href="#showcase">
              <span>{{ intro.button }}</span>
              <span class="material-symbols-outlined icon-18" aria-hidden="true">arrow_forward</span>
            </a>
            <RouterLink class="btn btn-secondary btn-pad" to="/book">
              <span>{{ intro.buttonSecondary }}</span>
            </RouterLink>
          </div>
        </div>
        <div class="metric-ribbon w-100 pt-2xl mt-2xl p-3 rounded-1">
          <div class="row g-3">
            <div class="col-4 d-flex flex-column">
              <span class="type-headline-md fw-bold">1,600<span class="text-danger font-display">{{ t('units.sqft') }}</span></span>
              <span class="type-overline text-body-tertiary text-uppercase ls-widest">{{ t('home.cycStudio') }}</span>
            </div>
            <div class="col-4 d-flex flex-column">
              <span class="type-headline-md fw-bold">120+</span>
              <span class="type-overline text-body-tertiary text-uppercase ls-widest">{{ t('home.productions') }}</span>
            </div>
            <div class="col-4 d-flex flex-column">
              <span class="type-headline-md fw-bold">45.50°</span>
              <span class="type-overline text-body-tertiary text-uppercase ls-widest">{{ t('home.city') }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="zoom-frame zoom-slow page-banner-media position-relative bg-body-secondary overflow-hidden shadow-2xl">
          <div v-if="hero" class="media-cover w-100 h-100" :style="{ backgroundImage: `url('${hero.image_url}')` }"></div>
          <div class="overlay-fade position-absolute top-0 start-0 end-0 bottom-0 opacity-80"></div>
          <div class="position-absolute bottom-0 start-0 end-0 m-3 d-flex align-items-center justify-content-between text-body-secondary type-label-sm">
            <span class="bg-dark bg-opacity-80 px-2 py-1 text-uppercase ls-widest text-fixed">{{ hero?.category || t('home.heroRef') }}</span>
            <span class="text-uppercase text-body-tertiary ls-wider d-none d-sm-inline-block">{{ hero?.title || t('home.stageReady') }}</span>
          </div>
        </div>
      </div>
    </div>
    </div>
  </section>

  <section id="showcase" class="site-wrap py-xl">
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between mb-4 gap-2">
      <div class="d-flex flex-column gap-1">
        <span class="type-overline text-uppercase ls-20 text-primary">{{ t('home.theHouse') }}</span>
        <h2 class="type-headline-lg text-uppercase ls-normal">{{ t('home.studioImages') }}</h2>
      </div>
      <p class="type-label-sm text-uppercase ls-widest text-body-secondary max-w-xs text-start text-md-end">
        {{ t('home.disciplines') }}
      </p>
    </div>

    <div class="row g-2">
      <div v-for="work in works" :key="work.id" :class="work.col_class || 'col-md-4'">
        <article class="project-card position-relative overflow-hidden rounded-1 h-100" :class="work.aspect || 'aspect-4x5'">
          <img class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" :alt="work.alt || work.title" :src="work.image_url">
          <div class="project-shade position-absolute top-0 start-0 end-0 bottom-0 p-3 d-flex flex-column justify-content-end">
            <span class="type-overline text-uppercase text-primary">{{ work.category }}</span>
            <span class="type-headline-sm text-uppercase">{{ work.title }}</span>
          </div>
        </article>
      </div>
    </div>

    <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between py-3 mt-2 bg-dark px-3 rounded-1 text-body-secondary">
      <p class="type-overline text-uppercase ls-25">{{ t('home.manifesto') }}</p>
      <p class="type-overline text-uppercase ls-25 text-body-tertiary mt-1 mt-sm-0">{{ t('home.cityLine') }}</p>
    </div>
  </section>

  <section class="site-wrap py-2xl">
    <div class="d-flex flex-column mb-xl">
      <span class="type-overline text-uppercase ls-22 text-primary">{{ t('home.deliver') }}</span>
      <h2 class="type-headline-lg text-uppercase">{{ t('home.scale') }}</h2>
    </div>
    <div class="row g-3">
      <div v-for="service in services" :key="service.id" class="col-md-6 col-lg-3">
        <article class="service-tile d-flex flex-column justify-content-between bg-body-secondary p-4 rounded-1 h-100">
          <div>
            <span class="type-headline-sm fw-bold text-danger d-block mb-2">{{ service.number }}</span>
            <h3 class="type-headline-sm text-uppercase hover-accent mb-1">{{ service.title }}</h3>
            <p class="type-body-md text-body-secondary mb-3">{{ service.summary }}</p>
            <p class="type-label-sm text-uppercase ls-wider text-primary mb-3">{{ formatCharge(service) }}<template v-if="service.enforce_minimum !== false"> · {{ t('home.hourMin', { n: service.minimum_hours || 1 }) }}</template></p>
          </div>
          <RouterLink class="d-flex align-items-center gap-1 type-label-sm text-uppercase ls-wider text-body-tertiary hover-body" :to="`/services/${service.slug}`">
            <span>{{ t('home.viewService') }}</span>
            <span class="material-symbols-outlined icon-16" aria-hidden="true">arrow_forward</span>
          </RouterLink>
        </article>
      </div>
    </div>
  </section>

  <section class="w-100 bg-dark py-3xl position-relative overflow-hidden">
    <div class="cta-glow" aria-hidden="true"></div>
    <div class="position-relative z-1 max-w-800 mx-auto px-page text-center d-flex flex-column align-items-center">
      <div class="bar bg-danger mb-3"></div>
      <h2 class="type-headline-lg text-uppercase ls-wide mb-1">{{ t('home.ctaTitle') }}</h2>
      <p class="type-body-lg text-body-secondary mb-xl">{{ t('home.ctaCopy') }}</p>
      <div class="d-flex flex-wrap align-items-center justify-content-center gap-3">
        <RouterLink class="btn btn-danger btn-pad-wide" to="/book">
          <span>{{ t('home.bookProject') }}</span>
          <span class="material-symbols-outlined icon-18" aria-hidden="true">arrow_forward</span>
        </RouterLink>
        <RouterLink class="btn btn-secondary btn-pad-wide" to="/services">
          <span>{{ t('home.explore') }}</span>
        </RouterLink>
      </div>
      <div class="mt-2xl pt-3 text-body-tertiary type-overline text-uppercase ls-widest">
        {{ t('home.availability') }}
      </div>
    </div>
  </section>
</template>
