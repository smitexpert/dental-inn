<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import UiDialog from '@/Components/ui/Dialog.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'created', 'selected'])

const open = ref(props.modelValue)
watch(() => props.modelValue, (v) => (open.value = v))
watch(open, (v) => emit('update:modelValue', v))

const tab = ref('pick') // 'pick' | 'create'

const search = ref('')
const results = ref([])
const searching = ref(false)
let searchTimer = null

watch(search, () => {
  clearTimeout(searchTimer)
  if (!search.value || search.value.length < 2) {
    results.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    searching.value = true
    try {
      const { data } = await axios.get('/patients/search', {
        params: { q: search.value, limit: 8 },
      })
      results.value = data.data ?? []
    } finally {
      searching.value = false
    }
  }, 250)
})

function pickPatient(p) {
  emit('selected', p)
  open.value = false
}

const form = useForm({
  name: '',
  phone: '',
  email: '',
  date_of_birth: '',
  gender: null,
})

const genderOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
]

function submitNew() {
  form.post('/patients/quick', {
    preserveScroll: true,
    onSuccess: (page) => {
      const created = page.props?.flash?.patient
      if (created) emit('created', created)
      form.reset()
      open.value = false
    },
  })
}
</script>

<template>
  <UiDialog v-model="open" title="Select patient" size="lg">
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
        Pick existing
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
        New patient
      </button>
    </div>

    <div v-if="tab === 'pick'">
      <UiInput v-model="search" placeholder="Search by name, phone, email, or code" autofocus />
      <div class="mt-3 max-h-72 overflow-y-auto rounded-md border border-base-300">
        <div v-if="searching" class="p-3 text-sm text-base-content/70">Searching...</div>
        <div
          v-else-if="search && results.length === 0"
          class="p-3 text-sm text-base-content/70"
        >
          No matches. Switch to "New patient" to create one.
        </div>
        <div v-else-if="!search" class="p-3 text-sm text-base-content/70">
          Type at least 2 characters to search.
        </div>
        <ul v-else class="divide-y divide-border">
          <li
            v-for="p in results"
            :key="p.id"
            class="p-3 hover:bg-base-200 cursor-pointer"
            @click="pickPatient(p)"
          >
            <div class="font-medium text-base-content">{{ p.name }}</div>
            <div class="text-xs text-base-content/70 font-mono">{{ p.patient_code }}</div>
            <div class="text-xs text-base-content/70">
              {{ p.phone || 'no phone' }}<span v-if="p.email"> · {{ p.email }}</span>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <div v-else>
      <form @submit.prevent="submitNew" class="space-y-3">
        <div>
          <label class="text-sm font-medium">Name *</label>
          <UiInput v-model="form.name" required class="mt-1" />
          <p v-if="form.errors.name" class="text-xs text-error-600 mt-1">{{ form.errors.name }}</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-medium">Phone</label>
            <UiInput v-model="form.phone" type="tel" class="mt-1" />
            <p v-if="form.errors.phone" class="text-xs text-error-600 mt-1">{{ form.errors.phone }}</p>
          </div>
          <div>
            <label class="text-sm font-medium">Email</label>
            <UiInput v-model="form.email" type="email" class="mt-1" />
            <p v-if="form.errors.email" class="text-xs text-error-600 mt-1">{{ form.errors.email }}</p>
          </div>
          <div>
            <label class="text-sm font-medium">Date of birth</label>
            <input
              v-model="form.date_of_birth"
              type="date"
              class="mt-1 h-10 w-full rounded-md border border-base-300 bg-base-100 px-3 text-sm"
            />
          </div>
          <div>
            <label class="text-sm font-medium">Gender</label>
            <UiSelect v-model="form.gender" :options="genderOptions" placeholder="Select gender" class="mt-1" />
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-base-300">
          <button
            type="button"
            class="h-10 px-4 rounded-md bg-base-200 text-base-content border border-base-300 text-sm"
            @click="tab = 'pick'"
          >
            Back to search
          </button>
          <UiButton type="submit" :loading="form.processing">Create patient</UiButton>
        </div>
      </form>
    </div>

    <template #footer>
      <UiButton variant="secondary" @click="open = false">Cancel</UiButton>
    </template>
  </UiDialog>
</template>