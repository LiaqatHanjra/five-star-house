import { ref } from 'vue'
import { locale, t } from './i18n'

export const pageSeo = ref(null)

let settingsRequest

function settings() {
  if (!settingsRequest) {
    settingsRequest = fetch('/api/seo', { headers: { Accept: 'application/json' } })
      .then((response) => (response.ok ? response.json() : {}))
      .catch(() => ({}))
  }
  return settingsRequest
}

function setMeta(attribute, key, content) {
  const selector = `meta[${attribute}="${key}"]`
  let element = document.head.querySelector(selector)
  if (!content) {
    element?.remove()
    return
  }
  if (!element) {
    element = document.createElement('meta')
    element.setAttribute(attribute, key)
    document.head.appendChild(element)
  }
  element.setAttribute('content', content)
}

function setCanonical(href) {
  let element = document.head.querySelector('link[rel="canonical"]')
  if (!element) {
    element = document.createElement('link')
    element.setAttribute('rel', 'canonical')
    document.head.appendChild(element)
  }
  element.setAttribute('href', href)
}

function pageKey(route) {
  const key = route.meta.titleKey || ''
  return key.startsWith('meta.') ? key.slice(5) : ''
}

export async function applyDocumentSeo(route) {
  const saved = await settings()
  const language = locale.value === 'fr' ? 'fr' : 'en'
  const key = pageKey(route)
  const custom = pageSeo.value
  const indexable = !route.path.startsWith('/admin')
    && !route.path.startsWith('/pay')
    && !route.path.startsWith('/booked')
  const savedTitle = route.path === '/' ? String(saved[`title_${language}`] || '').trim() : ''
  const savedDescription = route.path === '/' ? String(saved[`description_${language}`] || '').trim() : ''
  let title = custom?.title || savedTitle
  if (!title) {
    if (route.meta.titleKey === 'meta.home') title = t('meta.home')
    else if (route.meta.titleKey) title = `${t(route.meta.titleKey)} — Five Star House`
    else if (route.meta.title) title = `${route.meta.title} — Five Star House`
    else title = t('meta.home')
  }
  const fallbackDescription = key ? t(`meta.descriptions.${key}`) : t('meta.descriptions.home')
  const description = String(custom?.description || savedDescription || fallbackDescription).slice(0, 180)
  const url = `${window.location.origin}${route.path === '/' ? '/' : route.path}`
  const image = custom?.image || `${window.location.origin}/apple-touch-icon.png`

  document.documentElement.lang = language
  document.title = title
  setMeta('name', 'description', description)
  setMeta('name', 'robots', indexable ? 'index,follow' : 'noindex,nofollow')
  setCanonical(url)
  setMeta('property', 'og:site_name', 'Five Star House')
  setMeta('property', 'og:type', 'website')
  setMeta('property', 'og:title', title)
  setMeta('property', 'og:description', description)
  setMeta('property', 'og:url', url)
  setMeta('property', 'og:image', image)
  setMeta('property', 'og:locale', language === 'fr' ? 'fr_CA' : 'en_CA')
  setMeta('name', 'twitter:card', 'summary_large_image')
  setMeta('name', 'twitter:title', title)
  setMeta('name', 'twitter:description', description)
  setMeta('name', 'twitter:image', image)
}
