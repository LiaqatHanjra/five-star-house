<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'
import { formatMoney } from '../format'

const route = useRoute()
const router = useRouter()
const service = ref(null)
const windowInfo = ref({ open: '09:00', close: '19:00', minimum_hours: 2, currency: 'CAD' })
const marks = ref({})
const slots = ref([])
const cursor = ref(new Date())
const selectedDate = ref('')
const startTime = ref('')
const hours = ref(2)
const error = ref('')
const sending = ref(false)
const quote = ref(null)

const form = reactive({
  full_name: '',
  email: '',
  phone: '',
  address: '',
  event_title: '',
  message: '',
})

const kind = computed(() => {
  if (route.params.slug) return 'service'
  if (route.params.kind === 'studio-event') return 'studio_event'
  if (route.params.kind === 'owner-event') return 'owner_event'
  return 'studio_time'
})

const title = computed(() => service.value?.title || ({
  studio_time: 'Book the studio',
  studio_event: 'Book the studio for an event',
  owner_event: 'Book the owner for an event',
}[kind.value]))

const needsEvent = computed(() => kind.value === 'studio_event' || kind.value === 'owner_event')
const monthKey = computed(() => `${cursor.value.getFullYear()}-${String(cursor.value.getMonth() + 1).padStart(2, '0')}`)
const monthLabel = computed(() => cursor.value.toLocaleDateString('en-CA', { month: 'long', year: 'numeric' }))

const days = computed(() => {
  const year = cursor.value.getFullYear()
  const month = cursor.value.getMonth()
  const first = new Date(year, month, 1)
  const startPad = first.getDay()
  const count = new Date(year, month + 1, 0).getDate()
  const cells = []
  for (let i = 0; i < startPad; i += 1) cells.push({ outside: true })
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  for (let day = 1; day <= count; day += 1) {
    const date = new Date(year, month, day)
    const iso = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    const bookings = Array.isArray(marks.value[iso]) ? marks.value[iso] : []
    cells.push({
      iso,
      label: day,
      past: date < today,
      bookings,
    })
  }
  return cells
})

const maxHours = computed(() => {
  if (!startTime.value) return windowInfo.value.minimum_hours || 2
  const index = slots.value.findIndex((slot) => slot.time === startTime.value)
  let count = 0
  for (let i = index; i < slots.value.length; i += 1) {
    if (slots.value[i].booked) break
    count += 1
  }
  return Math.max(count, windowInfo.value.minimum_hours || 2)
})

const hourChoices = computed(() => {
  const min = service.value?.minimum_hours || windowInfo.value.minimum_hours || 2
  const list = []
  for (let value = min; value <= maxHours.value; value += 1) list.push(value)
  return list.length ? list : [min]
})

function shiftMonth(step) {
  cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + step, 1)
}

async function loadMonth() {
  const params = new URLSearchParams({
    month: monthKey.value,
    booking_kind: kind.value,
  })
  if (service.value?.id) params.set('service_id', service.value.id)
  const data = await api(`/api/availability?${params}`)
  windowInfo.value = data.window
  marks.value = data.marks || {}
}

async function selectDate(day) {
  if (!day.iso || day.past) return
  selectedDate.value = day.iso
  startTime.value = ''
  const params = new URLSearchParams({
    date: day.iso,
    booking_kind: kind.value,
  })
  if (service.value?.id) params.set('service_id', service.value.id)
  const data = await api(`/api/availability?${params}`)
  slots.value = data.slots || []
  windowInfo.value = data.window
}

function pickStart(slot) {
  if (!slot.can_start) return
  startTime.value = slot.time
  const min = service.value?.minimum_hours || windowInfo.value.minimum_hours || 2
  hours.value = min
}

async function refreshQuote() {
  if (!hours.value) return
  quote.value = await api('/api/quotes', {
    method: 'POST',
    body: {
      booking_kind: kind.value,
      service_id: service.value?.id || null,
      hours: hours.value,
    },
  })
}

async function submit() {
  sending.value = true
  error.value = ''
  try {
    const data = await api('/api/bookings', {
      method: 'POST',
      body: {
        booking_kind: kind.value,
        service_id: service.value?.id || null,
        booking_date: selectedDate.value,
        start_time: startTime.value,
        hours: hours.value,
        ...form,
      },
    })
    router.push(`/pay/${data.booking.reference}`)
  } catch (e) {
    error.value = e.message
  } finally {
    sending.value = false
  }
}

watch(hours, refreshQuote)
watch(monthKey, loadMonth)

onMounted(async () => {
  if (route.params.slug) {
    const data = await api(`/api/services/${route.params.slug}`)
    service.value = data.service
    hours.value = data.service.minimum_hours || 2
  }
  await loadMonth()
  if (!service.value) hours.value = windowInfo.value.minimum_hours || 2
  await refreshQuote()
})
</script>

<template>
  <div class="site-wrap py-xl py-lg-3xl d-flex flex-column gap-4">
    <div>
      <p class="type-overline text-uppercase ls-20 text-danger">MINIMUM {{ service?.minimum_hours || windowInfo.minimum_hours || 2 }} HOURS</p>
      <h1 class="type-headline-md text-uppercase">{{ title }}</h1>
      <p v-if="quote" class="type-body-md text-primary mb-0">{{ formatMoney(quote.hourly_rate, quote.currency) }} / hour · {{ formatMoney(quote.amount, quote.currency) }} for {{ hours }} hours</p>
    </div>

    <div class="row g-4">
      <div class="col-lg-5">
        <div class="bg-dark p-3 p-md-4 d-flex flex-column gap-3">
          <div class="d-flex justify-content-between align-items-center">
            <button class="btn btn-secondary btn-quote" type="button" @click="shiftMonth(-1)">Prev</button>
            <span class="type-label-md text-uppercase">{{ monthLabel }}</span>
            <button class="btn btn-secondary btn-quote" type="button" @click="shiftMonth(1)">Next</button>
          </div>
          <div class="cal-head">
            <span v-for="(name, index) in ['S', 'M', 'T', 'W', 'T', 'F', 'S']" :key="index">{{ name }}</span>
          </div>
          <div class="cal-grid">
            <span v-for="(day, index) in days" :key="index" class="cal-cell">
              <button
                class="cal-day"
                :class="{ outside: day.outside, past: day.past && !day.bookings?.length, booked: day.bookings?.length, selected: day.iso === selectedDate }"
                :disabled="day.outside || (day.past && !day.bookings?.length)"
                type="button"
                @click="selectDate(day)"
              >{{ day.label }}</button>
              <span v-if="day.bookings?.length" class="cal-tip">
                <span v-for="booking in day.bookings" :key="booking.reference" class="cal-tip-item">
                  <strong>{{ booking.name }}</strong>
                  <span>{{ booking.title }}</span>
                  <span>{{ booking.start }}–{{ booking.end }} · {{ booking.hours }} hours · {{ booking.status }}</span>
                </span>
              </span>
            </span>
          </div>
          <p class="type-body-sm text-body-tertiary mb-0">Red dates are already booked. Hover a red date to see who booked it.</p>
        </div>
      </div>

      <div class="col-lg-7 d-flex flex-column gap-4">
        <div v-if="selectedDate" class="bg-dark p-3 p-md-4">
          <h2 class="type-headline-sm text-uppercase mb-3">{{ selectedDate }} · choose a start</h2>
          <div class="hour-row">
            <span v-for="slot in slots" :key="slot.time" class="hour-wrap">
              <button
                class="hour-chip"
                :class="{ booked: slot.booked, selected: slot.time === startTime }"
                :disabled="!slot.booked && !slot.can_start"
                type="button"
                @click="pickStart(slot)"
              >{{ slot.time }}</button>
              <span v-if="slot.bookings?.length" class="cal-tip">
                <span v-for="booking in slot.bookings" :key="booking.reference" class="cal-tip-item">
                  <strong>{{ booking.name }}</strong>
                  <span>{{ booking.title }}</span>
                  <span>{{ booking.start }}–{{ booking.end }} · {{ booking.hours }} hours · {{ booking.status }}</span>
                </span>
              </span>
            </span>
          </div>
          <p class="type-body-sm text-body-tertiary mt-3 mb-0">Red times are already booked. Hover a red time to see the booking. Studio hours are {{ windowInfo.open }} to {{ windowInfo.close }}.</p>
        </div>

        <form v-if="startTime" class="bg-dark p-3 p-md-4 d-flex flex-column gap-3" @submit.prevent="submit">
          <div class="d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Hours</label>
            <select v-model.number="hours" class="form-select">
              <option v-for="choice in hourChoices" :key="choice" :value="choice">{{ choice }} hours</option>
            </select>
          </div>
          <div v-if="needsEvent" class="d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Event name</label>
            <input v-model="form.event_title" class="form-control" required>
          </div>
          <div class="row g-3">
            <div class="col-md-6 d-flex flex-column gap-1">
              <label class="type-label-sm text-uppercase">Full name</label>
              <input v-model="form.full_name" class="form-control" required>
            </div>
            <div class="col-md-6 d-flex flex-column gap-1">
              <label class="type-label-sm text-uppercase">Email</label>
              <input v-model="form.email" class="form-control" required type="email">
            </div>
            <div class="col-md-6 d-flex flex-column gap-1">
              <label class="type-label-sm text-uppercase">Phone</label>
              <input v-model="form.phone" class="form-control">
            </div>
            <div class="col-md-6 d-flex flex-column gap-1">
              <label class="type-label-sm text-uppercase">Address</label>
              <input v-model="form.address" class="form-control">
            </div>
          </div>
          <div class="d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">Notes</label>
            <textarea v-model="form.message" class="form-control" rows="4"></textarea>
          </div>
          <p v-if="error" class="type-body-sm text-danger mb-0">{{ error }}</p>
          <button class="btn btn-danger btn-submit" :disabled="sending" type="submit">
            {{ sending ? 'Saving booking...' : 'Continue to payment' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
