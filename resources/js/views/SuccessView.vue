<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import { formatMoney } from '../format'
import { t } from '../i18n'

const route = useRoute()
const booking = ref(null)
const payment = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    if (route.query.session_id) {
      const data = await api(`/api/bookings/${route.params.reference}/payment/verify?session_id=${encodeURIComponent(route.query.session_id)}`)
      booking.value = data.booking
      payment.value = data.payment
      return
    }

    if (route.query.token) {
      const data = await api(`/api/bookings/${route.params.reference}/payment/verify?paypal_order=${encodeURIComponent(route.query.token)}`)
      booking.value = data.booking
      payment.value = data.payment
      return
    }

    const data = await api(`/api/bookings/${route.params.reference}`)
    booking.value = data.booking
    payment.value = data.payment
  } catch (e) {
    error.value = e.message
    try {
      const data = await api(`/api/bookings/${route.params.reference}`)
      booking.value = data.booking
      payment.value = data.payment
    } catch (lookupError) {
      error.value = lookupError.message
    }
  }
})
</script>

<template>
  <section class="site-wrap py-xl py-lg-3xl">
    <div class="max-w-xl d-flex flex-column gap-3">
      <p v-if="error" class="type-body-md text-danger">{{ error }}</p>
      <template v-if="booking?.payment_status === 'paid'">
        <p class="type-overline text-uppercase ls-20 text-danger">{{ t('success.confirmedKicker') }}</p>
        <h1 class="type-headline-md text-uppercase">{{ t('success.confirmedTitle') }}</h1>
        <p class="type-body-lg text-body-secondary">
          {{ t('success.confirmedCopy', { reference: booking.reference, email: booking.email }) }}
        </p>
      </template>
      <template v-else-if="booking">
        <p class="type-overline text-uppercase ls-20 text-danger">{{ t('success.pendingKicker') }}</p>
        <h1 class="type-headline-md text-uppercase">{{ t('success.pendingTitle') }}</h1>
        <p class="type-body-lg text-body-secondary">{{ t('success.pendingCopy', { reference: booking.reference }) }}</p>
        <RouterLink class="btn btn-danger btn-pad align-self-start" :to="`/pay/${booking.reference}`">{{ t('success.returnPay') }}</RouterLink>
      </template>
      <div v-if="booking" class="bg-dark p-4">
        <p class="type-body-md mb-1">{{ booking.project_type }}</p>
        <p class="type-body-md text-body-secondary mb-1">{{ t('success.timeLine', { date: booking.booking_date, start: booking.start_time, end: booking.end_time }) }}</p>
        <p class="type-headline-sm text-primary mb-0">{{ formatMoney(booking.amount, payment?.currency || 'CAD') }}</p>
      </div>
      <RouterLink class="btn btn-secondary btn-pad align-self-start" to="/">{{ t('success.back') }}</RouterLink>
    </div>
  </section>
</template>
