<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../api'
import { formatCharge } from '../format'

const banner = ref(null)
const services = ref([])
const error = ref('')

onMounted(async () => {
  try {
    const data = await api('/api/services')
    banner.value = data.banner
    services.value = data.services
  } catch (e) {
    error.value = 'Services could not be loaded.'
  }
})
</script>

<template>
  <section class="w-100 bg-dark py-xl py-lg-3xl">
    <div class="site-wrap">
      <p v-if="error" class="type-body-md text-danger mb-3">{{ error }}</p>
      <div class="row g-4 g-lg-xl align-items-center">
        <div class="col-lg-6 d-flex flex-column gap-3">
          <div class="d-flex align-items-center gap-1">
            <span class="sq-2 bg-danger d-inline-block" aria-hidden="true"></span>
            <span class="type-overline text-uppercase ls-25 text-danger">OUR SERVICES</span>
          </div>
          <h1 class="type-headline-mobile type-md-headline-lg text-uppercase lh-1 ls-tight">
            CREATIVE<br>PRODUCTION<br>SOLUTIONS
          </h1>
          <p class="type-body-lg text-body-secondary max-w-xl pt-1">
            From concept to final cut, we handle every step of the process. Whether you need a full production, clean visuals, or an agile creative partner, we bring the right team, industry-grade tools, and seasoned experience to realize your vision.
          </p>
          <div class="d-flex flex-wrap align-items-center gap-3 pt-2">
            <div class="d-flex align-items-center gap-1 text-body-secondary type-label-sm text-uppercase">
              <span class="material-symbols-outlined text-danger icon-16">check_circle</span>
              <span>Full-Spectrum Production</span>
            </div>
            <div class="d-flex align-items-center gap-1 text-body-secondary type-label-sm text-uppercase">
              <span class="material-symbols-outlined text-danger icon-16">check_circle</span>
              <span>ARRI &amp; RED Ecosystem</span>
            </div>
            <div class="d-flex align-items-center gap-1 text-body-secondary type-label-sm text-uppercase">
              <span class="material-symbols-outlined text-danger icon-16">check_circle</span>
              <span>1600 SQ FT Cyclorama</span>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="zoom-frame zoom-slow position-relative w-100 aspect-16x10 bg-body-secondary overflow-hidden shadow-2xl">
            <div v-if="banner" class="media-cover w-100 h-100" :style="{ backgroundImage: `url('${banner.image_url}')` }"></div>
            <div class="overlay-fade position-absolute top-0 start-0 end-0 bottom-0 opacity-80"></div>
            <div class="position-absolute bottom-0 start-0 end-0 m-3 d-flex align-items-center justify-content-between text-body-secondary type-label-sm">
              <span class="bg-dark bg-opacity-80 px-2 py-1 text-uppercase ls-widest text-fixed">{{ banner?.category || 'STAGE 01 • LIVE CAPTURE' }}</span>
              <span class="text-uppercase text-body-tertiary ls-wider d-none d-sm-inline-block">{{ banner?.title || 'MONTREAL, QC' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="w-100 py-2xl py-lg-3xl">
    <div class="site-wrap d-flex flex-column gap-2xl">
      <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 bg-body-secondary bg-opacity-40 p-3">
        <div>
          <span class="type-overline text-uppercase ls-20 text-danger">CAPABILITIES • SCOPE</span>
          <h2 class="type-headline-md text-uppercase mt-1">CORE DISCIPLINES</h2>
        </div>
        <p class="type-body-sm text-body-secondary max-w-md">
          Modular, turnkey, or collaborative. We operate as an integrated unit or deploy custom teams geared precisely to campaign parameters.
        </p>
      </div>

      <article v-for="service in services" :id="service.slug" :key="service.id" class="service-tile bg-body-secondary p-3 p-md-xl shadow-sm">
        <div class="row g-4 g-lg-xl align-items-center">
          <div class="col-lg-5" :class="{ 'order-lg-2': service.image_side === 'right' }">
            <div class="zoom-frame position-relative aspect-16x10 bg-body-tertiary overflow-hidden">
              <div v-if="service.image_url" class="media-cover w-100 h-100" :style="{ backgroundImage: `url('${service.image_url}')` }"></div>
              <div class="position-absolute top-12 bg-dark bg-opacity-90 px-25 py-1 type-label-sm ls-widest" :class="service.image_side === 'right' ? 'end-12' : 'start-12'">{{ service.badge }}</div>
            </div>
          </div>
          <div class="col-lg-7 d-flex flex-column gap-3" :class="{ 'order-lg-1': service.image_side === 'right' }">
            <div class="d-flex align-items-center justify-content-between">
              <span class="type-headline-sm text-danger ls-widest fw-bold">{{ service.number }}</span>
              <span class="type-label-sm text-uppercase ls-widest text-body-tertiary">{{ service.eyebrow }}</span>
            </div>
            <h3 class="type-headline-md text-uppercase ls-wide">
              <RouterLink :to="`/services/${service.slug}`">{{ service.title }}</RouterLink>
            </h3>
            <p class="type-body-md text-body-secondary">{{ service.description }}</p>
            <p class="type-label-md text-uppercase text-primary">{{ formatCharge(service) }} · {{ service.minimum_hours || 2 }} HOUR MINIMUM</p>
            <div class="d-flex flex-wrap gap-2 pt-1">
              <span v-for="tag in service.tags" :key="tag" class="bg-secondary px-12 py-1 type-label-sm text-uppercase ls-wider">{{ tag }}</span>
            </div>
            <div class="d-flex align-items-center gap-4 pt-2">
              <RouterLink class="btn btn-danger btn-quote" :to="`/services/${service.slug}`">
                <span>VIEW SERVICE</span>
                <span class="material-symbols-outlined icon-16" aria-hidden="true">arrow_forward</span>
              </RouterLink>
            </div>
          </div>
        </div>
      </article>
    </div>
  </section>

  <section class="w-100 bg-dark py-2xl">
    <div class="site-wrap">
      <div class="bg-body-secondary p-4">
        <div class="row g-3">
          <div class="col-md-3 d-flex flex-column gap-1">
            <span class="type-overline text-uppercase ls-widest text-danger">STUDIO FOOTPRINT</span>
            <span class="type-headline-md type-md-headline-lg fw-bold">1,600<span class="text-danger type-headline-sm">SQ FT</span></span>
            <span class="type-body-sm text-body-tertiary">Expanded two-wall seamless cyc</span>
          </div>
          <div class="col-md-3 d-flex flex-column gap-1">
            <span class="type-overline text-uppercase ls-widest text-danger">POWER CAP</span>
            <span class="type-headline-md type-md-headline-lg fw-bold">100<span class="text-danger type-headline-sm">A / 3-PH</span></span>
            <span class="type-body-sm text-body-tertiary">Isolated sound recording feed</span>
          </div>
          <div class="col-md-3 d-flex flex-column gap-1">
            <span class="type-overline text-uppercase ls-widest text-danger">CLEAR CEILINGS</span>
            <span class="type-headline-md type-md-headline-lg fw-bold">14<span class="text-danger type-headline-sm">FT</span></span>
            <span class="type-body-sm text-body-tertiary">Full speed-rail lighting grid</span>
          </div>
          <div class="col-md-3 d-flex flex-column gap-1">
            <span class="type-overline text-uppercase ls-widest text-danger">LOCATION</span>
            <span class="type-headline-md type-md-headline-lg fw-bold">YUL<span class="text-danger type-headline-sm">MILE-EX</span></span>
            <span class="type-body-sm text-body-tertiary">45.5017° N, 73.5673° W</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="w-100 py-3xl">
    <div class="site-wrap d-flex flex-column gap-2xl">
      <div class="d-flex flex-column gap-1 max-w-2xl">
        <span class="type-overline text-uppercase ls-25 text-danger">EXECUTION MODEL</span>
        <h2 class="type-headline-mobile type-md-headline-lg text-uppercase">THE WORKFLOW</h2>
        <p class="type-body-md text-body-secondary">Strict methodology refined across commercial shoots, editorial campaigns, and long-form cinema.</p>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <article class="step-card bg-body-secondary p-xl d-flex flex-column justify-content-between gap-xl position-relative overflow-hidden h-100">
            <div class="d-flex flex-column gap-3 position-relative z-1">
              <span class="step-num type-headline-lg fw-bold">01</span>
              <h3 class="type-headline-sm text-uppercase ls-wider">DISCOVERY &amp; PRE-PRO</h3>
              <p class="type-body-md text-body-secondary">Creative treatments, moodboard alignment, budget architecture, location scouting, talent casting, and meticulous shot listing with 3D set pre-visualization.</p>
            </div>
            <div class="pt-3 border-top border-faint text-body-tertiary type-label-sm text-uppercase ls-widest">PHASE: 01 // ARCHITECTURE</div>
          </article>
        </div>
        <div class="col-md-4">
          <article class="step-card bg-body-secondary p-xl d-flex flex-column justify-content-between gap-xl position-relative overflow-hidden h-100">
            <div class="d-flex flex-column gap-3 position-relative z-1">
              <span class="step-num type-headline-lg fw-bold">02</span>
              <h3 class="type-headline-sm text-uppercase ls-wider">PRODUCTION &amp; SHOOT DAY</h3>
              <p class="type-body-md text-body-secondary">Execution on location or in our Mile-Ex soundstage. High-calibre camera crews, cinema grip trucks, dedicated DIT data verification, and live client monitor feeds.</p>
            </div>
            <div class="pt-3 border-top border-faint text-body-tertiary type-label-sm text-uppercase ls-widest">PHASE: 02 // CAPTURE</div>
          </article>
        </div>
        <div class="col-md-4">
          <article class="step-card bg-body-secondary p-xl d-flex flex-column justify-content-between gap-xl position-relative overflow-hidden h-100">
            <div class="d-flex flex-column gap-3 position-relative z-1">
              <span class="step-num type-headline-lg fw-bold">03</span>
              <h3 class="type-headline-sm text-uppercase ls-wider">POST &amp; MASTER DELIVERY</h3>
              <p class="type-body-md text-body-secondary">Offline rough cut, sound design, original scoring, precision DaVinci color grading, VFX compositing, and multi-format master exports geared for theater, broadcast, and social.</p>
            </div>
            <div class="pt-3 border-top border-faint text-body-tertiary type-label-sm text-uppercase ls-widest">PHASE: 03 // MASTERING</div>
          </article>
        </div>
      </div>
    </div>
  </section>

  <section class="w-100 bg-dark py-3xl position-relative overflow-hidden">
    <div class="wash-red position-absolute top-0 start-0 end-0 bottom-0"></div>
    <div class="max-w-900 mx-auto px-page text-center d-flex flex-column align-items-center gap-3 position-relative z-1">
      <div class="bar-lg bg-danger"></div>
      <span class="type-overline text-uppercase ls-25 text-primary">LET'S CREATE SOMETHING GREAT</span>
      <h2 class="type-headline-mobile type-md-headline-lg text-uppercase ls-tight">READY TO BRING YOUR PROJECT TO LIFE?</h2>
      <p class="type-body-lg text-body-secondary max-w-xl">Your vision. Our expertise. Let's make it happen. Reach out to discuss dates, equipment packages, or end-to-end studio production.</p>
      <div class="pt-3">
        <RouterLink class="btn btn-danger btn-cta" to="/book">
          <span>BOOK A PROJECT</span>
          <span class="material-symbols-outlined icon-16" aria-hidden="true">arrow_forward</span>
        </RouterLink>
      </div>
    </div>
  </section>
</template>
