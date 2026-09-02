import { describe, it, expect } from 'vitest'

const STATUSES = [
  { value: 'scheduled', label: 'Scheduled', variant: 'secondary' },
  { value: 'confirmed', label: 'Confirmed', variant: 'primary' },
  { value: 'checked_in', label: 'Checked in', variant: 'warning' },
  { value: 'completed', label: 'Completed', variant: 'success' },
  { value: 'cancelled', label: 'Cancelled', variant: 'danger' },
  { value: 'no_show', label: 'No show', variant: 'danger' },
]

const TRANSITIONS = {
  scheduled: ['confirmed', 'cancelled', 'no_show'],
  confirmed: ['checked_in', 'cancelled', 'no_show'],
  checked_in: ['completed', 'no_show'],
}

function variantFor(status) {
  return STATUSES.find((s) => s.value === status)?.variant ?? 'secondary'
}

function nextStatusesFor(current) {
  return TRANSITIONS[current] ?? []
}

describe('appointment helpers', () => {
  it('maps status to badge variant', () => {
    expect(variantFor('completed')).toBe('success')
    expect(variantFor('cancelled')).toBe('danger')
    expect(variantFor('confirmed')).toBe('primary')
    expect(variantFor('unknown')).toBe('secondary')
  })

  it('returns valid next statuses per current status', () => {
    expect(nextStatusesFor('scheduled')).toContain('confirmed')
    expect(nextStatusesFor('checked_in')).toContain('completed')
    expect(nextStatusesFor('completed')).toEqual([])
  })
})
