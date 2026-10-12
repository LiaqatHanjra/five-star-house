<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { locale, setLocale, t } from '../i18n'

const route = useRoute()
const bar = ref(null)

function active(path) {
  return route.path === path
}

function syncHeight() {
  if (!bar.value) return
  document.documentElement.style.setProperty('--header-h', `${bar.value.offsetHeight}px`)
}

onMounted(() => {
  syncHeight()
  window.addEventListener('resize', syncHeight)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', syncHeight)
})

watch(locale, async () => {
  await nextTick()
  syncHeight()
})
</script>

<template>
  <header class="site-header fixed-top border-bottom">
    <div ref="bar" class="site-wrap header-bar">
      <RouterLink class="brand-link d-flex align-items-center gap-2" to="/">
        <span class="brand-mark sq-25 bg-danger d-inline-block" aria-hidden="true"></span>
        <span class="brand-name type-headline-sm text-uppercase ls-wider">FIVE STAR HOUSE</span>
      </RouterLink>
      <nav class="site-nav d-flex" :aria-label="t('nav.menu')">
        <RouterLink class="nav-link" :class="{ active: active('/') }" to="/">{{ t('nav.home') }}</RouterLink>
        <RouterLink class="nav-link" :class="{ active: active('/services') }" to="/services">{{ t('nav.services') }}</RouterLink>
        <RouterLink class="nav-link" :class="{ active: active('/book') }" to="/book">{{ t('nav.book') }}</RouterLink>
        <div class="lang-switch" role="group" :aria-label="t('nav.language')">
          <button type="button" :aria-pressed="locale === 'en'" :class="{ active: locale === 'en' }" @click="setLocale('en')">EN</button>
          <button type="button" :aria-pressed="locale === 'fr'" :class="{ active: locale === 'fr' }" @click="setLocale('fr')">FR</button>
        </div>
      </nav>
    </div>
  </header>
</template>
