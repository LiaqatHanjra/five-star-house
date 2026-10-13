<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { locale, setLocale, t } from '../i18n'
import SiteBrand from './SiteBrand.vue'

const route = useRoute()
const header = ref(null)
const open = ref(false)

function active(path) {
  return route.path === path
}

function syncHeight() {
  if (!header.value) return
  document.documentElement.style.setProperty('--header-h', `${header.value.offsetHeight}px`)
}

function closeMenu() {
  open.value = false
}

function onPointerDown(event) {
  if (!header.value?.contains(event.target)) closeMenu()
}

function onKeydown(event) {
  if (event.key === 'Escape') closeMenu()
}

function onResize() {
  syncHeight()
  if (window.innerWidth >= 768) closeMenu()
}

onMounted(() => {
  syncHeight()
  window.addEventListener('resize', onResize)
  document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', onResize)
  document.removeEventListener('keydown', onKeydown)
  document.removeEventListener('pointerdown', onPointerDown)
})

watch(() => route.fullPath, closeMenu)

watch(open, async (value) => {
  document.removeEventListener('pointerdown', onPointerDown)
  if (!value) return
  await nextTick()
  document.addEventListener('pointerdown', onPointerDown)
})

watch(locale, async () => {
  await nextTick()
  syncHeight()
})
</script>

<template>
  <header ref="header" class="site-header fixed-top border-bottom">
    <div class="site-bleed header-bar">
      <RouterLink class="brand-link" to="/">
        <SiteBrand />
      </RouterLink>
      <button
        class="nav-toggle"
        type="button"
        :aria-expanded="open"
        aria-controls="site-menu"
        :aria-label="open ? t('nav.close') : t('nav.open')"
        @click="open = !open"
      >
        <span class="material-symbols-outlined" aria-hidden="true">{{ open ? 'close' : 'menu' }}</span>
      </button>
      <nav id="site-menu" class="site-nav" :class="{ 'is-open': open }" :aria-label="t('nav.menu')">
        <RouterLink class="nav-link" :class="{ active: active('/') }" to="/" @click="closeMenu">{{ t('nav.home') }}</RouterLink>
        <RouterLink class="nav-link" :class="{ active: active('/services') }" to="/services" @click="closeMenu">{{ t('nav.services') }}</RouterLink>
        <RouterLink class="nav-link" :class="{ active: active('/book') }" to="/book" @click="closeMenu">{{ t('nav.book') }}</RouterLink>
        <div class="lang-switch" role="group" :aria-label="t('nav.language')">
          <button type="button" :aria-pressed="locale === 'en'" :class="{ active: locale === 'en' }" @click="setLocale('en')">EN</button>
          <button type="button" :aria-pressed="locale === 'fr'" :class="{ active: locale === 'fr' }" @click="setLocale('fr')">FR</button>
        </div>
      </nav>
    </div>
  </header>
</template>
