<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../../api'

const route = useRoute()
const router = useRouter()
const status = ref(null)
const message = ref('')
const error = ref('')
const busy = ref(false)

const syncedThrough = computed(() => {
  const value = status.value?.sync_through
  if (!value) return '—'
  return new Date(`${value}T12:00:00`).toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  })
})

const lastSynced = computed(() => relativeTime(status.value?.last_synced_at))

function relativeTime(iso) {
  if (!iso) return 'Not yet'
  const minutes = Math.round((Date.now() - new Date(iso).getTime()) / 60000)
  if (minutes < 1) return 'just now'
  if (minutes < 60) return minutes === 1 ? 'a minute ago' : `${minutes} minutes ago`
  const hours = Math.round(minutes / 60)
  if (hours < 24) return hours === 1 ? 'an hour ago' : `${hours} hours ago`
  const days = Math.round(hours / 24)
  if (days === 1) return 'a day ago'
  if (days < 30) return `${days} days ago`
  return new Date(iso).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
}

async function load() {
  status.value = await api('/api/admin/calendar')
}

onMounted(async () => {
  if (route.query.google === 'connected') {
    message.value = 'Google Calendar is connected. Bookings are on FIVE STAR CALENDAR.'
  } else if (route.query.google === 'error') {
    error.value = String(route.query.message || 'Google Calendar could not be connected.')
  }
  if (route.query.google) {
    router.replace({ path: '/admin/calendar' })
  }
  try {
    await load()
  } catch (e) {
    error.value = e.message
  }
})

async function connect() {
  busy.value = true
  error.value = ''
  try {
    const data = await api('/api/admin/calendar/connect', { method: 'POST' })
    window.location.assign(data.url)
  } catch (e) {
    error.value = e.message
    busy.value = false
  }
}

async function refresh() {
  busy.value = true
  error.value = ''
  message.value = ''
  try {
    const data = await api('/api/admin/calendar/sync', { method: 'POST' })
    status.value = { ...status.value, ...data }
    message.value = 'Calendar refreshed.'
  } catch (e) {
    error.value = e.message
  } finally {
    busy.value = false
  }
}

async function disconnect() {
  if (!window.confirm('Disconnect Google Calendar? Events already on FIVE STAR CALENDAR stay there. The site stops adding bookings and reading busy times.')) {
    return
  }
  busy.value = true
  error.value = ''
  message.value = ''
  try {
    await api('/api/admin/calendar', { method: 'DELETE' })
    status.value = {
      ...status.value,
      connected: false,
      email: null,
      calendar_name: null,
      sync_through: null,
      last_synced_at: null,
    }
    message.value = 'Google Calendar disconnected.'
  } catch (e) {
    error.value = e.message
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="d-flex flex-column gap-3">
    <div>
      <p class="type-overline text-uppercase ls-20 text-danger">STUDIO DESK</p>
      <h1 class="type-headline-md text-uppercase">GOOGLE CALENDAR</h1>
    </div>

    <p v-if="message" class="type-body-sm text-primary mb-0">{{ message }}</p>
    <p v-if="error" class="type-body-sm text-danger mb-0">{{ error }}</p>

    <section v-if="status" class="sync-card">
      <div class="d-flex justify-content-between align-items-start gap-3">
        <h2>Direct sync</h2>
        <span class="sync-state">{{ status.connected ? 'Connected' : 'Not connected' }}</span>
      </div>

      <div class="sync-google">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.4h6.5a5.6 5.6 0 0 1-2.4 3.7v3h3.9c2.3-2.1 3.5-5.2 3.5-8.8z"/>
          <path fill="#34A853" d="M12 24c3.2 0 5.9-1.1 7.9-2.9l-3.9-3c-1.1.7-2.4 1.2-4 1.2-3.1 0-5.7-2.1-6.6-4.9H1.4v3.1A12 12 0 0 0 12 24z"/>
          <path fill="#FBBC05" d="M5.4 14.4A7.2 7.2 0 0 1 5 12c0-.8.1-1.6.4-2.4V6.5H1.4A12 12 0 0 0 0 12c0 1.9.5 3.8 1.4 5.5l4-3.1z"/>
          <path fill="#EA4335" d="M12 4.8c1.7 0 3.3.6 4.5 1.8l3.4-3.4A12 12 0 0 0 1.4 6.5l4 3.1C6.3 6.8 8.9 4.8 12 4.8z"/>
        </svg>
        <span>Google</span>
      </div>

      <dl v-if="status.connected" class="sync-meta">
        <div>
          <dt>Email</dt>
          <dd>{{ status.email }}</dd>
        </div>
        <div>
          <dt>Calendar name</dt>
          <dd>{{ status.calendar_name }}</dd>
        </div>
        <div>
          <dt>Event synced through</dt>
          <dd>{{ syncedThrough }}</dd>
        </div>
        <div>
          <dt>Last synced</dt>
          <dd>{{ lastSynced }}</dd>
        </div>
      </dl>

      <div v-else class="sync-setup">
        <p>Connect the studio Google account. The site uses a calendar named FIVE STAR CALENDAR. Each person who books is invited by Google Calendar at the email on their reservation, and busy times on that calendar show as unavailable.</p>
        <p v-if="!status.configured">Add <code>GOOGLE_CLIENT_ID</code> and <code>GOOGLE_CLIENT_SECRET</code> to the server environment, enable the Google Calendar API, and set this authorized redirect URI:</p>
        <p v-else>Google will warn that this app is not verified. Click Continue, then Allow calendar access. Only FIVE STAR CALENDAR is used for bookings and availability.</p>
        <code class="sync-uri">{{ status.redirect_uri }}</code>
      </div>

      <div class="sync-actions">
        <template v-if="status.connected">
          <button class="sync-refresh" type="button" :disabled="busy" @click="refresh">Refresh sync</button>
          <button class="sync-disconnect" type="button" :disabled="busy" @click="disconnect">Disconnect</button>
        </template>
        <button v-else class="sync-refresh" type="button" :disabled="busy || !status.configured" @click="connect">Connect Google Calendar</button>
      </div>
    </section>
  </div>
</template>
