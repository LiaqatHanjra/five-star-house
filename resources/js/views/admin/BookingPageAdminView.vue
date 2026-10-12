<script setup>
import { onMounted, reactive, ref } from 'vue'
import { api } from '../../api'

const fields = [
  'kicker',
  'title',
  'lead',
  'hint',
  'studio_kicker',
  'studio_title',
  'studio_copy',
  'event_kicker',
  'event_title',
  'event_copy',
  'owner_kicker',
  'owner_title',
  'owner_copy',
]

function blank() {
  return Object.fromEntries(fields.map((field) => [field, '']))
}

const content = reactive({ en: blank(), fr: blank() })
const lang = ref('en')
const message = ref('')
const error = ref('')
const saving = ref(false)

onMounted(async () => {
  const data = await api('/api/admin/booking-page')
  Object.assign(content.en, data.content?.en || {})
  Object.assign(content.fr, data.content?.fr || {})
})

async function save() {
  saving.value = true
  message.value = ''
  error.value = ''
  try {
    const data = await api('/api/admin/booking-page', {
      method: 'POST',
      body: { en: { ...content.en }, fr: { ...content.fr } },
    })
    Object.assign(content.en, data.content.en)
    Object.assign(content.fr, data.content.fr)
    message.value = 'Booking page saved.'
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form class="d-flex flex-column gap-3 max-w-3xl" @submit.prevent="save">
    <div>
      <p class="type-overline text-uppercase ls-20 text-danger">STUDIO DESK</p>
      <h1 class="type-headline-md text-uppercase">BOOKING PAGE</h1>
    </div>
    <p class="type-body-sm text-body-secondary mb-0">
      These texts appear on the public booking page. Edit English and French separately. In a card description, <code>{n}</code> is the minimum number of hours and <code>{unit}</code> is the word hour.
    </p>
    <p v-if="message" class="type-body-sm text-primary mb-0">{{ message }}</p>
    <p v-if="error" class="type-body-sm text-danger mb-0">{{ error }}</p>

    <div class="d-flex gap-2">
      <button class="btn btn-quote" :class="lang === 'en' ? 'btn-danger' : 'btn-secondary'" type="button" @click="lang = 'en'">English</button>
      <button class="btn btn-quote" :class="lang === 'fr' ? 'btn-danger' : 'btn-secondary'" type="button" @click="lang = 'fr'">Français</button>
    </div>

    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Eyebrow</span>
      <input v-model="content[lang].kicker" class="form-control" required maxlength="80">
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Heading</span>
      <input v-model="content[lang].title" class="form-control" required maxlength="160">
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Introduction</span>
      <textarea v-model="content[lang].lead" class="form-control" required maxlength="600" rows="4"></textarea>
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Footer note</span>
      <textarea v-model="content[lang].hint" class="form-control" required maxlength="600" rows="3"></textarea>
    </label>

    <h2 class="type-headline-sm text-uppercase mb-0 mt-2">Studio rental</h2>
    <div class="row g-3">
      <label class="col-md-4 d-flex flex-column gap-1">
        <span class="type-label-sm text-uppercase">Label</span>
        <input v-model="content[lang].studio_kicker" class="form-control" required maxlength="80">
      </label>
      <label class="col-md-8 d-flex flex-column gap-1">
        <span class="type-label-sm text-uppercase">Title</span>
        <input v-model="content[lang].studio_title" class="form-control" required maxlength="160">
      </label>
    </div>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Description</span>
      <textarea v-model="content[lang].studio_copy" class="form-control" required maxlength="600" rows="3"></textarea>
    </label>

    <h2 class="type-headline-sm text-uppercase mb-0 mt-2">Studio for an event</h2>
    <div class="row g-3">
      <label class="col-md-4 d-flex flex-column gap-1">
        <span class="type-label-sm text-uppercase">Label</span>
        <input v-model="content[lang].event_kicker" class="form-control" required maxlength="80">
      </label>
      <label class="col-md-8 d-flex flex-column gap-1">
        <span class="type-label-sm text-uppercase">Title</span>
        <input v-model="content[lang].event_title" class="form-control" required maxlength="160">
      </label>
    </div>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Description</span>
      <textarea v-model="content[lang].event_copy" class="form-control" required maxlength="600" rows="3"></textarea>
    </label>

    <h2 class="type-headline-sm text-uppercase mb-0 mt-2">Book the owner</h2>
    <div class="row g-3">
      <label class="col-md-4 d-flex flex-column gap-1">
        <span class="type-label-sm text-uppercase">Label</span>
        <input v-model="content[lang].owner_kicker" class="form-control" required maxlength="80">
      </label>
      <label class="col-md-8 d-flex flex-column gap-1">
        <span class="type-label-sm text-uppercase">Title</span>
        <input v-model="content[lang].owner_title" class="form-control" required maxlength="160">
      </label>
    </div>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Description</span>
      <textarea v-model="content[lang].owner_copy" class="form-control" required maxlength="600" rows="3"></textarea>
    </label>

    <button class="btn btn-danger btn-quote align-self-start" :disabled="saving" type="submit">{{ saving ? 'Saving...' : 'Save booking page' }}</button>
  </form>
</template>
