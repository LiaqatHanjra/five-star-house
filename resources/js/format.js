import { dateLocale, locale, t } from './i18n'

export function formatCharge(service) {
  if (!service) return ''
  locale.value
  const formatted = new Intl.NumberFormat(dateLocale(), {
    style: 'currency',
    currency: service.currency || 'CAD',
    maximumFractionDigits: 0,
  }).format(Number(service.charge || 0))

  if (service.charge_unit === 'hour') return `${formatted}${t('units.perHour')}`
  if (service.charge_unit === 'day') return `${formatted}${t('units.perDay')}`
  return formatted
}

export function formatMoney(amount, currency = 'CAD') {
  locale.value
  return new Intl.NumberFormat(dateLocale(), {
    style: 'currency',
    currency: currency || 'CAD',
    maximumFractionDigits: 0,
  }).format(Number(amount || 0))
}
