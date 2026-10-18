/**
 * usePrescriptionFormat
 * ---------------------
 * Display helpers for prescription items. Centralised here so the
 * print view, the on-screen Show view, and any future view render
 * medicines and durations the same way.
 *
 * The helpers address common data-entry habits that produce ugly
 * output on the printed Rx:
 *
 * 1. Doctors type the strength straight into the medicine name field
 *    ("Pentonics40mg") and leave the strength field blank. `formatMedicine`
 *    detects a trailing dose token (digits + optional unit) and splits
 *    it off so the rendered line is always "Name Strength" with a space.
 *    Strength is left as-typed (English digits) — it's a pharmacological
 *    notation like "40mg" that doesn't translate to Bangla digits.
 *
 * 2. Doctors type just a number for the duration ("7") and forget the
 *    unit. `formatDuration` normalises that to "7 দিন" (7 days) so
 *    anyone reading the Rx knows the course length.
 *
 * 3. Doctors type English digits in dosage/duration ("1" / "10") even
 *    though the rest of the Rx is in Bangla. `toBanglaDigits` rewrites
 *    ASCII 0-9 to Bangla ০-৯ so the printed sheet uses one digit system.
 */

const EN_TO_BN = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯']

function toBanglaDigits(value) {
  if (value === null || value === undefined) return ''
  return String(value).replace(/[0-9]/g, (d) => EN_TO_BN[Number(d)])
}

export function usePrescriptionFormat() {
  function formatMedicine(name, strength) {
    const cleanName = (name ?? '').trim()
    const cleanStrength = (strength ?? '').trim()
    if (cleanStrength) return `${cleanName} ${cleanStrength}`
    // No separate strength — try to peel a trailing "40mg" / "10 ml" off
    // the name. Matches one or more digits followed by an optional unit
    // word (mg, ml, gm, mcg, iu, %, etc.). Strength is left in ASCII.
    const m = cleanName.match(/^(.*?)\s*(\d+(?:\.\d+)?(?:\s?(?:mg|ml|gm|mcg|iu|%|mcg\/ml))?)$/i)
    if (m && m[1].trim()) {
      return `${m[1].trim()} ${m[2].trim()}`
    }
    return cleanName
  }

  function formatDuration(duration) {
    const v = (duration ?? '').trim()
    if (!v) return ''
    // Already mentions a unit (দিন / day / week / সপ্তাহ / মাস / month) — leave it.
    if (/দিন|সপ্তাহ|মাস|day|week|month/i.test(v)) return toBanglaDigits(v)
    // Bare number → assume days, which is the most common dental Rx.
    if (/^\d+(\.\d+)?$/.test(v)) return `${toBanglaDigits(v)} দিন`
    return toBanglaDigits(v)
  }

  return { formatMedicine, formatDuration, toBanglaDigits }
}


