<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../api'
import { formatCharge } from '../format'

const hero = ref(null)
const works = ref([])
const services = ref([])
const error = ref('')

onMounted(async () => {
  try {
    const data = await api('/api/home')
    hero.value = data.hero
    works.value = data.works
    services.value = data.services
  } catch (e) {
    error.value = 'The studio feed is unavailable right now.'
  }
})
</script>

<template>
  <section class="site-wrap py-xl py-lg-3xl">
    <p v-if="error" class="type-body-md text-danger">{{ error }}</p>
    <div class="row g-4 g-lg-xl align-items-center">
      <div class="col-lg-6 d-flex flex-column align-items-start z-1">
        <div class="d-inline-flex align-items-center gap-1 mb-3">
          <span class="sq-2 bg-danger d-inline-block" aria-hidden="true"></span>
          <p class="type-overline text-uppercase ls-22 text-primary">FILM • PHOTO • CREATIVE PRODUCTION</p>
        </div>
        <h1 class="type-hero text-uppercase ls-tight lh-1 mb-3">FIVE STAR<br>HOUSE</h1>
        <p class="type-body-lg text-body-secondary max-w-lg mb-xl">
          Production, direction and visual creation under one roof. We craft provocative cinematic films, commercial campaigns, and bespoke editorial imagery for global brands and independent visionaries.
        </p>
        <div class="d-flex flex-wrap align-items-center gap-3">
          <a class="btn btn-danger btn-pad" href="#showcase">
            <span>VIEW WORK</span>
            <span class="material-symbols-outlined icon-18" aria-hidden="true">arrow_forward</span>
          </a>
          <RouterLink class="btn btn-secondary btn-pad" to="/book">
            <span>START A PROJECT</span>
          </RouterLink>
        </div>
        <div class="metric-ribbon w-100 pt-2xl mt-2xl p-3 rounded-1">
          <div class="row g-3">
            <div class="col-4 d-flex flex-column">
              <span class="type-headline-md fw-bold">1,600<span class="text-danger font-display">SQFT</span></span>
              <span class="type-overline text-body-tertiary text-uppercase ls-widest">CYC STUDIO</span>
            </div>
            <div class="col-4 d-flex flex-column">
              <span class="type-headline-md fw-bold">120+</span>
              <span class="type-overline text-body-tertiary text-uppercase ls-widest">PRODUCTIONS</span>
            </div>
            <div class="col-4 d-flex flex-column">
              <span class="type-headline-md fw-bold">45.50°</span>
              <span class="type-overline text-body-tertiary text-uppercase ls-widest">MTL • CA</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 position-relative mt-4 mt-lg-0">
        <div class="portrait-frame position-relative w-100 aspect-4x5 bg-body-secondary overflow-hidden rounded-1">
          <img v-if="hero" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" :alt="hero.alt || 'Studio portrait'" :src="hero.image_url">
          <div class="overlay-fade position-absolute top-0 start-0 end-0 bottom-0 opacity-80"></div>
          <div class="position-absolute bottom-0 start-0 m-3 d-flex align-items-center gap-2 bg-dark bg-opacity-90 px-3 py-1 rounded-1 backdrop-md">
            <span class="sq-25 rounded-circle bg-danger animate-pulse" aria-hidden="true"></span>
            <span class="type-overline text-uppercase ls-widest">{{ hero?.title || 'STAGE A • CAMERA READY' }}</span>
          </div>
          <div class="position-absolute top-0 end-0 m-3">
            <span class="type-label-sm text-uppercase ls-widest text-body-tertiary bg-dark bg-opacity-80 px-2 py-1 rounded-1">{{ hero?.category || 'REF • 001/MTL' }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="showcase" class="site-wrap py-xl">
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between mb-4 gap-2">
      <div class="d-flex flex-column gap-1">
        <span class="type-overline text-uppercase ls-20 text-primary">THE HOUSE</span>
        <h2 class="type-headline-lg text-uppercase ls-normal">STUDIO IMAGES</h2>
      </div>
      <p class="type-label-sm text-uppercase ls-widest text-body-secondary max-w-xs text-start text-md-end">
        NARRATIVE • FASHION • COMMERCIAL ADVERTISING • SOUNDSTAGE
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
      <p class="type-overline text-uppercase ls-25">REAL PEOPLE. REAL STORIES. POWERFUL VISUALS.</p>
      <p class="type-overline text-uppercase ls-25 text-body-tertiary mt-1 mt-sm-0">MONTREAL, CANADA • YUL</p>
    </div>
  </section>

  <section class="site-wrap py-2xl">
    <div class="d-flex flex-column mb-xl">
      <span class="type-overline text-uppercase ls-22 text-primary">WHAT WE DELIVER</span>
      <h2 class="type-headline-lg text-uppercase">BUILT FOR SCALE &amp; VISION</h2>
    </div>
    <div class="row g-3">
      <div v-for="service in services" :key="service.id" class="col-md-6 col-lg-3">
        <article class="service-tile d-flex flex-column justify-content-between bg-body-secondary p-4 rounded-1 h-100">
          <div>
            <span class="type-headline-sm fw-bold text-danger d-block mb-2">{{ service.number }}</span>
            <h3 class="type-headline-sm text-uppercase hover-accent mb-1">{{ service.title }}</h3>
            <p class="type-body-md text-body-secondary mb-3">{{ service.summary }}</p>
            <p class="type-label-sm text-uppercase ls-wider text-primary mb-3">{{ formatCharge(service) }} · {{ service.minimum_hours || 2 }} HOUR MIN</p>
          </div>
          <RouterLink class="d-flex align-items-center gap-1 type-label-sm text-uppercase ls-wider text-body-tertiary hover-body" :to="`/services/${service.slug}`">
            <span>VIEW SERVICE</span>
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
      <h2 class="type-headline-lg text-uppercase ls-wide mb-1">LET'S CREATE SOMETHING GREAT</h2>
      <p class="type-body-lg text-body-secondary mb-xl">Your vision. Our expertise. Let's make it happen.</p>
      <div class="d-flex flex-wrap align-items-center justify-content-center gap-3">
        <RouterLink class="btn btn-danger btn-pad-wide" to="/book">
          <span>BOOK A PROJECT</span>
          <span class="material-symbols-outlined icon-18" aria-hidden="true">arrow_forward</span>
        </RouterLink>
        <RouterLink class="btn btn-secondary btn-pad-wide" to="/services">
          <span>EXPLORE SERVICES</span>
        </RouterLink>
      </div>
      <div class="mt-2xl pt-3 text-body-tertiary type-overline text-uppercase ls-widest">
        AVAILABILITY: Q2/Q3 BOOKINGS NOW OPEN
      </div>
    </div>
  </section>
</template>
