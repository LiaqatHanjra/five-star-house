<script setup>
import logoUrl from '../assets/Five-star-logo.png'

defineProps({
  compact: { type: Boolean, default: false },
})
</script>

<template>
  <span class="site-brand">
    <img class="brand-logo" :class="{ 'brand-logo-sm': compact }" :src="logoUrl" alt="" width="44" height="40">
    <span class="brand-name text-uppercase ls-wider" :class="compact ? 'type-label-sm' : 'type-headline-sm'">FIVE STAR HOUSE</span>
  </span>
</template>
