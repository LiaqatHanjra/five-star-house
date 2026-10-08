<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../../api'

const customers = ref([])

onMounted(async () => {
  const data = await api('/api/admin/customers')
  customers.value = data.customers
})
</script>

<template>
  <div class="d-flex flex-column gap-3">
    <div>
      <p class="type-overline text-uppercase ls-20 text-danger">STUDIO DESK</p>
      <h1 class="type-headline-md text-uppercase">CUSTOMERS</h1>
    </div>
    <p v-if="!customers.length" class="type-body-md text-body-secondary">No customers yet.</p>
    <article v-for="customer in customers" :key="customer.id" class="admin-card">
      <h2 class="type-headline-sm text-uppercase mb-1">{{ customer.name }}</h2>
      <p class="type-body-md mb-1">{{ customer.email }} <span v-if="customer.phone">· {{ customer.phone }}</span></p>
      <p v-if="customer.address" class="type-body-sm text-body-secondary mb-1">{{ customer.address }}</p>
      <p class="type-label-sm text-uppercase text-primary mb-0">{{ customer.bookings_count }} bookings</p>
    </article>
  </div>
</template>
