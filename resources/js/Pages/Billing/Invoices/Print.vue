<script setup>
/**
 * Invoice Print (A4)
 * ------------------
 * Dedicated printable view for an invoice. No layout, no sidebar, no
 * letterhead — the clinic's stationery already carries the letterhead,
 * and this page must not duplicate it. The Vue component renders only
 * the invoice content; @media print + @page in the <style> block
 * below size the page to A4 portrait with 15mm margins.
 *
 * The page auto-triggers window.print() in onMounted so the browser
 * print dialog appears as soon as the page is ready. A toolbar with a
 * manual Print button + Back link is shown only on screen via the
 * .no-print class.
 */
import { Head, Link, router } from '@inertiajs/vue3'
import { onMounted } from 'vue'

const props = defineProps({
  invoice: { type: Object, required: true },
  currency: { type: Object, required: true },
})

const invoice = props.invoice
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

function fmtDate(s) {
  if (!s) return '—'
  // Invoice dates come in Y-m-d from the serializer; render as-is
  // (the page is for printing — locale formatting is the browser's job).
  return s
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

function goBack() {
  // router.visit lets us navigate without adding to history — the user
  // is likely coming straight from the invoice Show page.
  router.visit(`/billing/invoices/${invoice.id}`)
}

onMounted(() => {
  // Defer one tick so the layout/fonts settle before the print dialog
  // captures the page; avoids blank previews on slow connections.
  setTimeout(doPrint, 250)
})
</script>

<template>
  <Head :title="`Invoice ${invoice.invoice_number}`" />

  <div class="invoice-page">
    <!-- Screen-only toolbar (hidden when printing) -->
    <div class="no-print toolbar">
      <div class="toolbar-inner">
        <div class="toolbar-meta">
          <span class="toolbar-label">Invoice</span>
          <span class="toolbar-number">{{ invoice.invoice_number }}</span>
        </div>
        <div class="toolbar-actions">
          <Link :href="`/billing/invoices/${invoice.id}`" class="btn-link">
            ← Back to invoice
          </Link>
          <button type="button" class="btn-primary" @click="doPrint">
            Print
          </button>
        </div>
      </div>
    </div>

    <!-- The actual printable sheet -->
    <article class="invoice-sheet">
      <header class="sheet-header">
        <div class="sheet-title">
          <h1>INVOICE</h1>
          <p class="sheet-number">No. {{ invoice.invoice_number }}</p>
        </div>
        <div class="sheet-dates">
          <div class="date-row">
            <span class="date-label">Issue date</span>
            <span class="date-value">{{ longDate(invoice.invoice_date) }}</span>
          </div>
          <div class="date-row">
            <span class="date-label">Due date</span>
            <span class="date-value">{{ longDate(invoice.due_date) }}</span>
          </div>
          <div class="date-row">
            <span class="date-label">Status</span>
            <span class="date-value status" :data-status="invoice.status">
              {{ invoice.status_label }}
            </span>
          </div>
        </div>
      </header>

      <section class="bill-to">
        <h2 class="section-label">Billed to</h2>
        <div class="bill-to-body">
          <p class="patient-name">{{ invoice.patient?.name ?? '—' }}</p>
          <p v-if="invoice.patient?.patient_code" class="patient-code">
            Patient ID: {{ invoice.patient.patient_code }}
          </p>
        </div>
      </section>

      <section class="items">
        <table class="items-table">
          <thead>
            <tr>
              <th class="col-desc">Description</th>
              <th class="col-num">Qty</th>
              <th class="col-num">Unit price</th>
              <th class="col-num">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in invoice.items" :key="item.id">
              <td class="col-desc">{{ item.description }}</td>
              <td class="col-num">{{ item.quantity }}</td>
              <td class="col-num">{{ fmtAmount(item.unit_price) }}</td>
              <td class="col-num">{{ fmtAmount(item.line_total) }}</td>
            </tr>
            <tr v-if="invoice.items.length === 0">
              <td colspan="4" class="empty">No line items.</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="totals">
        <dl class="totals-list">
          <div class="totals-row">
            <dt>Subtotal</dt>
            <dd>{{ fmtAmount(invoice.subtotal) }}</dd>
          </div>
          <div v-if="Number(invoice.discount_amount) > 0" class="totals-row">
            <dt>Discount</dt>
            <dd>− {{ fmtAmount(invoice.discount_amount) }}</dd>
          </div>
          <div v-if="Number(invoice.tax_amount) > 0" class="totals-row">
            <dt>Tax ({{ invoice.tax_rate }}%)</dt>
            <dd>{{ fmtAmount(invoice.tax_amount) }}</dd>
          </div>
          <div class="totals-row total">
            <dt>Total</dt>
            <dd>{{ fmtAmount(invoice.total) }}</dd>
          </div>
          <div class="totals-row paid">
            <dt>Paid</dt>
            <dd>{{ fmtAmount(invoice.paid_amount) }}</dd>
          </div>
          <div class="totals-row balance">
            <dt>Balance due</dt>
            <dd>{{ fmtAmount(invoice.balance_due) }}</dd>
          </div>
        </dl>
      </section>

      <section v-if="invoice.notes" class="notes">
        <h2 class="section-label">Notes</h2>
        <p class="notes-body">{{ invoice.notes }}</p>
      </section>

      <footer class="signature">
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
.invoice-page {
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
.invoice-sheet {
  width: 210mm;
  min-height: 297mm;
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
  text-align: right;
  font-size: 10pt;
  flex: 0 0 auto;
  white-space: nowrap;
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
}
.date-value {
  font-weight: 600;
  color: #111827;
  white-space: nowrap;
  text-align: right;
}
.date-value.status {
  padding: 0.05rem 0.45rem;
  border-radius: 3px;
  display: inline-block;
}
.date-value.status[data-status='paid'] {
  color: #166534;
}
.date-value.status[data-status='partially_paid'] {
  color: #92400e;
}
.date-value.status[data-status='overdue'] {
  color: #b91c1c;
}
.date-value.status[data-status='cancelled'] {
  color: #6b7280;
  text-decoration: line-through;
}

.bill-to {
  margin-top: 1.25rem;
}
.section-label {
  margin: 0 0 0.35rem;
  font-size: 8.5pt;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #6b7280;
  font-weight: 600;
}
.bill-to-body {
  border-left: 3px solid #111827;
  padding-left: 0.75rem;
}
.patient-name {
  margin: 0;
  font-size: 13pt;
  font-weight: 600;
  color: #111827;
}
.patient-code {
  margin: 0.15rem 0 0;
  font-size: 9.5pt;
  color: #6b7280;
  font-family: 'Courier New', monospace;
}

.items {
  margin-top: 1.5rem;
  flex-grow: 1;
}
.items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 10pt;
}
.items-table thead th {
  text-align: left;
  font-size: 8.5pt;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #6b7280;
  padding: 0.45rem 0.5rem;
  border-bottom: 1.5px solid #111827;
}
.items-table tbody td {
  padding: 0.55rem 0.5rem;
  border-bottom: 1px solid #e5e7eb;
  vertical-align: top;
}
.items-table tbody tr:last-child td {
  border-bottom: 1.5px solid #111827;
}
.col-num {
  text-align: right;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}
.col-desc {
  text-align: left;
}
.empty {
  text-align: center;
  color: #9ca3af;
  font-style: italic;
  padding: 1rem 0;
}

.totals {
  margin-top: 1.25rem;
  display: flex;
  justify-content: flex-end;
}
.totals-list {
  width: 75mm;
  margin: 0;
}
.totals-row {
  display: flex;
  justify-content: space-between;
  padding: 0.25rem 0;
  font-size: 10pt;
  color: #374151;
}
.totals-row dt,
.totals-row dd {
  margin: 0;
}
.totals-row dd {
  font-variant-numeric: tabular-nums;
}
.totals-row.total {
  border-top: 1.5px solid #111827;
  border-bottom: 1.5px solid #111827;
  padding: 0.5rem 0;
  font-size: 12pt;
  font-weight: 700;
  color: #111827;
}
.totals-row.paid {
  color: #166534;
}
.totals-row.balance {
  font-weight: 700;
  color: #b91c1c;
  padding-top: 0.4rem;
}

.notes {
  margin-top: 1.25rem;
  padding-top: 0.75rem;
  border-top: 1px dashed #d1d5db;
}
.notes-body {
  white-space: pre-wrap;
  font-size: 9.5pt;
  color: #374151;
  margin: 0;
}

.signature {
  margin-top: 2rem;
  display: flex;
  justify-content: flex-end;
}
.signature-line {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 65mm;
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
  .invoice-page {
    background: #fff;
    min-height: 0;
  }
  .toolbar {
    display: none !important;
  }
  .invoice-sheet {
    margin: 0;
    box-shadow: none;
    width: 100%;
    min-height: 0;
    padding: 0;
  }
  .signature {
    page-break-inside: avoid;
  }
  .items-table tbody tr {
    page-break-inside: avoid;
  }
}
</style>
