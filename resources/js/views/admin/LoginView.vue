<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../../api'

const route = useRoute()
const router = useRouter()
const email = ref('admin@fivestarhouse.com')
const password = ref('')
const error = ref('')
const sending = ref(false)

async function submit() {
  sending.value = true
  error.value = ''
  try {
    const data = await api('/api/admin/login', {
      method: 'POST',
      body: { email: email.value, password: password.value },
    })
    localStorage.setItem('admin_token', data.token)
    localStorage.setItem('admin_user', JSON.stringify(data.user))
    router.push(route.query.redirect || '/admin')
  } catch (e) {
    error.value = e.message
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div class="login-wrap">
    <form class="login-card d-flex flex-column gap-3" @submit.prevent="submit">
      <div class="d-flex align-items-center gap-2">
        <span class="sq-25 bg-danger d-inline-block"></span>
        <span class="type-headline-sm text-uppercase ls-wider">FIVE STAR HOUSE</span>
      </div>
      <div>
        <p class="type-overline text-uppercase ls-20 text-danger mb-2">STUDIO DESK</p>
        <h1 class="type-headline-md text-uppercase">ADMIN LOGIN</h1>
      </div>
      <label class="d-flex flex-column gap-1">
        <span class="type-label-sm text-uppercase ls-wider">Email</span>
        <input v-model="email" class="form-control" required type="email">
      </label>
      <label class="d-flex flex-column gap-1">
        <span class="type-label-sm text-uppercase ls-wider">Password</span>
        <input v-model="password" class="form-control" required type="password">
      </label>
      <p v-if="error" class="type-body-sm text-danger mb-0">{{ error }}</p>
      <button class="btn btn-danger btn-pad" :disabled="sending" type="submit">
        {{ sending ? 'SIGNING IN...' : 'ENTER DESK' }}
      </button>
      <RouterLink class="type-label-sm text-uppercase ls-wider text-body-tertiary" to="/">Back to site</RouterLink>
    </form>
  </div>
</template>
