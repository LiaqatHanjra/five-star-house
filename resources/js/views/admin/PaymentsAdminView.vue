<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../../api'
import { formatMoney } from '../../format'

const payments = ref([])

onMounted(async () => {
  const data = await api('/api/admin/payments')
  payments.value = data.payments
})
</script>

<template>
  <div class="d-flex flex-column gap-3">
    <div>
      <p class="type-overline text-uppercase ls-20 text-danger">STUDIO DESK</p>
      <h1 class="type-headline-md text-uppercase">PAYMENTS</h1>
    </div>
    <p v-if="!payments.length" class="type-body-md text-body-secondary">No payments yet.</p>
    <article v-for="payment in payments" :key="payment.id" class="admin-card">
      <div class="d-flex flex-wrap justify-content-between gap-2">
        <div>
          <h2 class="type-headline-sm text-uppercase mb-1">{{ payment.reference }}</h2>
          <p class="type-body-sm text-body-secondary mb-0">{{ payment.booking?.full_name }} · {{ payment.booking?.email }}</p>
        </div>
        <span class="status-pill">{{ payment.status }}</span>
      </div>
      <p class="type-body-md mb-0 mt-2">{{ formatMoney(payment.amount, payment.currency) }} · {{ payment.booking?.project_type }}</p>
      <p class="type-body-sm text-body-tertiary mb-0">{{ payment.booking?.booking_date }} · {{ payment.booking?.reference }}</p>
    </article>
  </div>
</template>
