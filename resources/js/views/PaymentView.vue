<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'
import { formatMoney } from '../format'
import { hourUnit, t } from '../i18n'

const route = useRoute()
const router = useRouter()
const booking = ref(null)
const payment = ref(null)
const method = ref('')
const error = ref('')
const paying = ref(false)

onMounted(async () => {
  try {
    const data = await api(`/api/bookings/${route.params.reference}`)
    booking.value = data.booking
    payment.value = data.payment
  } catch (e) {
    error.value = t('payment.missing')
  }
})

async function pay() {
  paying.value = true
  error.value = ''
  try {
    const result = await api(`/api/bookings/${route.params.reference}/pay`, {
      method: 'POST',
      body: { method: method.value },
    })
    if (result.paid) {
      router.push(`/booked/${route.params.reference}`)
      return
    }
    window.location.assign(result.checkout_url)
  } catch (e) {
    error.value = e.message
  } finally {
    paying.value = false
  }
}

function fallbackPath() {
  const kind = booking.value?.booking_kind
  if (kind === 'service' && booking.value?.service_slug) {
    return `/book/service/${booking.value.service_slug}`
  }
  return {
    studio_event: '/book/studio-event',
    owner_event: '/book/owner-event',
    studio_time: '/book/studio-time',
  }[kind] || '/book'
}

function isAppPath(path) {
  return typeof path === 'string' && path.startsWith('/') && !path.startsWith('/pay')
}

function goBack() {
  const previous = window.history.state?.back
  if (isAppPath(previous)) {
    router.back()
    return
  }
  const returnTo = window.history.state?.returnTo
  if (isAppPath(returnTo)) {
    router.push(returnTo)
    return
  }
  router.push(fallbackPath())
}
</script>

<template>
  <section class="site-wrap py-xl py-lg-3xl">
    <div class="max-w-xl d-flex flex-column align-items-start gap-3">
      <button class="btn btn-secondary btn-quote" type="button" @click="goBack">{{ t('payment.back') }}</button>
      <p class="type-overline text-uppercase ls-20 text-danger mb-0">{{ t('payment.kicker') }}</p>
      <h1 class="type-headline-md text-uppercase mb-0">{{ t('payment.title') }}</h1>
      <p v-if="route.query.cancelled" class="type-body-md text-body-secondary">{{ t('payment.cancelled') }}</p>
      <p v-if="error" class="type-body-md text-danger">{{ error }}</p>
      <div v-if="booking" class="bg-dark p-4 d-flex flex-column gap-2">
        <p class="type-body-md mb-0">{{ booking.project_type }}</p>
        <p class="type-body-md text-body-secondary mb-0">{{ t('payment.hoursLine', { date: booking.booking_date, time: booking.start_time, n: booking.hours, unit: hourUnit(booking.hours) }) }}</p>
        <p class="type-body-md text-body-secondary mb-0">{{ booking.full_name }} · {{ booking.email }}</p>
        <p class="type-headline-sm text-primary">{{ formatMoney(booking.amount, payment?.currency || 'CAD') }}</p>
        <div class="d-flex flex-column gap-2" role="radiogroup" :aria-label="t('payment.choose')">
          <label class="pay-method">
            <input v-model="method" name="payment-method" type="radio" value="stripe">
            <svg class="pay-brand stripe" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.591-7.305z"/>
            </svg>
            <span class="type-label-md text-uppercase">{{ t('payment.stripe') }}</span>
          </label>
          <label class="pay-method">
            <input v-model="method" name="payment-method" type="radio" value="paypal">
            <svg class="pay-brand paypal" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a3.35 3.35 0 0 0-.607-.541c-.013.076-.026.175-.041.254-.59 3.025-2.566 6.082-8.558 6.082H9.83c-.524 0-.968.382-1.05.9l-1.12 7.106-.316 2.003a.337.337 0 0 0 .333.39h3.48a.563.563 0 0 0 .556-.478l.023-.12.44-2.79.028-.152a.563.563 0 0 1 .556-.478h.35c2.273 0 4.052-.923 4.572-3.59.217-1.116.104-2.048-.47-2.7a2.24 2.24 0 0 0-.63-.486z"/>
            </svg>
            <span class="type-label-md text-uppercase">{{ t('payment.paypal') }}</span>
          </label>
        </div>
        <p class="type-body-sm text-body-tertiary mb-0">{{ t('payment.cardNote') }}</p>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <button class="btn btn-danger btn-pad" :disabled="paying || !method || payment?.status === 'paid'" type="button" @click="pay">
            {{ paying ? t('payment.processing') : t('payment.pay') }}
          </button>
          <button class="btn btn-secondary btn-pad" type="button" @click="goBack">{{ t('payment.cancel') }}</button>
        </div>
      </div>
    </div>
  </section>
</template>
