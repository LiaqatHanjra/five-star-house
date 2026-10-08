<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../../api'

const inquiries = ref([])
const budgets = {
  under5k: 'Under $5,000 CAD',
  '5k-15k': '$5,000 – $15,000 CAD',
  '15k-30k': '$15,000 – $30,000 CAD',
  '30k-plus': '$30,000+ CAD',
}

async function load() {
  const data = await api('/api/admin/inquiries')
  inquiries.value = data.inquiries
}

async function setStatus(inquiry, status) {
  const data = await api(`/api/admin/inquiries/${inquiry.id}/status`, {
    method: 'POST',
    body: { status },
  })
  inquiry.status = data.inquiry.status
}

onMounted(load)
</script>

<template>
  <div class="d-flex flex-column gap-4">
    <div>
      <p class="type-overline text-uppercase ls-20 text-danger">BOOKINGS</p>
      <h1 class="type-headline-md text-uppercase">INQUIRIES</h1>
    </div>
    <div v-if="!inquiries.length" class="admin-card type-body-md text-body-secondary">No project inquiries yet.</div>
    <article v-for="inquiry in inquiries" :key="inquiry.id" class="admin-card d-flex flex-column gap-2">
      <div class="d-flex flex-wrap justify-content-between gap-2">
        <div>
          <h2 class="type-headline-sm text-uppercase mb-1">{{ inquiry.full_name }}</h2>
          <p class="type-body-sm text-body-secondary mb-0">{{ inquiry.email }} <span v-if="inquiry.phone">· {{ inquiry.phone }}</span></p>
        </div>
        <span class="status-pill">{{ inquiry.status }}</span>
      </div>
      <p class="type-body-md mb-0">{{ inquiry.project_type }} · {{ inquiry.timeline }} · {{ budgets[inquiry.budget] || inquiry.budget }}</p>
      <p class="type-body-md text-body-secondary mb-0">{{ inquiry.message }}</p>
      <div class="d-flex gap-2">
        <button class="btn btn-secondary btn-quote" type="button" @click="setStatus(inquiry, 'reviewed')">Mark reviewed</button>
        <button class="btn btn-secondary btn-quote" type="button" @click="setStatus(inquiry, 'archived')">Archive</button>
      </div>
    </article>
  </div>
</template>
