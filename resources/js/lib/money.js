/**
 * Format a number as currency using the clinic's settings.
 *
 * @param {number|string} amount
 * @param {import('@/types/settings.js').ClinicSettings} settings
 * @returns {string}
 */
export function formatMoney(amount, settings) {
  const value = Number(amount ?? 0)
  const formatted = value.toFixed(settings.currency_decimals)
  const withSeparator = formatted.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  return settings.currency_position === 'before'
    ? `${settings.currency_symbol}${withSeparator}`
    : `${withSeparator}${settings.currency_symbol}`
}
