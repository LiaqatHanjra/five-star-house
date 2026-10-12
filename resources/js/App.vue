<script setup>
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { locale, t } from './i18n'

const route = useRoute()

watch([() => route.fullPath, locale], () => {
  document.documentElement.lang = locale.value
  if (route.meta.titleKey === 'meta.home') {
    document.title = t('meta.home')
    return
  }
  if (route.meta.titleKey) {
    document.title = `${t(route.meta.titleKey)} — Five Star House`
    return
  }
  document.title = route.meta.title
    ? `${route.meta.title} — Five Star House`
    : t('meta.home')
}, { immediate: true })
</script>

<template>
  <RouterView />
</template>
