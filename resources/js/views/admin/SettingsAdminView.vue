<script setup>
import { onMounted, reactive, ref } from 'vue'
import { api } from '../../api'

const form = reactive({
  owner_name: '',
  owner_email: 'studiofivestarmtl@gmail.com',
  owner_email_secondary: 'vladimirphilogene@gmail.com',
  phone: '',
  address: '',
  instagram_url: '',
  youtube_url: '',
  vimeo_url: '',
  open_time: '09:00',
  close_time: '19:00',
  minimum_hours: 1,
  studio_minimum_hours: 1,
  currency: 'CAD',
  studio_hourly_rate: 75,
  event_hourly_rate: 250,
  owner_hourly_rate: 300,
  seo_title_en: '',
  seo_title_fr: '',
  seo_description_en: '',
  seo_description_fr: '',
})
const message = ref('')
const error = ref('')
const saving = ref(false)

onMounted(async () => {
  const data = await api('/api/admin/settings')
  Object.assign(form, data.settings)
  form.minimum_hours = Number(form.minimum_hours)
  form.studio_minimum_hours = Number(form.studio_minimum_hours)
})

async function save() {
  saving.value = true
  message.value = ''
  error.value = ''
  try {
    const data = await api('/api/admin/settings', {
      method: 'POST',
      body: {
        ...form,
        minimum_hours: Number(form.minimum_hours),
        studio_minimum_hours: Number(form.studio_minimum_hours),
      },
    })
    Object.assign(form, data.settings)
    message.value = 'Settings saved.'
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form class="d-flex flex-column gap-3 max-w-xl" @submit.prevent="save">
    <div>
      <p class="type-overline text-uppercase ls-20 text-danger">STUDIO DESK</p>
      <h1 class="type-headline-md text-uppercase">SITE SETTINGS</h1>
    </div>
    <p v-if="message" class="type-body-sm text-primary mb-0">{{ message }}</p>
    <p v-if="error" class="type-body-sm text-danger mb-0">{{ error }}</p>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Owner name</span>
      <input v-model="form.owner_name" class="form-control" required>
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Main email</span>
      <input v-model="form.owner_email" class="form-control" required type="email">
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Secondary email</span>
      <input v-model="form.owner_email_secondary" class="form-control" type="email">
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Phone</span>
      <input v-model="form.phone" class="form-control">
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Address</span>
      <input v-model="form.address" class="form-control">
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Instagram URL</span>
      <input v-model="form.instagram_url" class="form-control" placeholder="https://">
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">YouTube URL</span>
      <input v-model="form.youtube_url" class="form-control" placeholder="https://">
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Vimeo URL</span>
      <input v-model="form.vimeo_url" class="form-control" placeholder="https://">
    </label>
    <div class="row g-3">
      <div class="col-md-4 d-flex flex-column gap-1">
        <label class="type-label-sm text-uppercase">Opens</label>
        <input v-model="form.open_time" class="form-control" required type="time">
      </div>
      <div class="col-md-4 d-flex flex-column gap-1">
        <label class="type-label-sm text-uppercase">Closes</label>
        <input v-model="form.close_time" class="form-control" required type="time">
      </div>
      <div class="col-md-4 d-flex flex-column gap-1">
        <label class="type-label-sm text-uppercase">Minimum hours</label>
        <input v-model.number="form.minimum_hours" class="form-control" min="1" max="12" required type="number">
      </div>
    </div>
    <div class="row g-3">
      <div class="col-md-4 d-flex flex-column gap-1">
        <label class="type-label-sm text-uppercase">Studio rental minimum hours</label>
        <input v-model.number="form.studio_minimum_hours" class="form-control" min="1" max="12" required type="number">
      </div>
    </div>
    <div class="row g-3">
      <div class="col-md-3 d-flex flex-column gap-1">
        <label class="type-label-sm text-uppercase">Currency</label>
        <input v-model="form.currency" class="form-control" maxlength="3" required>
      </div>
      <div class="col-md-3 d-flex flex-column gap-1">
        <label class="type-label-sm text-uppercase">Studio rental / hour</label>
        <input v-model="form.studio_hourly_rate" class="form-control" min="0" required step="0.01" type="number">
      </div>
      <div class="col-md-3 d-flex flex-column gap-1">
        <label class="type-label-sm text-uppercase">Event / hour</label>
        <input v-model="form.event_hourly_rate" class="form-control" min="0" required step="0.01" type="number">
      </div>
      <div class="col-md-3 d-flex flex-column gap-1">
        <label class="type-label-sm text-uppercase">Owner / hour</label>
        <input v-model="form.owner_hourly_rate" class="form-control" min="0" required step="0.01" type="number">
      </div>
    </div>
    <p class="type-body-sm text-body-secondary mb-0">
      Customers choose Stripe or PayPal at checkout. Add the Stripe and PayPal keys in the server environment to accept online payments.
    </p>
    <div class="d-flex flex-column gap-1 mt-2">
      <h2 class="type-headline-sm text-uppercase mb-0">Search engines</h2>
      <p class="type-body-sm text-body-secondary mb-0">The title and description Google shows for the home page. Leave a field empty to use the built-in text.</p>
    </div>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Home title, English</span>
      <input v-model="form.seo_title_en" class="form-control" maxlength="70">
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Home description, English</span>
      <textarea v-model="form.seo_description_en" class="form-control" maxlength="160" rows="3"></textarea>
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Home title, French</span>
      <input v-model="form.seo_title_fr" class="form-control" maxlength="70">
    </label>
    <label class="d-flex flex-column gap-1">
      <span class="type-label-sm text-uppercase">Home description, French</span>
      <textarea v-model="form.seo_description_fr" class="form-control" maxlength="160" rows="3"></textarea>
    </label>
    <button class="btn btn-danger btn-quote align-self-start" :disabled="saving" type="submit">{{ saving ? 'Saving...' : 'Save settings' }}</button>
  </form>
</template>
