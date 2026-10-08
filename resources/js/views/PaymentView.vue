<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'
import { formatMoney } from '../format'

const route = useRoute()
const router = useRouter()
const booking = ref(null)
const payment = ref(null)
const error = ref('')
const paying = ref(false)

onMounted(async () => {
  try {
    const data = await api(`/api/bookings/${route.params.reference}`)
    booking.value = data.booking
    payment.value = data.payment
  } catch (e) {
    error.value = 'This booking could not be found.'
  }
})

async function pay() {
  paying.value = true
  error.value = ''
  try {
    const result = await api(`/api/bookings/${route.params.reference}/pay`, { method: 'POST' })
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
</script>

<template>
  <section class="site-wrap py-xl py-lg-3xl">
    <div class="max-w-xl">
      <p class="type-overline text-uppercase ls-20 text-danger">PAYMENT</p>
      <h1 class="type-headline-md text-uppercase mb-3">CONFIRM AND PAY</h1>
      <p v-if="route.query.cancelled" class="type-body-md text-body-secondary">Checkout was cancelled. Your booking is still pending; you can try again below.</p>
      <p v-if="error" class="type-body-md text-danger">{{ error }}</p>
      <div v-if="booking" class="bg-dark p-4 d-flex flex-column gap-2">
        <p class="type-body-md mb-0">{{ booking.project_type }}</p>
        <p class="type-body-md text-body-secondary mb-0">{{ booking.booking_date }} · {{ booking.start_time }} · {{ booking.hours }} hours</p>
        <p class="type-body-md text-body-secondary mb-0">{{ booking.full_name }} · {{ booking.email }}</p>
        <p class="type-headline-sm text-primary">{{ formatMoney(booking.amount, payment?.currency || 'CAD') }}</p>
        <p class="type-body-sm text-body-tertiary">The studio records this payment with the booking. Card numbers are not stored on this site.</p>
        <button class="btn btn-danger btn-pad align-self-start" :disabled="paying || payment?.status === 'paid'" type="button" @click="pay">
          {{ paying ? 'Processing...' : 'Pay and confirm' }}
        </button>
      </div>
    </div>
  </section>
</template>
