import { format } from 'date-fns'
import { toZonedTime, fromZonedTime, formatInTimeZone } from 'date-fns-tz'

/**
 * @param {Date|string} date
 * @param {string} tz IANA timezone
 * @param {string} [fmt] date-fns format
 */
export function formatInClinicTz(date, tz, fmt = 'yyyy-MM-dd HH:mm') {
  return formatInTimeZone(date, tz, fmt)
}

/**
 * Convert a UTC date into the clinic's local-time Date object (for display).
 * @param {Date|string} date
 * @param {string} tz
 */
export function toClinicLocal(date, tz) {
  return toZonedTime(date, tz)
}

/**
 * Convert a local-time Date (entered in the clinic's timezone) into UTC.
 * @param {Date|string} date
 * @param {string} tz
 */
export function fromClinicLocal(date, tz) {
  return fromZonedTime(date, tz)
}
