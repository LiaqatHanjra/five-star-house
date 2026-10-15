<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'
import { formatMoney } from '../format'
import { dateLocale, hourUnit, locale, statusLabel, t, tp } from '../i18n'
import { pageSeo } from '../seo'

const props = defineProps({
  embedded: { type: Boolean, default: false },
})

const emit = defineEmits(['unit-change'])

const route = useRoute()
const router = useRouter()
const service = ref(null)
const pageContent = ref(null)
const windowInfo = ref({ open: '09:00', close: '19:00', minimum_hours: 1, currency: 'CAD' })
const marks = ref({})
const freeHours = ref({})
const slots = ref([])
const cursor = ref(new Date())
const selectedDate = ref('')
const startTime = ref('')
const hours = ref(1)
const error = ref('')
const sending = ref(false)
const quote = ref(null)
const selectedUnit = ref('')

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

const title = computed(() => {
  if (service.value?.title) return service.value.title
  const text = pageContent.value?.[locale.value] || {}
  const key = {
    studio_time: 'studio_title',
    studio_event: 'event_title',
    owner_event: 'owner_title',
  }[kind.value]
  return text[key] || t(`book.titles.${kind.value}`)
})

const needsEvent = computed(() => kind.value === 'studio_event' || kind.value === 'owner_event')
const isService = computed(() => kind.value === 'service')
const byHour = computed(() => !isService.value || selectedUnit.value === 'hour')
const showCalendar = computed(() => !isService.value || selectedUnit.value !== '')
const backTo = computed(() => (route.params.slug && !props.embedded ? `/services/${route.params.slug}` : '/book'))
const monthKey = computed(() => `${cursor.value.getFullYear()}-${String(cursor.value.getMonth() + 1).padStart(2, '0')}`)
const monthLabel = computed(() => cursor.value.toLocaleDateString(dateLocale(), { month: 'long', year: 'numeric' }))
const weekdays = computed(() => t('calendar.weekdays'))
const weekLabels = computed(() => t('calendar.weeks'))

function clockLabel(value) {
  const raw = String(value || '').slice(0, 5)
  if (locale.value === 'fr') return raw
  const [hourText, minuteText] = raw.split(':')
  let hour = Number(hourText)
  const minute = Number(minuteText || 0)
  if (Number.isNaN(hour)) return raw
  const suffix = hour >= 12 ? 'PM' : 'AM'
  hour = hour % 12 || 12
  if (!minute) return `${hour}${suffix}`
  return `${hour}:${String(minute).padStart(2, '0')} ${suffix}`
}

function bookingNotes(bookings) {
  return bookings.slice(0, 2).map((booking) => ({
    name: booking.status === 'busy' ? '' : String(booking.name || '').split(' ').slice(0, 2).join(' '),
    range: `${clockLabel(booking.start)} - ${clockLabel(booking.end)}`,
  }))
}

const weeks = computed(() => {
  const year = cursor.value.getFullYear()
  const month = cursor.value.getMonth()
  const first = new Date(year, month, 1)
  const startPad = (first.getDay() + 6) % 7
  const count = new Date(year, month + 1, 0).getDate()
  const cells = []
  for (let i = 0; i < startPad; i += 1) cells.push({ outside: true })
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  for (let day = 1; day <= count; day += 1) {
    const date = new Date(year, month, day)
    const iso = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    const bookings = Array.isArray(marks.value[iso]) ? marks.value[iso] : []
    const open = Number(freeHours.value[iso] ?? 0)
    cells.push({
      iso,
      label: day,
      past: date < today,
      bookings,
      notes: bookingNotes(bookings),
      freeHours: open,
      full: bookings.length > 0 && open === 0,
    })
  }
  while (cells.length % 7 !== 0) cells.push({ outside: true })
  const rows = []
  for (let index = 0; index < cells.length; index += 7) rows.push(cells.slice(index, index + 7))
  return rows
})

const rateNote = computed(() => {
  if (!quote.value) return ''
  const min = quote.value.minimum_hours || windowInfo.value.minimum_hours || 1
  if (isService.value && (selectedUnit.value === 'day' || selectedUnit.value === 'project')) return quoteLine.value
  return tp('calendar.rate', min, {
    rate: formatMoney(quote.value.hourly_rate, quote.value.currency),
  })
})

const maxHours = computed(() => {
  if (!startTime.value) return windowInfo.value.minimum_hours || 1
  const index = slots.value.findIndex((slot) => slot.time === startTime.value)
  let count = 0
  for (let i = index; i < slots.value.length; i += 1) {
    if (slots.value[i].booked) break
    count += 1
  }
  return Math.max(count, windowInfo.value.minimum_hours || 1)
})

const quoteLine = computed(() => {
  if (!quote.value) return ''
  const total = formatMoney(quote.value.amount, quote.value.currency)
  if (isService.value && selectedUnit.value === 'day') return t('wizard.dayQuote', { total })
  if (isService.value && selectedUnit.value === 'project') return t('wizard.projectQuote', { total })
  return tp('wizard.quote', hours.value, {
    rate: formatMoney(quote.value.hourly_rate, quote.value.currency),
    total,
  })
})

const hourChoices = computed(() => {
  const min = service.value?.minimum_hours || windowInfo.value.minimum_hours || 1
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
  freeHours.value = data.free || {}
}

async function selectDate(day) {
  if (!day.iso || day.past) return
  if (!byHour.value && day.bookings?.length) return
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
  if (!byHour.value) {
    if (slots.value.some((slot) => slot.booked)) {
      selectedDate.value = ''
      return
    }
    startTime.value = windowInfo.value.open
  }
}

function pickStart(slot) {
  if (!slot.can_start) return
  startTime.value = slot.time
  const min = service.value?.minimum_hours || windowInfo.value.minimum_hours || 1
  hours.value = min
}

async function refreshQuote() {
  if (!hours.value) return
  if (isService.value && !service.value?.id) return
  if (isService.value && !selectedUnit.value) {
    quote.value = null
    return
  }
  const unit = isService.value ? selectedUnit.value : 'hour'
  quote.value = await api('/api/quotes', {
    method: 'POST',
    body: {
      booking_kind: kind.value,
      service_id: service.value?.id || null,
      hours: unit === 'hour' ? hours.value : 1,
      charge_unit: unit,
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
        start_time: byHour.value ? startTime.value : windowInfo.value.open,
        hours: byHour.value ? hours.value : 1,
        charge_unit: isService.value ? selectedUnit.value : 'hour',
        ...form,
      },
    })
    router.push({
      path: `/pay/${data.booking.reference}`,
      state: { returnTo: route.fullPath },
    })
  } catch (e) {
    error.value = e.message
  } finally {
    sending.value = false
  }
}

watch(hours, refreshQuote)
watch(selectedUnit, () => {
  selectedDate.value = ''
  startTime.value = ''
  slots.value = []
  emit('unit-change', selectedUnit.value)
  refreshQuote()
})
watch(monthKey, loadMonth)
watch([title, service], () => {
  const name = title.value
  if (!name || String(name).startsWith('book.titles.')) return
  pageSeo.value = {
    title: `${name} — Five Star House`,
    description: service.value?.summary || '',
    image: service.value?.image_url || '',
  }
}, { immediate: true })

onUnmounted(() => {
  if (!props.embedded) pageSeo.value = null
})

onMounted(async () => {
  api('/api/studio').then((data) => {
    pageContent.value = data.content || null
  }).catch(() => {})
  if (route.params.slug) {
    const data = await api(`/api/services/${route.params.slug}`)
    service.value = data.service
    hours.value = data.service.minimum_hours || 1
  }
  await loadMonth()
  if (!service.value) hours.value = windowInfo.value.minimum_hours || 1
  await refreshQuote()
})
</script>

<template>
  <div :class="embedded ? 'd-flex flex-column gap-4' : 'site-wrap py-xl py-lg-3xl d-flex flex-column gap-4'">
    <div v-if="!embedded" class="d-flex flex-column align-items-start gap-3">
      <RouterLink class="btn btn-secondary btn-quote" :to="backTo">{{ t('wizard.back') }}</RouterLink>
      <div>
        <h1 class="type-headline-md text-uppercase">{{ title }}</h1>
        <p class="type-overline text-uppercase ls-20 text-danger mb-0">{{ tp('wizard.minimum', service?.minimum_hours || windowInfo.minimum_hours || 1) }}</p>
        <p v-if="quoteLine" class="type-body-md text-primary mb-0">{{ quoteLine }}</p>
      </div>
    </div>
    <div v-if="isService" class="d-flex flex-column gap-1" :class="{ 'mt-5 pt-lg-3': embedded }">
      <label class="type-label-sm text-uppercase" for="service-rate">{{ t('detail.rate') }}</label>
      <select id="service-rate" v-model="selectedUnit" class="form-select">
        <option value="">{{ t('detail.chooseRate') }}</option>
        <option value="hour">{{ t('detail.perHour') }}</option>
        <option value="day">{{ t('detail.perDay') }}</option>
        <option value="project">{{ t('detail.perProject') }}</option>
      </select>
    </div>
    <p v-if="embedded && quoteLine" class="type-body-md text-primary mb-0">{{ quoteLine }}</p>

    <div v-if="showCalendar" class="d-flex flex-column gap-4">
      <div class="booking-cal">
        <div class="booking-cal-kicker">
          <span class="booking-cal-mark"></span>
          <span>{{ t('calendar.title') }}</span>
        </div>
        <div class="booking-cal-nav">
          <button class="booking-cal-arrow" type="button" :aria-label="t('calendar.prev')" @click="shiftMonth(-1)">‹</button>
          <strong>{{ monthLabel }}</strong>
          <button class="booking-cal-arrow" type="button" :aria-label="t('calendar.next')" @click="shiftMonth(1)">›</button>
        </div>
        <div class="booking-cal-scroll">
          <div class="booking-cal-sheet">
            <div class="booking-cal-head">
              <span>{{ t('calendar.date') }}</span>
              <span v-for="(name, index) in weekdays" :key="index">{{ name }}</span>
            </div>
            <div v-for="(week, weekIndex) in weeks" :key="weekIndex" class="booking-cal-row">
              <div class="cal-week">{{ weekLabels[weekIndex] }}</div>
              <div v-for="(day, dayIndex) in week" :key="dayIndex" class="cal-cell" :class="{ 'is-blank': day.outside }">
                <button
                  v-if="!day.outside"
                  class="cal-day"
                  :class="{ past: day.past && !day.bookings?.length, 'booked-full': day.full, 'booked-part': day.bookings?.length && !day.full, selected: day.iso === selectedDate }"
                  :disabled="day.past && !day.bookings?.length"
                  type="button"
                  @click="selectDate(day)"
                >
                  <span class="cal-num">{{ day.label }}</span>
                  <span v-if="day.bookings?.length && !day.full" class="cal-flag"></span>
                  <span v-for="(note, noteIndex) in day.notes" :key="noteIndex" class="cal-note">
                    <span v-if="note.name">{{ note.name }}</span>
                    <span>{{ note.range }}</span>
                  </span>
                </button>
                <span v-if="day.bookings?.length" class="cal-tip" :class="day.freeHours ? 'is-open' : 'is-full'">
                  <span class="cal-tip-item" :class="day.freeHours ? 'cal-tip-open' : 'cal-tip-full'">
                    {{ day.freeHours ? tp('wizard.slotAvailable', day.freeHours) : t('wizard.slotFull') }}
                  </span>
                </span>
              </div>
            </div>
          </div>
        </div>
        <p v-if="rateNote" class="booking-cal-rate">{{ rateNote }}</p>
        <p class="booking-cal-hint">{{ t('wizard.bookedHint') }}</p>
      </div>

      <div class="d-flex flex-column gap-4">
        <div v-if="selectedDate && byHour" class="bg-dark p-3 p-md-4">
          <h2 class="type-headline-sm text-uppercase mb-3">{{ t('wizard.chooseStart', { date: selectedDate }) }}</h2>
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
                  <template v-if="booking.status === 'busy'">
                    <strong>{{ t('wizard.unavailableName') }}</strong>
                    <span>{{ t('wizard.unavailable') }}</span>
                  </template>
                  <template v-else>
                    <strong>{{ booking.name }}</strong>
                    <span>{{ booking.title }}</span>
                    <span>{{ t('wizard.bookingLine', { start: booking.start, end: booking.end, n: booking.hours, unit: hourUnit(booking.hours), status: statusLabel(booking.status) }) }}</span>
                  </template>
                </span>
              </span>
            </span>
          </div>
          <p class="type-body-sm text-body-tertiary mt-3 mb-0">{{ t('wizard.slotHint', { open: windowInfo.open, close: windowInfo.close }) }}</p>
        </div>

        <form v-if="byHour ? startTime : selectedDate" class="bg-dark p-3 p-md-4 d-flex flex-column gap-3" @submit.prevent="submit">
          <div v-if="byHour" class="d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">{{ t('wizard.hours') }}</label>
            <select v-model.number="hours" class="form-select">
              <option v-for="choice in hourChoices" :key="choice" :value="choice">{{ tp('wizard.hoursOption', choice) }}</option>
            </select>
          </div>
          <div v-if="needsEvent" class="d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">{{ t('wizard.eventName') }}</label>
            <input v-model="form.event_title" class="form-control" required>
          </div>
          <div class="row g-3">
            <div class="col-md-6 d-flex flex-column gap-1">
              <label class="type-label-sm text-uppercase">{{ t('wizard.fullName') }}</label>
              <input v-model="form.full_name" class="form-control" required>
            </div>
            <div class="col-md-6 d-flex flex-column gap-1">
              <label class="type-label-sm text-uppercase">{{ t('wizard.email') }}</label>
              <input v-model="form.email" class="form-control" required type="email">
            </div>
            <div class="col-md-6 d-flex flex-column gap-1">
              <label class="type-label-sm text-uppercase">{{ t('wizard.phone') }}</label>
              <input v-model="form.phone" class="form-control">
            </div>
            <div class="col-md-6 d-flex flex-column gap-1">
              <label class="type-label-sm text-uppercase">{{ t('wizard.address') }}</label>
              <input v-model="form.address" class="form-control">
            </div>
          </div>
          <div class="d-flex flex-column gap-1">
            <label class="type-label-sm text-uppercase">{{ t('wizard.notes') }}</label>
            <textarea v-model="form.message" class="form-control" rows="4"></textarea>
          </div>
          <p v-if="error" class="type-body-sm text-danger mb-0">{{ error }}</p>
          <button class="btn btn-danger btn-submit" :disabled="sending" type="submit">
            {{ sending ? t('wizard.saving') : t('wizard.continue') }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
