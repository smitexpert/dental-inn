/**
 * Dental procedure catalogue
 * ---------------------------
 * Subset of ADA CDT codes relevant to charting — diagnostic, preventive,
 * restorative, endodontic, surgical, and adjunctive. Used by the
 * ToothChartPicker when selecting a procedure for a marked tooth.
 *
 * Each entry has a code (CDT), label (human-readable), and a list of valid
 * surfaces (where applicable). Surfaces are blank for procedures that
 * don't take a surface (e.g. extractions, endo).
 */

export const DENTAL_PROCEDURES = [
  // Diagnostic
  { code: 'D0120', label: 'Periodic oral evaluation', surfaces: [] },
  { code: 'D0140', label: 'Limited oral evaluation', surfaces: [] },
  { code: 'D0150', label: 'Comprehensive oral evaluation', surfaces: [] },
  { code: 'D0210', label: 'Full mouth X-ray series', surfaces: [] },
  { code: 'D0220', label: 'Periapical X-ray, first film', surfaces: [] },
  { code: 'D0230', label: 'Periapical X-ray, each additional', surfaces: [] },
  { code: 'D0274', label: 'Bitewing X-rays (4 films)', surfaces: [] },
  { code: 'D0330', label: 'Panoramic film', surfaces: [] },

  // Preventive
  { code: 'D1110', label: 'Adult prophylaxis (cleaning)', surfaces: [] },
  { code: 'D1120', label: 'Child prophylaxis', surfaces: [] },
  { code: 'D1206', label: 'Topical fluoride varnish', surfaces: [] },
  { code: 'D1351', label: 'Sealant, per tooth', surfaces: ['occlusal'] },

  // Restorative
  { code: 'D2140', label: 'Amalgam, one surface', surfaces: ['occlusal', 'mesial', 'distal', 'buccal', 'lingual'] },
  { code: 'D2150', label: 'Amalgam, two surfaces', surfaces: ['occlusal', 'mesial', 'distal', 'buccal', 'lingual'] },
  { code: 'D2160', label: 'Amalgam, three surfaces', surfaces: ['occlusal', 'mesial', 'distal', 'buccal', 'lingual'] },
  { code: 'D2161', label: 'Amalgam, four or more surfaces', surfaces: ['occlusal', 'mesial', 'distal', 'buccal', 'lingual'] },
  { code: 'D2330', label: 'Resin, one surface (anterior)', surfaces: ['mesial', 'distal', 'buccal', 'lingual', 'incisal'] },
  { code: 'D2331', label: 'Resin, two surfaces (anterior)', surfaces: ['mesial', 'distal', 'buccal', 'lingual', 'incisal'] },
  { code: 'D2332', label: 'Resin, three surfaces (anterior)', surfaces: ['mesial', 'distal', 'buccal', 'lingual', 'incisal'] },
  { code: 'D2335', label: 'Resin, four+ surfaces (anterior)', surfaces: ['mesial', 'distal', 'buccal', 'lingual', 'incisal'] },
  { code: 'D2391', label: 'Resin, one surface (posterior)', surfaces: ['occlusal', 'mesial', 'distal', 'buccal', 'lingual'] },
  { code: 'D2392', label: 'Resin, two surfaces (posterior)', surfaces: ['occlusal', 'mesial', 'distal', 'buccal', 'lingual'] },
  { code: 'D2393', label: 'Resin, three surfaces (posterior)', surfaces: ['occlusal', 'mesial', 'distal', 'buccal', 'lingual'] },
  { code: 'D2394', label: 'Resin, four+ surfaces (posterior)', surfaces: ['occlusal', 'mesial', 'distal', 'buccal', 'lingual'] },
  { code: 'D2740', label: 'Crown, porcelain/ceramic', surfaces: [] },
  { code: 'D2750', label: 'Crown, porcelain fused to metal', surfaces: [] },
  { code: 'D2752', label: 'Crown, porcelain fused to noble metal', surfaces: [] },
  { code: 'D2790', label: 'Crown, full cast metal', surfaces: [] },

  // Endodontic
  { code: 'D3220', label: 'Pulpotomy', surfaces: [] },
  { code: 'D3310', label: 'Root canal, anterior', surfaces: [] },
  { code: 'D3320', label: 'Root canal, bicuspid', surfaces: [] },
  { code: 'D3330', label: 'Root canal, molar', surfaces: [] },

  // Periodontic
  { code: 'D4341', label: 'Scaling & root planing, per quadrant', surfaces: [] },
  { code: 'D4910', label: 'Periodontal maintenance', surfaces: [] },

  // Oral surgery
  { code: 'D7111', label: 'Extraction, coronal remnant', surfaces: [] },
  { code: 'D7140', label: 'Extraction, erupted tooth', surfaces: [] },
  { code: 'D7210', label: 'Extraction, erupted tooth requiring elevation', surfaces: [] },
  { code: 'D7220', label: 'Extraction, soft tissue impaction', surfaces: [] },
  { code: 'D7230', label: 'Extraction, partial bony impaction', surfaces: [] },
  { code: 'D7240', label: 'Extraction, complete bony impaction', surfaces: [] },
  { code: 'D7250', label: 'Surgical removal of residual roots', surfaces: [] },

  // Adjunctive
  { code: 'D9110', label: 'Palliative (emergency) treatment', surfaces: [] },
  { code: 'D9230', label: 'Inhalation of nitrous oxide', surfaces: [] },
  { code: 'D9243', label: 'IV sedation, each 15 min', surfaces: [] },
]

export const TOOTH_SURFACES = [
  { value: 'occlusal', label: 'Occlusal' },
  { value: 'incisal', label: 'Incisal' },
  { value: 'mesial', label: 'Mesial' },
  { value: 'distal', label: 'Distal' },
  { value: 'buccal', label: 'Buccal' },
  { value: 'lingual', label: 'Lingual' },
]

/**
 * Find a procedure entry by its code.
 *
 * @param {string} code
 * @returns {object|null}
 */
export function findProcedure(code) {
  return DENTAL_PROCEDURES.find((p) => p.code === code) ?? null
}
