export function formatCharge(service) {
  if (!service) return ''
  const formatted = new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: service.currency || 'CAD',
    maximumFractionDigits: 0,
  }).format(Number(service.charge || 0))

  if (service.charge_unit === 'hour') return `${formatted} / hour`
  if (service.charge_unit === 'day') return `${formatted} / day`
  return formatted
}

export function formatMoney(amount, currency = 'CAD') {
  return new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: currency || 'CAD',
    maximumFractionDigits: 0,
  }).format(Number(amount || 0))
}
