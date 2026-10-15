<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { api } from '../api'
import { hourUnit, locale, t } from '../i18n'
import BookWizardView from './BookWizardView.vue'

const settings = ref({})
const content = ref(null)
const sidebar = ref(null)
const banner = ref(null)
const services = ref([])
const sending = ref(false)
const error = ref('')
const result = ref(null)

const form = reactive({
  full_name: '',
  email: '',
  service_id: '',
  message: '',
})

const page = computed(() => content.value?.[locale.value] || {})

const socials = computed(() => [
  { icon: 'instagram', label: t('book.instagram'), href: settings.value.instagram_url },
  { icon: 'youtube', label: t('book.youtube'), href: settings.value.youtube_url },
  { icon: 'vimeo', label: t('book.vimeo'), href: settings.value.vimeo_url },
].map((item) => ({
  ...item,
  href: String(item.href || '').startsWith('http') ? item.href : '',
})))

function linkMail(email) {
  return email ? `mailto:${email}` : ''
}

async function submit() {
  sending.value = true
  error.value = ''
  try {
    const data = await api('/api/project-requests', {
      method: 'POST',
      body: {
        full_name: form.full_name,
        email: form.email,
        service_id: Number(form.service_id),
        message: form.message,
      },
    })
    result.value = data
  } catch (e) {
    error.value = e.message
  } finally {
    sending.value = false
  }
}

onMounted(async () => {
  const [studio, booking] = await Promise.all([
    api('/api/studio'),
    api('/api/booking'),
  ])
  settings.value = studio.settings || {}
  content.value = studio.content || null
  sidebar.value = booking.sidebar || null
  banner.value = booking.banner || null
  services.value = booking.services || []
})
</script>

<template>
  <div>
    <section class="book-hero">
      <img v-if="sidebar?.image_url" :alt="sidebar.alt || ''" :src="sidebar.image_url">
      <div class="book-hero-shade"></div>
      <div class="site-wrap book-hero-copy">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="sq-2 bg-danger d-inline-block"></span>
          <span class="type-overline text-uppercase ls-25 text-danger">{{ page.kicker || t('book.kicker') }}</span>
        </div>
        <h1 class="book-hero-title text-uppercase">{{ page.title || t('book.title') }}</h1>
        <p class="type-body-lg text-body-secondary book-hero-lead mb-0">
          {{ page.lead || t('book.lead') }}
        </p>
      </div>
    </section>

    <section class="site-wrap book-studio">
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="sq-2 bg-danger d-inline-block"></span>
        <span class="type-overline text-uppercase ls-25 text-danger">{{ t('book.studioKicker') }}</span>
      </div>
      <h2 class="type-headline-md text-uppercase mb-2">{{ t('book.studioTitle') }}</h2>
      <p class="type-body-md text-body-secondary mb-4">{{ t('book.studioCopy', { n: settings.studio_minimum_hours || settings.minimum_hours || 1, unit: hourUnit(settings.studio_minimum_hours || settings.minimum_hours || 1) }) }}</p>
      <BookWizardView embedded booking-kind="studio_time" choose-unit />
    </section>

    <section class="site-wrap book-panel">
      <div class="row g-5">
        <div class="col-lg-7">
          <form v-if="!result" class="d-flex flex-column gap-3" @submit.prevent="submit">
            <label class="d-flex flex-column gap-2">
              <span class="book-label">{{ t('book.name') }} <span class="text-danger">*</span></span>
              <input v-model="form.full_name" class="form-control book-field" required :placeholder="t('book.namePlaceholder')">
            </label>
            <label class="d-flex flex-column gap-2">
              <span class="book-label">{{ t('book.email') }} <span class="text-danger">*</span></span>
              <input v-model="form.email" class="form-control book-field" required type="email" placeholder="you@example.com">
            </label>
            <label class="d-flex flex-column gap-2">
              <span class="book-label">{{ t('book.projectType') }} <span class="text-danger">*</span></span>
              <select v-model="form.service_id" class="form-select book-field book-select" required>
                <option value="">{{ t('book.projectPlaceholder') }}</option>
                <option v-for="service in services" :key="service.id" :value="service.id">{{ service.title }}</option>
              </select>
            </label>
            <label class="d-flex flex-column gap-2">
              <span class="book-label">{{ t('book.projectMessage') }} <span class="text-danger">*</span></span>
              <textarea v-model="form.message" class="form-control book-field" required rows="6" :placeholder="t('book.projectPlaceholderMessage')"></textarea>
            </label>
            <p v-if="error" class="type-body-sm text-danger mb-0">{{ error }}</p>
            <button class="btn btn-danger book-submit" :disabled="sending" type="submit">
              {{ sending ? t('book.sending') : t('book.submit') }}
              <span v-if="!sending" aria-hidden="true">→</span>
            </button>
          </form>
          <div v-else class="d-flex flex-column gap-2">
            <h2 class="type-headline-sm text-uppercase">{{ t('book.sent') }}</h2>
            <p class="type-body-md text-body-secondary mb-0">{{ result.mailed ? t('book.sentNote') : t('book.savedNote') }}</p>
          </div>
        </div>

        <aside class="col-lg-5 d-flex flex-column gap-4">
          <div class="d-flex flex-column gap-3">
            <p class="type-overline text-uppercase ls-20 text-danger mb-0">{{ t('book.contact') }}</p>
            <p class="book-city mb-0">{{ t('book.city') }}</p>
            <p v-if="settings.address" class="book-contact-line mb-0">
              <svg class="book-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"></path><circle cx="12" cy="11" r="2.2"></circle></svg>
              <span>{{ settings.address }}</span>
            </p>
            <a v-if="settings.owner_email" class="book-contact-line" :href="linkMail(settings.owner_email)">
              <svg class="book-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5.5" width="17" height="13"></rect><path d="M4 7.5l8 6 8-6"></path></svg>
              <span>{{ settings.owner_email }}</span>
            </a>
            <a v-if="settings.phone" class="book-contact-line" :href="`tel:${settings.phone}`">
              <svg class="book-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3.5h2.4l1 2.8-1.7 1a12 12 0 0 0 5 5l1-1.7 2.8 1V14a1.5 1.5 0 0 1-1.4 1.5A14 14 0 0 1 6.5 4.9 1.5 1.5 0 0 1 8 3.5z"></path></svg>
              <span>{{ settings.phone }}</span>
            </a>
          </div>
          <div class="d-flex flex-column gap-3">
            <p class="type-overline text-uppercase ls-20 text-danger mb-0">{{ t('book.follow') }}</p>
            <component
              :is="item.href ? 'a' : 'span'"
              v-for="item in socials"
              :key="item.label"
              class="book-social"
              :href="item.href || undefined"
              :target="item.href ? '_blank' : undefined"
              :rel="item.href ? 'noopener noreferrer' : undefined"
            >
              <svg v-if="item.icon === 'instagram'" class="book-social-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M7 2.5h10A4.5 4.5 0 0 1 21.5 7v10a4.5 4.5 0 0 1-4.5 4.5H7A4.5 4.5 0 0 1 2.5 17V7A4.5 4.5 0 0 1 7 2.5zm5 4.2a5.3 5.3 0 1 0 5.3 5.3A5.3 5.3 0 0 0 12 6.7zm6.35-1.25a1.15 1.15 0 1 0 1.15 1.15 1.15 1.15 0 0 0-1.15-1.15zM12 9a3 3 0 1 1-3 3 3 3 0 0 1 3-3z"/></svg>
              <svg v-else-if="item.icon === 'youtube'" class="book-social-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M23.2 12.2s0-3.2-.4-4.6a3 3 0 0 0-2.1-2.1C19 5 12 5 12 5s-7 0-8.7.5a3 3 0 0 0-2.1 2.1C.8 9 .8 12.2.8 12.2s0 3.2.4 4.6a3 3 0 0 0 2.1 2.1C5 19.4 12 19.4 12 19.4s7 0 8.7-.5a3 3 0 0 0 2.1-2.1c.4-1.4.4-4.6.4-4.6zM9.8 15.5v-6.6l6.2 3.3z"/></svg>
              <svg v-else class="book-social-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.1 7.2c-.1 2.2-1.6 5.2-4.6 9-3.1 4-5.7 6-7.8 6-1.3 0-2.4-1.2-3.3-3.6-.6-2.2-1.2-4.4-1.8-6.6-.7-2.4-1.4-3.6-2.1-3.6-.2 0-.7.3-1.7 1L1 8.2c1.1-.9 2.2-1.9 3.2-2.8 1.5-1.2 2.5-1.9 3.2-1.9 1.5-.1 2.5.9 2.9 3.1.4 2.5.7 4 .9 4.6.5 2.2 1 3.2 1.6 3.2.5 0 1.2-.7 2.1-2.2.9-1.5 1.4-2.6 1.5-3.4.1-1.3-.4-2-1.6-2-.6 0-1.2.1-1.8.4C14.2 5 16.5 3.1 20 3.2c2.5.1 3.7 1.7 3.1 4z"/></svg>
              {{ item.label }}
            </component>
          </div>
        </aside>
      </div>
    </section>

    <section class="book-band">
      <img v-if="banner?.image_url" :alt="banner.alt || ''" :src="banner.image_url">
      <div class="book-band-shade"></div>
      <div class="site-wrap book-band-copy">
        <p class="book-band-brand text-uppercase mb-2">Five Star House</p>
        <div class="d-flex flex-wrap justify-content-between gap-2">
          <p class="type-overline text-uppercase ls-20 text-danger mb-0">{{ t('footer.tagline') }}</p>
          <p class="type-overline text-uppercase ls-20 mb-0">{{ t('book.city') }}</p>
        </div>
      </div>
    </section>
  </div>
</template>
