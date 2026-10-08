<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { api } from '../../api'
import { formatCharge } from '../../format'

const services = ref([])
const banner = ref(null)
const editing = ref(null)
const creating = ref(false)
const message = ref('')
const error = ref('')
const saving = ref(false)
const file = ref(null)
const bannerFile = ref(null)

const bannerForm = reactive({
  page: 'services',
  slot: 'hero',
  title: '',
  category: '',
  alt: '',
  image_url: '',
  sort_order: 0,
  is_active: true,
  caption: '',
  col_class: '',
  aspect: '',
})

const emptyService = () => ({
  number: '',
  title: '',
  slug: '',
  summary: '',
  description: '',
  eyebrow: '',
  badge: '',
  charge: 0,
  currency: 'CAD',
  charge_unit: 'hour',
  minimum_hours: 2,
  image_url: '',
  tags: '',
  cta_label: 'REQUEST QUOTE',
  image_side: 'left',
  sort_order: services.value.length + 1,
  is_active: true,
})

const form = reactive(emptyService())
const preview = computed(() => (file.value ? URL.createObjectURL(file.value) : form.image_url))

function fillBanner(image) {
  Object.assign(bannerForm, {
    page: 'services',
    slot: 'hero',
    title: image?.title || '',
    category: image?.category || '',
    alt: image?.alt || '',
    image_url: image?.image_url || '',
    sort_order: image?.sort_order || 0,
    is_active: image ? image.is_active : true,
    caption: image?.caption || '',
    col_class: '',
    aspect: '',
  })
}

function fillService(service) {
  Object.assign(form, {
    ...service,
    tags: (service.tags || []).join(', '),
    charge: Number(service.charge),
  })
}

async function load() {
  const [serviceData, imageData] = await Promise.all([
    api('/api/admin/services'),
    api('/api/admin/site-images?page=services'),
  ])
  services.value = serviceData.services
  banner.value = imageData.images.find((image) => image.slot === 'hero') || null
  fillBanner(banner.value)
}

function startCreate() {
  editing.value = null
  creating.value = true
  file.value = null
  Object.assign(form, emptyService())
}

function edit(service) {
  creating.value = false
  editing.value = service
  file.value = null
  fillService(service)
}

function onFile(event) {
  file.value = event.target.files?.[0] || null
}

function servicePayload() {
  const body = new FormData()
  Object.entries(form).forEach(([key, value]) => {
    body.append(key, key === 'is_active' ? (value ? '1' : '0') : (value ?? ''))
  })
  if (file.value) body.append('image', file.value)
  return body
}

async function saveService() {
  saving.value = true
  message.value = ''
  error.value = ''
  try {
    const path = creating.value ? '/api/admin/services' : `/api/admin/services/${editing.value.id}`
    await api(path, { method: 'POST', body: servicePayload(), isForm: true })
    message.value = creating.value ? 'Service added.' : 'Service saved.'
    creating.value = false
    editing.value = null
    await load()
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

async function removeService() {
  if (!editing.value || !window.confirm('Delete this service?')) return
  await api(`/api/admin/services/${editing.value.id}`, { method: 'DELETE' })
  message.value = 'Service removed.'
  editing.value = null
  await load()
}

async function saveBanner() {
  saving.value = true
  message.value = ''
  error.value = ''
  try {
    const body = new FormData()
    Object.entries(bannerForm).forEach(([key, value]) => {
      body.append(key, key === 'is_active' ? (value ? '1' : '0') : (value ?? ''))
    })
    if (bannerFile.value) body.append('image', bannerFile.value)
    const path = banner.value ? `/api/admin/site-images/${banner.value.id}` : '/api/admin/site-images'
    await api(path, { method: 'POST', body, isForm: true })
    bannerFile.value = null
    message.value = 'Services banner saved.'
    await load()
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="d-flex flex-column gap-4">
    <div class="d-flex justify-content-between align-items-end gap-3">
      <div>
        <p class="type-overline text-uppercase ls-20 text-danger">CONTENT</p>
        <h1 class="type-headline-md text-uppercase">SERVICES &amp; CHARGES</h1>
      </div>
      <button class="btn btn-danger btn-quote" type="button" @click="startCreate">Add service</button>
    </div>
    <p v-if="message" class="type-body-sm text-primary mb-0">{{ message }}</p>
    <p v-if="error" class="type-body-sm text-danger mb-0">{{ error }}</p>

    <form class="admin-card d-flex flex-column gap-3" @submit.prevent="saveBanner">
      <h2 class="type-headline-sm text-uppercase">Services page image</h2>
      <div class="row g-3">
        <div class="col-md-4">
          <img v-if="bannerForm.image_url" class="thumb" alt="" :src="bannerForm.image_url">
        </div>
        <div class="col-md-8 row g-3">
          <div class="col-md-6 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Overlay label</label>
            <input v-model="bannerForm.category" class="form-control">
          </div>
          <div class="col-md-6 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Location label</label>
            <input v-model="bannerForm.title" class="form-control">
          </div>
          <div class="col-12 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Image URL</label>
            <input v-model="bannerForm.image_url" class="form-control" type="text">
          </div>
          <div class="col-12 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Upload replacement</label>
            <input class="form-control" accept="image/png,image/jpeg,image/webp" type="file" @change="bannerFile = $event.target.files?.[0] || null">
          </div>
        </div>
      </div>
      <button class="btn btn-secondary btn-quote align-self-start" :disabled="saving" type="submit">Save banner</button>
    </form>

    <div class="admin-grid">
      <div class="d-flex flex-column gap-2">
        <button v-for="service in services" :key="service.id" class="admin-card text-start border-0" type="button" @click="edit(service)">
          <div class="type-label-sm text-danger">{{ service.number }}</div>
          <div class="type-headline-sm text-uppercase">{{ service.title }}</div>
          <div class="type-body-sm text-primary">{{ formatCharge(service) }}</div>
        </button>
      </div>

      <form v-if="editing || creating" class="admin-card d-flex flex-column gap-3" @submit.prevent="saveService">
        <h2 class="type-headline-sm text-uppercase">{{ creating ? 'New service' : form.title }}</h2>
        <img v-if="preview" class="thumb" alt="" :src="preview">
        <div class="row g-3">
          <div class="col-md-2 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">No.</label>
            <input v-model="form.number" class="form-control" required>
          </div>
          <div class="col-md-6 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Title</label>
            <input v-model="form.title" class="form-control" required>
          </div>
          <div class="col-md-4 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Slug</label>
            <input v-model="form.slug" class="form-control" placeholder="auto from title">
          </div>
          <div class="col-md-4 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Hourly rate</label>
            <input v-model="form.charge" class="form-control" min="0" required step="0.01" type="number">
          </div>
          <div class="col-md-2 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Currency</label>
            <input v-model="form.currency" class="form-control" maxlength="3" required>
          </div>
          <div class="col-md-3 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Charged</label>
            <select v-model="form.charge_unit" class="form-select">
              <option value="hour">Per hour</option>
              <option value="project">Per project</option>
              <option value="day">Per day</option>
            </select>
          </div>
          <div class="col-md-3 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Minimum hours</label>
            <input v-model.number="form.minimum_hours" class="form-control" min="2" type="number">
          </div>
          <div class="col-md-3 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Status</label>
            <select v-model="form.is_active" class="form-select">
              <option :value="true">Visible</option>
              <option :value="false">Hidden</option>
            </select>
          </div>
          <div class="col-md-6 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Eyebrow</label>
            <input v-model="form.eyebrow" class="form-control">
          </div>
          <div class="col-md-6 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Image badge</label>
            <input v-model="form.badge" class="form-control">
          </div>
          <div class="col-12 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Home summary</label>
            <textarea v-model="form.summary" class="form-control" required rows="3"></textarea>
          </div>
          <div class="col-12 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Full description</label>
            <textarea v-model="form.description" class="form-control" required rows="4"></textarea>
          </div>
          <div class="col-12 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Tags, separated by commas</label>
            <input v-model="form.tags" class="form-control">
          </div>
          <div class="col-md-4 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Button label</label>
            <input v-model="form.cta_label" class="form-control" required>
          </div>
          <div class="col-md-4 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Image side</label>
            <select v-model="form.image_side" class="form-select">
              <option value="left">Left</option>
              <option value="right">Right</option>
            </select>
          </div>
          <div class="col-md-4 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Sort</label>
            <input v-model.number="form.sort_order" class="form-control" min="0" type="number">
          </div>
          <div class="col-12 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Image URL</label>
            <input v-model="form.image_url" class="form-control" type="text">
          </div>
          <div class="col-12 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Or upload a file</label>
            <input class="form-control" accept="image/png,image/jpeg,image/webp" type="file" @change="onFile">
          </div>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-danger btn-quote" :disabled="saving" type="submit">{{ saving ? 'Saving...' : 'Save service' }}</button>
          <button v-if="editing" class="btn btn-secondary btn-quote" type="button" @click="removeService">Delete</button>
        </div>
      </form>
      <div v-else class="admin-card type-body-md text-body-secondary">Select a service to change its copy, photo, or charge.</div>
    </div>
  </div>
</template>
