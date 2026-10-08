<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import { formatMoney } from '../format'

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
        <p class="type-overline text-uppercase ls-20 text-danger">CONFIRMED</p>
        <h1 class="type-headline-md text-uppercase">YOU ARE BOOKED</h1>
        <p class="type-body-lg text-body-secondary">
          {{ booking.reference }} is paid. A confirmation email is on its way to {{ booking.email }} and to the studio owner.
        </p>
      </template>
      <template v-else-if="booking">
        <p class="type-overline text-uppercase ls-20 text-danger">PAYMENT PENDING</p>
        <h1 class="type-headline-md text-uppercase">YOUR BOOKING IS NOT CONFIRMED</h1>
        <p class="type-body-lg text-body-secondary">We could not confirm payment for {{ booking.reference }}. Please return to checkout to try again.</p>
        <RouterLink class="btn btn-danger btn-pad align-self-start" :to="`/pay/${booking.reference}`">Return to payment</RouterLink>
      </template>
      <div v-if="booking" class="bg-dark p-4">
        <p class="type-body-md mb-1">{{ booking.project_type }}</p>
        <p class="type-body-md text-body-secondary mb-1">{{ booking.booking_date }} · {{ booking.start_time }} to {{ booking.end_time }}</p>
        <p class="type-headline-sm text-primary mb-0">{{ formatMoney(booking.amount, payment?.currency || 'CAD') }}</p>
      </div>
      <RouterLink class="btn btn-secondary btn-pad align-self-start" to="/">Back to the studio</RouterLink>
    </div>
  </section>
</template>
