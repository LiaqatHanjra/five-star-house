import { ref } from 'vue'
import en from './locales/en.js'
import fr from './locales/fr.js'

const catalogs = { en, fr }

function storedLocale() {
  try {
    return localStorage.getItem('locale') === 'fr' ? 'fr' : 'en'
  } catch {
    return 'en'
  }
}

export const locale = ref(storedLocale())

if (typeof document !== 'undefined') {
  document.documentElement.lang = locale.value
}

function lookup(catalog, path) {
  return path.split('.').reduce((node, key) => {
    if (node && Object.prototype.hasOwnProperty.call(node, key)) return node[key]
    return undefined
  }, catalog)
}

export function t(key, params = {}) {
  const primary = lookup(catalogs[locale.value], key)
  const fallback = lookup(catalogs.en, key)
  const value = primary ?? fallback
  if (Array.isArray(value)) return value
  const text = typeof value === 'string' ? value : key
  return text.replace(/\{(\w+)\}/g, (_, name) => params[name] ?? '')
}

export function tp(key, count, params = {}) {
  const form = Number(count) === 1 ? 'one' : 'other'
  return t(`${key}.${form}`, { n: count, ...params })
}

export function hourUnit(count) {
  return t(Number(count) === 1 ? 'units.hour' : 'units.hours')
}

export function setLocale(code) {
  const next = code === 'fr' ? 'fr' : 'en'
  locale.value = next
  document.documentElement.lang = next
  try {
    localStorage.setItem('locale', next)
  } catch {
    // Private browsing can block storage; the choice still applies this visit.
  }
}

export function statusLabel(status) {
  const label = t(`status.${status}`)
  return label === `status.${status}` ? status : label
}

export function dateLocale() {
  return locale.value === 'fr' ? 'fr-CA' : 'en-CA'
}
