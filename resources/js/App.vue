<script setup>
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { locale } from './i18n'
import { applyDocumentSeo, pageSeo } from './seo'

const route = useRoute()

watch(() => route.fullPath, () => {
  pageSeo.value = null
})

watch([() => route.fullPath, locale, pageSeo], () => {
  applyDocumentSeo(route)
}, { immediate: true })
</script>

<template>
  <RouterView />
</template>
