<script setup>
/**
 * Payment Receipt Print (A4)
 * --------------------------
 * Dedicated printable view for a payment receipt. No layout, no
 * sidebar, no letterhead — the clinic's stationery already carries
 * the letterhead, and this page must not duplicate it. The Vue
 * component renders only the receipt content; @media print + @page
 * in the <style> block below size the page to A4 portrait with 15mm
 * margins.
 *
 * The page auto-triggers window.print() in onMounted so the browser
 * print dialog appears as soon as the page is ready. A toolbar with
 * a manual Print button + Back link is shown only on screen via the
 * .no-print class.
 */
import { Head, Link } from '@inertiajs/vue3'
import { onMounted } from 'vue'

const props = defineProps({
  payment: { type: Object, required: true },
  currency: { type: Object, required: true },
})

const payment = props.payment
const currency = props.currency

function fmtAmount(value) {
  const n = Number(value ?? 0)
  const fixed = Number.isFinite(n) ? n.toFixed(currency.decimals ?? 2) : '0.00'
  const sym = currency.symbol || ''
  const code = currency.code || ''
  if (currency.position === 'before') {
    return code ? `${sym}${fixed} ${code}` : `${sym}${fixed}`
  }
  return code ? `${fixed} ${code}` : `${fixed} ${sym}`.trim()
}

function longDate(s) {
  if (!s) return '—'
  const d = new Date(s)
  if (Number.isNaN(d.getTime())) return s
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })
}

function doPrint() {
  window.print()
}

onMounted(() => {
  // Defer one tick so layout/fonts settle before the print dialog
  // captures the page; avoids blank previews on slow connections.
  setTimeout(doPrint, 250)
})
</script>

<template>
  <Head title="Payment receipt" />

  <div class="print-page">
    <!-- Screen-only toolbar (hidden when printing) -->
    <div class="no-print toolbar">
      <div class="toolbar-inner">
        <div class="toolbar-meta">
          <span class="toolbar-label">Receipt</span>
          <span class="toolbar-number">#{{ payment.id }}</span>
        </div>
        <div class="toolbar-actions">
          <Link :href="`/billing/payments/${payment.id}/receipt`" class="btn-link">
            ← Back to receipt
          </Link>
          <button type="button" class="btn-primary" @click="doPrint">
            Print
          </button>
        </div>
      </div>
    </div>

    <!-- The actual printable sheet -->
    <article class="print-sheet">
      <header class="sheet-header">
        <div class="sheet-title">
          <h1>PAYMENT RECEIPT</h1>
          <p class="sheet-number">Receipt #{{ payment.id }}</p>
        </div>
        <div class="sheet-dates">
          <div class="date-row">
            <span class="date-label">Payment date</span>
            <span class="date-value">{{ longDate(payment.payment_date) }}</span>
          </div>
          <div v-if="payment.reference" class="date-row">
            <span class="date-label">Reference</span>
            <span class="date-value">{{ payment.reference }}</span>
          </div>
        </div>
      </header>

      <section class="two-col">
        <div class="col-block">
          <h2 class="section-label">Received from</h2>
          <p class="block-name">{{ payment.invoice?.patient?.name ?? '—' }}</p>
          <p v-if="payment.invoice?.patient?.patient_code" class="block-sub">
            Patient ID: {{ payment.invoice.patient.patient_code }}
          </p>
        </div>
        <div class="col-block">
          <h2 class="section-label">Received by</h2>
          <p class="block-name">{{ payment.receiver ?? '—' }}</p>
        </div>
      </section>

      <section class="amount-block">
        <p class="amount-label">Amount paid</p>
        <p class="amount-value">{{ fmtAmount(payment.amount) }}</p>
        <p class="amount-meta">
          via {{ payment.method_label }}
        </p>
      </section>

      <section v-if="payment.invoice" class="invoice-block">
        <h2 class="section-label">Linked invoice</h2>
        <div class="invoice-grid">
          <div class="invoice-line">
            <span class="line-label">Invoice</span>
            <span class="line-value font-mono">{{ payment.invoice.invoice_number }}</span>
          </div>
          <div class="invoice-line">
            <span class="line-label">Invoice total</span>
            <span class="line-value">{{ fmtAmount(payment.invoice.total) }}</span>
          </div>
          <div class="invoice-line">
            <span class="line-label">Paid to date</span>
            <span class="line-value">{{ fmtAmount(payment.invoice.paid_amount) }}</span>
          </div>
          <div class="invoice-line">
            <span class="line-label">Balance due</span>
            <span
              class="line-value"
              :class="{ 'balance-positive': Number(payment.invoice.balance_due) > 0 }"
            >
              {{ fmtAmount(payment.invoice.balance_due) }}
            </span>
          </div>
        </div>
      </section>

      <section v-if="payment.notes" class="notes">
        <h2 class="section-label">Notes</h2>
        <p class="notes-body">{{ payment.notes }}</p>
      </section>

      <footer class="signatures">
        <div class="signature-line">
          <span class="line"></span>
          <span class="line-label">Received by</span>
        </div>
        <div class="signature-line">
          <span class="line"></span>
          <span class="line-label">Authorised signatory</span>
        </div>
      </footer>
    </article>
  </div>
</template>

<style scoped>
/* Page wrapper — full-bleed background on screen so the toolbar has
 * some breathing room; switches to a clean white canvas for print. */
.print-page {
  min-height: 100vh;
  background: #e5e7eb;
  color: #111827;
  font-family: 'Times New Roman', Georgia, 'DejaVu Serif', serif;
}

/* --- Screen toolbar (hidden in print) --- */
.toolbar {
  background: #1f2937;
  color: #f9fafb;
  border-bottom: 1px solid #111827;
  position: sticky;
  top: 0;
  z-index: 10;
}
.toolbar-inner {
  max-width: 210mm;
  margin: 0 auto;
  padding: 0.75rem 15mm;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
.toolbar-meta {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  min-width: 0;
}
.toolbar-label {
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #9ca3af;
  font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
}
.toolbar-number {
  font-family: 'Courier New', monospace;
  font-size: 0.95rem;
  font-weight: 600;
  color: #f9fafb;
}
.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
  font-size: 0.875rem;
}
.btn-link,
.btn-primary {
  display: inline-flex;
  align-items: center;
  padding: 0.4rem 0.85rem;
  border-radius: 6px;
  border: 1px solid transparent;
  cursor: pointer;
  text-decoration: none;
  font-weight: 500;
  transition: background 0.15s ease;
}
.btn-link {
  color: #d1d5db;
  background: transparent;
}
.btn-link:hover {
  color: #fff;
  background: #374151;
}
.btn-primary {
  color: #fff;
  background: #2563eb;
  border-color: #1d4ed8;
}
.btn-primary:hover {
  background: #1d4ed8;
}

/* --- Printable sheet --- */
.print-sheet {
  width: 210mm;
  min-height: 140mm;
  margin: 1rem auto;
  padding: 18mm 18mm 14mm 18mm;
  background: #ffffff;
  box-shadow: 0 6px 24px rgba(15, 23, 42, 0.12);
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  color: #111827;
  font-size: 10.5pt;
  line-height: 1.45;
}

.sheet-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #111827;
}
.sheet-title {
  flex: 1 1 auto;
  min-width: 0;
}
.sheet-title h1 {
  margin: 0;
  font-size: 22pt;
  font-weight: 700;
  letter-spacing: 0.12em;
}
.sheet-number {
  margin: 0.25rem 0 0;
  font-size: 10pt;
  letter-spacing: 0.04em;
  font-family: 'Courier New', monospace;
  color: #374151;
}
.sheet-dates {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 10pt;
  flex: 0 0 auto;
}
.date-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 1rem;
}
.date-label {
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-size: 8.5pt;
  white-space: nowrap;
  width: 28mm;
  flex: 0 0 28mm;
}
.date-value {
  font-weight: 600;
  color: #111827;
  white-space: nowrap;
  text-align: right;
  display: inline-block;
}

.section-label {
  margin: 0 0 0.35rem;
  font-size: 8.5pt;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #6b7280;
  font-weight: 600;
}

.two-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-top: 1.25rem;
}
.col-block {
  border-left: 3px solid #111827;
  padding-left: 0.75rem;
}
.block-name {
  margin: 0;
  font-size: 12pt;
  font-weight: 600;
  color: #111827;
}
.block-sub {
  margin: 0.15rem 0 0;
  font-size: 9.5pt;
  color: #6b7280;
  font-family: 'Courier New', monospace;
}

.amount-block {
  margin-top: 1.5rem;
  padding: 1rem 1.25rem;
  border: 1.5px solid #111827;
  background: #f9fafb;
  text-align: center;
}
.amount-label {
  margin: 0;
  font-size: 9pt;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #6b7280;
}
.amount-value {
  margin: 0.25rem 0 0;
  font-size: 28pt;
  font-weight: 700;
  letter-spacing: 0.02em;
  color: #111827;
  font-variant-numeric: tabular-nums;
}
.amount-meta {
  margin: 0.25rem 0 0;
  font-size: 10pt;
  color: #374151;
  letter-spacing: 0.04em;
}

.invoice-block {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px dashed #d1d5db;
}
.invoice-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem 1.5rem;
}
.invoice-line {
  display: flex;
  justify-content: space-between;
  padding: 0.2rem 0;
  font-size: 10pt;
  color: #374151;
}
.line-label {
  color: #6b7280;
}
.line-value {
  font-weight: 600;
  color: #111827;
  font-variant-numeric: tabular-nums;
}
.line-value.balance-positive {
  color: #b91c1c;
}

.notes {
  margin-top: 1rem;
  padding-top: 0.75rem;
  border-top: 1px dashed #d1d5db;
}
.notes-body {
  white-space: pre-wrap;
  font-size: 9.5pt;
  color: #374151;
  margin: 0;
}

.signatures {
  margin-top: 2.5rem;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2.5rem;
}
.signature-line {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
}
.signature-line .line {
  display: block;
  width: 100%;
  border-top: 1px solid #111827;
  height: 0;
}
.line-label {
  font-size: 8.5pt;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #6b7280;
}

/* --- Print rules --- */
@page {
  size: A4 portrait;
  margin: 15mm;
}
@media print {
  .print-page {
    background: #fff;
    min-height: 0;
  }
  .toolbar {
    display: none !important;
  }
  .print-sheet {
    margin: 0;
    box-shadow: none;
    width: 100%;
    min-height: 0;
    padding: 0;
  }
  .signatures,
  .amount-block {
    page-break-inside: avoid;
  }
}
</style>
