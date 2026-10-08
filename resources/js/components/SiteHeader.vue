<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()

function active(path) {
  return route.path === path
}
</script>

<template>
  <header class="site-header fixed-top border-bottom">
    <div class="site-wrap h-20 d-flex align-items-center justify-content-between">
      <RouterLink class="brand-link d-flex align-items-center gap-2" to="/">
        <span class="brand-mark sq-25 bg-danger d-inline-block" aria-hidden="true"></span>
        <span class="brand-name type-headline-sm text-uppercase ls-wider">FIVE STAR HOUSE</span>
      </RouterLink>
      <nav class="site-nav d-flex" aria-label="Main navigation">
        <RouterLink class="nav-link" :class="{ active: active('/') }" to="/">HOME</RouterLink>
        <RouterLink class="nav-link" :class="{ active: active('/services') }" to="/services">SERVICES</RouterLink>
        <RouterLink class="nav-link" :class="{ active: active('/book') }" to="/book">BOOK</RouterLink>
      </nav>
    </div>
  </header>
</template>
