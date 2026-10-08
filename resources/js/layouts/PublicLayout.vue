<script setup>
import SiteFooter from '../components/SiteFooter.vue'
import SiteHeader from '../components/SiteHeader.vue'
</script>

<template>
  <div class="min-vh-100 d-flex flex-column">
    <SiteHeader />
    <main class="w-100 flex-grow-1 pt-20">
      <RouterView />
    </main>
    <SiteFooter />
  </div>
</template>
