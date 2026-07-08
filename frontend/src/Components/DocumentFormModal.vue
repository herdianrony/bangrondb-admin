<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="visible"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto"
        @keydown.escape="close"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="close"></div>

        <!-- Panel -->
        <div class="relative w-full max-w-2xl my-8 mx-4 animate-scale-in">
          <div class="card !p-0 overflow-hidden">
            <!-- ═══ Header ═══ -->
            <div class="px-6 py-5 border-b border-white/[0.07] flex items-center justify-between bg-[#161922]">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl grid place-items-center"
                     :class="isEdit ? 'bg-amber-500/10 border border-amber-500/20' : 'bg-emerald-500/10 border border-emerald-500/20'">
                  <component :is="isEdit ? Pencil : Plus" class="w-4 h-4"
                    :class="isEdit ? 'text-amber-400' : 'text-emerald-400'" />
                </div>
                <div>
                  <h3 class="font-bold text-white text-base">{{ isEdit ? 'Edit Document' : 'New Document' }}</h3>
                  <p class="text-[11px] text-slate-500 mt-0.5">
                    <span class="font-mono text-slate-400">{{ db }}.{{ collection }}</span>
                    <span v-if="Object.keys(schema).length" class="ml-2">
                      {{ Object.keys(schema).length }} fields
                    </span>
                    <span v-else class="ml-2">free-form JSON</span>
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <!-- View toggle -->
                <div class="flex items-center bg-[#0f1117] border border-white/[0.07] rounded-lg p-0.5">
                  <button
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-medium transition-all"
                    :class="!useRaw ? 'bg-indigo-500/15 text-indigo-300' : 'text-slate-500 hover:text-slate-300'"
                    @click="useRaw = false"
                  >
                    <LayoutGrid class="w-3.5 h-3.5" /> Form
                  </button>
                  <button
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-medium transition-all"
                    :class="useRaw ? 'bg-indigo-500/15 text-indigo-300' : 'text-slate-500 hover:text-slate-300'"
                    @click="useRaw = true"
                  >
                    <FileJson class="w-3.5 h-3.5" /> JSON
                  </button>
                </div>
                <button class="btn-ghost-sm !p-2" @click="close">
                  <X class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- ═══ Body ═══ -->
            <div class="p-6 space-y-5 max-h-[65vh] overflow-y-auto">
              <!-- Form Mode -->
              <div v-if="!useRaw && Object.keys(visibleSchema).length">
                <SchemaForm
                  :schema="schema"
                  v-model="formModel"
                  :api-error="error"
                  @validate="validateServer"
                />
              </div>

              <!-- JSON Mode or No Schema -->
              <div v-else>
                <div class="flex items-center justify-between mb-2">
                  <label class="section-label mb-0">JSON Document</label>
                  <button class="text-[11px] text-slate-500 hover:text-slate-300 flex items-center gap-1 transition-colors" @click="formatJson">
                    <AlignLeft class="w-3 h-3" /> Format
                  </button>
                </div>
                <textarea
                  v-model="editorText"
                  rows="16"
                  class="input font-mono text-sm !leading-relaxed"
                  placeholder='{"key": "value"}'
                  spellcheck="false"
                ></textarea>
                <p class="text-[11px] text-slate-600 mt-1.5">
                  <AlertTriangle class="w-3 h-3 inline mr-1 -mt-0.5" />
                  Pastikan JSON valid sebelum menyimpan
                </p>
              </div>

              <!-- Error -->
              <Transition name="fade">
                <div v-if="error" class="flex items-start gap-2.5 bg-red-950/30 border border-red-900/50 rounded-xl p-3.5">
                  <AlertCircle class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5" />
                  <p class="text-red-300 text-sm leading-relaxed">{{ error }}</p>
                </div>
              </Transition>
            </div>

            <!-- ═══ Footer ═══ -->
            <div class="px-6 py-4 border-t border-white/[0.07] flex items-center justify-between bg-[#161922]/50">
              <div class="flex items-center gap-3">
                <button class="btn-ghost-sm" @click="validateServer()">
                  <ShieldCheck class="w-3.5 h-3.5" />
                  Validate
                </button>
                <Transition name="fade">
                  <span v-if="validationMsg" class="text-xs font-medium"
                    :class="validationOk ? 'text-emerald-400' : 'text-amber-400'">
                    {{ validationMsg }}
                  </span>
                </Transition>
              </div>
              <div class="flex items-center gap-2">
                <button class="btn-ghost" @click="close">Cancel</button>
                <button class="btn flex items-center gap-2" :disabled="saving" @click="save">
                  <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                  <Save v-else class="w-4 h-4" />
                  {{ saving ? 'Saving...' : (isEdit ? 'Update' : 'Create') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import axios from 'axios'
import SchemaForm from '@/Components/SchemaForm.vue'
import {
  Plus, Pencil, X, Save, FileJson, LayoutGrid, ShieldCheck,
  AlertCircle, AlertTriangle, Loader2, AlignLeft,
} from 'lucide-vue-next'

const api = axios.create({ baseURL: '/api' })

const props = defineProps({
  visible: { type: Boolean, default: false },
  document: { type: Object, default: null },
  schema: { type: Object, default: () => ({}) },
  db: { type: String, default: '' },
  collection: { type: String, default: '' },
})

const emit = defineEmits(['close', 'saved'])

const isEdit = computed(() => !!props.document?._id)
const formModel = ref({})
const editorText = ref('{}')
const useRaw = ref(false)
const error = ref('')
const validationMsg = ref('')
const validationOk = ref(false)
const saving = ref(false)

const visibleSchema = computed(() => {
  const out = {}
  for (const [k, v] of Object.entries(props.schema || {})) {
    if (!v.hidden) out[k] = v
  }
  return out
})

watch(() => props.visible, async (v) => {
  if (!v) return
  reset()
  await nextTick()
})

watch(() => props.document, (doc) => {
  if (doc && props.visible) {
    formModel.value = { ...doc }
    delete formModel.value._id
    editorText.value = JSON.stringify(doc, null, 2)
  }
}, { immediate: true })

watch(formModel, (v) => {
  if (!useRaw.value) {
    editorText.value = JSON.stringify(v, null, 2)
  }
}, { deep: true })

function reset() {
  error.value = ''
  validationMsg.value = ''
  validationOk.value = false
  saving.value = false
  if (props.document) {
    formModel.value = { ...props.document }
    delete formModel.value._id
    editorText.value = JSON.stringify(props.document, null, 2)
  } else {
    formModel.value = {}
    // Apply schema defaults
    for (const [f, def] of Object.entries(props.schema)) {
      if (def.default !== undefined) formModel.value[f] = def.default
    }
    editorText.value = '{}'
  }
  useRaw.value = !Object.keys(props.schema).length
}

function close() {
  error.value = ''
  emit('close')
}

function getPayload() {
  if (useRaw.value || !Object.keys(props.schema).length) {
    try {
      return JSON.parse(editorText.value)
    } catch (e) {
      throw new Error('JSON tidak valid: ' + e.message)
    }
  }
  return { ...formModel.value }
}

async function validateServer(payload) {
  validationMsg.value = 'Validating...'
  validationOk.value = false
  try {
    const body = payload || getPayload()
    const r = await api.post(`/${props.db}/${props.collection}/validate`, body)
    validationOk.value = r.data.valid
    validationMsg.value = r.data.valid ? 'Document valid sesuai schema' : (r.data.error || 'Invalid')
    return r.data.valid
  } catch (e) {
    validationOk.value = false
    validationMsg.value = 'Error: ' + (e.response?.data?.error || e.message)
    return false
  }
}

function formatJson() {
  try {
    const parsed = JSON.parse(editorText.value)
    editorText.value = JSON.stringify(parsed, null, 2)
    error.value = ''
  } catch (e) {
    error.value = 'JSON tidak valid: ' + e.message
  }
}

async function save() {
  error.value = ''
  saving.value = true
  try {
    const payload = getPayload()
    const valid = await validateServer(payload)
    if (!valid && Object.keys(props.schema).length) {
      saving.value = false
      return
    }
    if (isEdit.value) {
      payload._id = props.document._id
      await api.post(`/${props.db}/${props.collection}/save`, payload)
    } else {
      await api.post(`/${props.db}/${props.collection}/documents`, payload)
    }
    emit('saved')
    close()
  } catch (e) {
    error.value = e.response?.data?.message || e.response?.data?.error || e.message
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.modal-enter-active { transition: opacity 0.2s ease; }
.modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.fade-enter-active { transition: all 0.2s ease; }
.fade-leave-active { transition: all 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-4px); }
</style>