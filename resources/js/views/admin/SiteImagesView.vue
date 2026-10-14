<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../../api'

const route = useRoute()
const images = ref([])
const editing = ref(null)
const creating = ref(false)
const message = ref('')
const error = ref('')
const saving = ref(false)
const file = ref(null)
const pageLang = ref('en')
const pageCopy = reactive({
  en: { heading: '', description: '', tags: '', button: '', button_secondary: '' },
  fr: { heading: '', description: '', tags: '', button: '', button_secondary: '' },
})

const form = reactive({
  page: 'home',
  slot: 'gallery',
  title: '',
  category: '',
  caption: '',
  alt: '',
  image_url: '',
  col_class: 'col-md-4',
  aspect: 'aspect-4x5',
  sort_order: 0,
  is_active: true,
})

const page = computed(() => route.meta.page)
const heading = computed(() => (page.value === 'home' ? 'HOME IMAGES' : 'BOOKING IMAGES'))
const preview = computed(() => (file.value ? URL.createObjectURL(file.value) : form.image_url))

function resetForm(image = null) {
  file.value = null
  Object.assign(form, {
    page: page.value,
    slot: image?.slot || (page.value === 'home' ? 'gallery' : 'sidebar'),
    title: image?.title || '',
    category: image?.category || '',
    caption: image?.caption || '',
    alt: image?.alt || '',
    image_url: image?.image_url || '',
    col_class: image?.col_class || 'col-md-4',
    aspect: image?.aspect || 'aspect-4x5',
    sort_order: image?.sort_order ?? images.value.length + 1,
    is_active: image ? image.is_active : true,
  })
}

function tagText(tags) {
  return Array.isArray(tags) ? tags.join('\n') : ''
}

function tagList(text) {
  return String(text || '').split(/\r?\n/).map((tag) => tag.trim()).filter(Boolean)
}

function fillPage(content) {
  for (const lang of ['en', 'fr']) {
    const text = content?.[lang] || {}
    pageCopy[lang].heading = text.heading || ''
    pageCopy[lang].description = text.description || ''
    pageCopy[lang].tags = tagText(text.tags)
    pageCopy[lang].button = text.button || ''
    pageCopy[lang].button_secondary = text.button_secondary || ''
  }
}

async function load() {
  creating.value = false
  editing.value = null
  const requests = [api(`/api/admin/site-images?page=${page.value}`)]
  if (page.value === 'home') requests.push(api('/api/admin/home-page'))
  const [imageData, pageData] = await Promise.all(requests)
  images.value = imageData.images
  if (pageData) fillPage(pageData.content)
}

async function savePage() {
  saving.value = true
  message.value = ''
  error.value = ''
  try {
    const data = await api('/api/admin/home-page', {
      method: 'POST',
      body: {
        en: { ...pageCopy.en, tags: tagList(pageCopy.en.tags) },
        fr: { ...pageCopy.fr, tags: tagList(pageCopy.fr.tags) },
      },
    })
    fillPage(data.content)
    message.value = 'Home page saved.'
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

function startCreate() {
  editing.value = null
  creating.value = true
  resetForm()
  form.slot = 'gallery'
}

function edit(image) {
  creating.value = false
  editing.value = image
  resetForm(image)
}

function onFile(event) {
  file.value = event.target.files?.[0] || null
}

function payload() {
  const body = new FormData()
  Object.entries(form).forEach(([key, value]) => {
    body.append(key, key === 'is_active' ? (value ? '1' : '0') : (value ?? ''))
  })
  if (file.value) body.append('image', file.value)
  return body
}

async function save() {
  saving.value = true
  message.value = ''
  error.value = ''
  try {
    const path = creating.value
      ? '/api/admin/site-images'
      : `/api/admin/site-images/${editing.value.id}`
    await api(path, { method: 'POST', body: payload(), isForm: true })
    message.value = creating.value ? 'Image added.' : 'Image saved.'
    await load()
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

async function remove(image) {
  if (!window.confirm('Remove this gallery image?')) return
  error.value = ''
  try {
    await api(`/api/admin/site-images/${image.id}`, { method: 'DELETE' })
    message.value = 'Image removed.'
    await load()
  } catch (e) {
    error.value = e.message
  }
}

onMounted(load)
watch(page, load)
</script>

<template>
  <div class="d-flex flex-column gap-4">
    <div class="d-flex justify-content-between align-items-end gap-3">
      <div>
        <p class="type-overline text-uppercase ls-20 text-danger">CONTENT</p>
        <h1 class="type-headline-md text-uppercase">{{ heading }}</h1>
      </div>
      <button v-if="page === 'home'" class="btn btn-danger btn-quote" type="button" @click="startCreate">Add work</button>
    </div>
    <p v-if="message" class="type-body-sm text-primary mb-0">{{ message }}</p>
    <p v-if="error" class="type-body-sm text-danger mb-0">{{ error }}</p>

    <form v-if="page === 'home'" class="admin-card d-flex flex-column gap-3" @submit.prevent="savePage">
      <h2 class="type-headline-sm text-uppercase">Home page intro</h2>
      <p class="type-body-sm text-body-secondary mb-0">This heading, description, tags, and buttons appear at the top of the public home page. Edit English and French separately. Put each line of the heading, and each tag, on its own line.</p>
      <div class="d-flex gap-2">
        <button class="btn btn-quote" :class="pageLang === 'en' ? 'btn-danger' : 'btn-secondary'" type="button" @click="pageLang = 'en'">English</button>
        <button class="btn btn-quote" :class="pageLang === 'fr' ? 'btn-danger' : 'btn-secondary'" type="button" @click="pageLang = 'fr'">Français</button>
      </div>
      <label class="d-flex flex-column gap-1">
        <span class="type-label-sm text-uppercase">Main heading</span>
        <textarea v-model="pageCopy[pageLang].heading" class="form-control" required maxlength="240" rows="3"></textarea>
      </label>
      <label class="d-flex flex-column gap-1">
        <span class="type-label-sm text-uppercase">Description</span>
        <textarea v-model="pageCopy[pageLang].description" class="form-control" required maxlength="800" rows="4"></textarea>
      </label>
      <label class="d-flex flex-column gap-1">
        <span class="type-label-sm text-uppercase">Tags</span>
        <textarea v-model="pageCopy[pageLang].tags" class="form-control" required rows="4"></textarea>
      </label>
      <div class="row g-3">
        <label class="col-md-6 d-flex flex-column gap-1">
          <span class="type-label-sm text-uppercase">Primary button</span>
          <input v-model="pageCopy[pageLang].button" class="form-control" required maxlength="60">
        </label>
        <label class="col-md-6 d-flex flex-column gap-1">
          <span class="type-label-sm text-uppercase">Secondary button</span>
          <input v-model="pageCopy[pageLang].button_secondary" class="form-control" required maxlength="60">
        </label>
      </div>
      <button class="btn btn-danger btn-quote align-self-start" :disabled="saving" type="submit">{{ saving ? 'Saving...' : 'Save page intro' }}</button>
    </form>

    <div class="admin-grid">
      <div class="d-flex flex-column gap-2">
        <button v-for="image in images" :key="image.id" class="admin-card text-start border-0" type="button" @click="edit(image)">
          <img class="thumb mb-2" :alt="image.alt || image.title" :src="image.image_url">
          <div class="type-label-sm text-uppercase">{{ image.slot }} · {{ image.title || 'Untitled' }}</div>
          <div class="type-body-sm text-body-tertiary">{{ image.is_active ? 'Visible' : 'Hidden' }}</div>
        </button>
      </div>

      <form v-if="editing || creating" class="admin-card d-flex flex-column gap-3" @submit.prevent="save">
        <h2 class="type-headline-sm text-uppercase">{{ creating ? 'New gallery image' : 'Edit image' }}</h2>
        <img v-if="preview" class="thumb" alt="" :src="preview">
        <div class="row g-3">
          <div class="col-md-6 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Title</label>
            <input v-model="form.title" class="form-control" type="text">
          </div>
          <div class="col-md-6 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Label</label>
            <input v-model="form.category" class="form-control" type="text">
          </div>
          <div class="col-12 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Caption</label>
            <textarea v-model="form.caption" class="form-control" rows="3"></textarea>
          </div>
          <div class="col-md-6 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Alt text</label>
            <input v-model="form.alt" class="form-control" type="text">
          </div>
          <div class="col-md-3 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Sort</label>
            <input v-model.number="form.sort_order" class="form-control" min="0" type="number">
          </div>
          <div class="col-md-3 d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Status</label>
            <select v-model="form.is_active" class="form-select">
              <option :value="true">Visible</option>
              <option :value="false">Hidden</option>
            </select>
          </div>
          <template v-if="form.slot === 'gallery'">
            <div class="col-md-6 d-flex flex-column gap-1">
              <label class="type-label-sm text-uppercase">Column width</label>
              <select v-model="form.col_class" class="form-select">
                <option value="col-md-3">Narrow</option>
                <option value="col-md-4">Third</option>
                <option value="col-md-5">Medium</option>
                <option value="col-md-7">Wide</option>
                <option value="col-md-8">Extra wide</option>
              </select>
            </div>
            <div class="col-md-6 d-flex flex-column gap-1">
              <label class="type-label-sm text-uppercase">Shape</label>
              <select v-model="form.aspect" class="form-select">
                <option value="aspect-4x5">Portrait</option>
                <option value="aspect-video">Landscape</option>
                <option value="aspect-square aspect-md-auto">Square</option>
                <option value="aspect-video aspect-md-auto">Wide film</option>
              </select>
            </div>
          </template>
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
          <button class="btn btn-danger btn-quote" :disabled="saving" type="submit">{{ saving ? 'Saving...' : 'Save image' }}</button>
          <button v-if="editing && editing.slot === 'gallery'" class="btn btn-secondary btn-quote" type="button" @click="remove(editing)">Delete</button>
        </div>
      </form>
      <div v-else class="admin-card type-body-md text-body-secondary">Select an image to edit it.</div>
    </div>
  </div>
</template>
