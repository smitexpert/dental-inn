<script setup>
/**
 * MedicinePickerDialog
 * -------------------
 * Two-tab modal used inside the prescription builder:
 *   1. "বিদ্যমান ওষুধ নির্বাচন" — debounced search of the medicine catalog
 *   2. "নতুন ওষুধ যোগ করুন" — quick-create a new medicine row
 *
 * Mirrors the structure of PatientQuickAddDialog.vue so the picker feels
 * native to this app. The doctor types a Bangla/English prefix; we hit
 * /medicines/search and let them click a result. If nothing matches
 * they can switch tabs and add the medicine inline — POST /medicines/quick
 * returns the row so the picker can select it immediately.
 */
import { reactive, ref, watch } from 'vue'
import axios from 'axios'
import UiDialog from '@/Components/ui/Dialog.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'selected'])

const open = ref(props.modelValue)
watch(() => props.modelValue, (v) => (open.value = v))
watch(open, (v) => emit('update:modelValue', v))

const tab = ref('pick') // 'pick' | 'create'

const search = ref('')
const results = ref([])
const searching = ref(false)
const searchError = ref('')
let searchTimer = null

watch(search, () => {
  clearTimeout(searchTimer)
  searchError.value = ''
  if (!search.value || search.value.length < 2) {
    results.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    searching.value = true
    try {
      const { data } = await axios.get('/medicines/search', {
        params: { q: search.value, limit: 8 },
      })
      results.value = Array.isArray(data) ? data : (data.data ?? [])
    } catch (e) {
      searchError.value = 'অনুসন্ধান ব্যর্থ হয়েছে। আবার চেষ্টা করুন।'
      results.value = []
    } finally {
      searching.value = false
    }
  }, 250)
})

function pickMedicine(m) {
  emit('selected', {
    id: m.id,
    name: m.name,
    strength: m.strength ?? '',
    form: m.form ?? '',
    display: m.display,
  })
  open.value = false
}

// Local form state + error map — we don't use `useForm()` here because
// /medicines/quick returns plain JSON and Inertia's form helper expects an
// Inertia response. Plain refs + axios keep things consistent with the
// /medicines/search call above.
const form = reactive({
  name: '',
  strength: '',
  form: '',
  name_bn: '',
})
const formErrors = reactive({
  name: '',
  strength: '',
  form: '',
  name_bn: '',
})
const submitting = ref(false)

function resetForm() {
  form.name = ''
  form.strength = ''
  form.form = ''
  form.name_bn = ''
  formErrors.name = ''
  formErrors.strength = ''
  formErrors.form = ''
  formErrors.name_bn = ''
}

function submitNew() {
  // Hit /medicines/quick with plain axios — the endpoint returns JSON, so
  // routing it through `form.post()` (Inertia) raises "plain JSON response
  // was received" because Inertia expects an Inertia-shaped payload.
  submitting.value = true
  axios
    .post('/medicines/quick', {
      name: form.name,
      strength: form.strength || null,
      form: form.form || null,
      name_bn: form.name_bn || null,
    })
    .then(({ data }) => {
      // The endpoint returns the row directly, so we don't need a re-fetch.
      pickMedicine({
        id: data.id,
        name: data.name,
        strength: data.strength ?? '',
        form: data.form ?? '',
        display: data.display,
      })
    })
    .catch((err) => {
      const errors = err.response?.data?.errors ?? {}
      // Mirror the Laravel 422 shape so the existing error rendering keeps working.
      if (errors.name) formErrors.name = errors.name[0]
      if (errors.strength) formErrors.strength = errors.strength[0]
      if (errors.form) formErrors.form = errors.form[0]
    })
    .finally(() => {
      submitting.value = false
    })
}

function reset() {
  search.value = ''
  results.value = []
  resetForm()
  tab.value = 'pick'
}
</script>

<template>
  <UiDialog v-model="open" title="ওষুধ নির্বাচন করুন" size="lg" @close="reset">
    <div class="flex gap-2 border-b border-base-300 mb-3 -mt-1">
      <button
        type="button"
        :class="[
          'px-3 py-2 text-sm font-medium border-b-2 -mb-px',
          tab === 'pick'
            ? 'border-primary text-primary'
            : 'border-transparent text-base-content/70 hover:text-base-content',
        ]"
        @click="tab = 'pick'"
      >
        বিদ্যমান ওষুধ নির্বাচন
      </button>
      <button
        type="button"
        :class="[
          'px-3 py-2 text-sm font-medium border-b-2 -mb-px',
          tab === 'create'
            ? 'border-primary text-primary'
            : 'border-transparent text-base-content/70 hover:text-base-content',
        ]"
        @click="tab = 'create'"
      >
        নতুন ওষুধ যোগ করুন
      </button>
    </div>

    <!-- Tab 1: pick existing -->
    <div v-if="tab === 'pick'" class="space-y-3">
      <div>
        <UiInput
          v-model="search"
          placeholder="ওষুধের নাম লিখুন (কমপক্ষে ২ অক্ষর)…"
          autofocus
        />
      </div>

      <div v-if="searching" class="text-sm text-muted py-2">অনুসন্ধান চলছে…</div>
      <div v-else-if="searchError" class="text-sm text-error py-2">
        {{ searchError }}
      </div>
      <div
        v-else-if="search && search.length >= 2 && results.length === 0"
        class="text-sm text-muted py-3 text-center border border-dashed border-base-300 rounded-md"
      >
        কোনো ওষুধ পাওয়া যায়নি। "নতুন ওষুধ যোগ করুন" ট্যাবে যান।
      </div>
      <div v-else-if="results.length === 0" class="text-sm text-muted py-3 text-center">
        শুরু করতে ওষুধের নাম টাইপ করুন।
      </div>

      <ul v-else class="divide-y divide-base-300 max-h-72 overflow-y-auto border border-base-300 rounded-md">
        <li v-for="m in results" :key="m.id">
          <button
            type="button"
            class="w-full text-left px-3 py-2 hover:bg-base-200 transition-colors"
            @click="pickMedicine(m)"
          >
            <div class="font-medium">{{ m.display || m.name }}</div>
            <div v-if="m.name_bn" class="text-xs text-muted">{{ m.name_bn }}</div>
          </button>
        </li>
      </ul>
    </div>

    <!-- Tab 2: create new -->
    <div v-else class="space-y-3">
      <p class="text-sm text-muted">
        ওষুধটি এখনো ক্যাটালগে যোগ হয়নি। নিচের ঘরগুলো পূরণ করে সেভ করলে
        এই প্রেসক্রিপশনেই ব্যবহার করতে পারবেন।
      </p>
      <div>
        <label class="text-xs font-medium text-base-content/70">ওষুধের নাম *</label>
        <UiInput v-model="form.name" placeholder="যেমন: Aceclofenac" />
        <p v-if="formErrors.name" class="text-xs text-error mt-1">{{ formErrors.name }}</p>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-medium text-base-content/70">স্ট্রেংথ</label>
          <UiInput v-model="form.strength" placeholder="যেমন: 100mg" />
          <p v-if="formErrors.strength" class="text-xs text-error mt-1">{{ formErrors.strength }}</p>
        </div>
        <div>
          <label class="text-xs font-medium text-base-content/70">ফর্ম</label>
          <UiInput v-model="form.form" placeholder="যেমন: Tablet" />
          <p v-if="formErrors.form" class="text-xs text-error mt-1">{{ formErrors.form }}</p>
        </div>
      </div>
      <div>
        <label class="text-xs font-medium text-base-content/70">বাংলা নাম (ঐচ্ছিক)</label>
        <UiInput v-model="form.name_bn" placeholder="যেমন: এসিক্লোফেনাক" />
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <UiButton variant="outline" type="button" @click="tab = 'pick'">বাতিল</UiButton>
        <UiButton :loading="submitting" @click="submitNew">ওষুধ যোগ করুন</UiButton>
      </div>
    </div>
  </UiDialog>
</template>