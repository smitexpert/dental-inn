import { describe, it, expect } from 'vitest'
import { formatMoney } from '@/lib/money.js'

const bdt = { currency_symbol: '৳', currency_decimals: 0, currency_position: 'before' }
const usd = { currency_symbol: '$', currency_decimals: 2, currency_position: 'before' }
const inr = { currency_symbol: '₹', currency_decimals: 2, currency_position: 'after' }

describe('formatMoney', () => {
  it('formats BDT without decimals, symbol before', () => {
    expect(formatMoney(1234567, bdt)).toBe('৳1,234,567')
  })
  it('formats USD with 2 decimals, symbol before', () => {
    expect(formatMoney(1234.5, usd)).toBe('$1,234.50')
  })
  it('formats INR with 2 decimals, symbol after', () => {
    expect(formatMoney(99, inr)).toBe('99.00₹')
  })
  it('handles null and undefined', () => {
    expect(formatMoney(null, usd)).toBe('$0.00')
    expect(formatMoney(undefined, bdt)).toBe('৳0')
  })
})
