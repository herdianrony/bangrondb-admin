<template>
  <div class="space-y-5">
    <!-- ═══ Toolbar: Add Field Bar ═══ -->
    <div class="flex flex-wrap items-center gap-2">
      <!-- Inline add bar -->
      <div class="flex items-center flex-1 min-w-0 bg-[#0f1117] border border-white/[0.07] rounded-xl overflow-hidden focus-within:border-indigo-500/50 focus-within:ring-2 focus-within:ring-indigo-500/10 transition-all duration-150">
        <input
          v-model="newFieldName"
          @keyup.enter="addField"
          placeholder="field_name"
          class="flex-1 min-w-0 bg-transparent px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 outline-none font-mono"
        />
        <div class="w-px h-6 bg-white/[0.07]"></div>
        <select
          v-model="newFieldType"
          class="bg-transparent text-xs text-slate-300 outline-none pl-3 pr-8 py-2.5 appearance-none cursor-pointer"
        >
          <optgroup label="Text" class="text-slate-500">
            <option v-for="t in typeGroups.text" :key="t" :value="t">{{ t }}</option>
          </optgroup>
          <optgroup label="Numbers" class="text-slate-500">
            <option v-for="t in typeGroups.numbers" :key="t" :value="t">{{ t }}</option>
          </optgroup>
          <optgroup label="Boolean" class="text-slate-500">
            <option v-for="t in typeGroups.boolean" :key="t" :value="t">{{ t }}</option>
          </optgroup>
          <optgroup label="Structured" class="text-slate-500">
            <option v-for="t in typeGroups.structured" :key="t" :value="t">{{ t }}</option>
          </optgroup>
          <optgroup label="Selection" class="text-slate-500">
            <option v-for="t in typeGroups.selection" :key="t" :value="t">{{ t }}</option>
          </optgroup>
          <optgroup label="Date / Time" class="text-slate-500">
            <option v-for="t in typeGroups.datetime" :key="t" :value="t">{{ t }}</option>
          </optgroup>
          <optgroup label="Reference" class="text-slate-500">
            <option v-for="t in typeGroups.reference" :key="t" :value="t">{{ t }}</option>
          </optgroup>
        </select>
        <div class="w-px h-6 bg-white/[0.07]"></div>
        <button class="flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-indigo-300 hover:text-white hover:bg-indigo-600/20 transition-all duration-150 whitespace-nowrap" @click="addField">
          <Plus :size="15" /> Tambah Field
        </button>
      </div>
      <!-- Right actions -->
      <button class="btn-ghost-sm" @click="$emit('import')"><Code2 :size="13" /> Import</button>
      <button class="btn-ghost-sm" @click="copyJson"><ClipboardCopy :size="13" /> Salin</button>
    </div>

    <!-- ═══ Field List ═══ -->
    <div class="grid gap-2">
      <div
        v-for="(f, idx) in fieldList"
        :key="f.key"
        class="bg-[#0f1117] border border-white/[0.07] rounded-xl overflow-hidden animate-fade-in"
        :class="[
          open[f.key] ? 'border-l-2' : 'border-l-2',
          typeGroupColor(f.def.type)
        ]"
      >
        <!-- ── Collapsed / Header Row ── -->
        <div
          class="flex items-center justify-between px-3.5 py-2.5 cursor-pointer hover:bg-white/[0.02] transition-colors duration-100"
          @click="toggleOpen(f.key)"
        >
          <div class="flex items-center gap-2.5 min-w-0 flex-1">
            <!-- Type icon -->
            <span class="flex-shrink-0" :class="typeGroupTextColor(f.def.type)">
              <component :is="typeIcon(f.def.type)" :size="15" />
            </span>
            <!-- Index number -->
            <span class="text-slate-600 text-[11px] font-mono tabular-nums w-5 text-right flex-shrink-0">{{ idx + 1 }}</span>
            <!-- Field name -->
            <span class="font-mono text-sm text-slate-100 truncate">{{ f.key }}</span>
            <!-- Type badge -->
            <span
              class="badge flex-shrink-0"
              :class="typeBadgeClass(f.def.type)"
            >{{ f.def.type || 'string' }}</span>
            <!-- Label -->
            <span v-if="f.def.label && !open[f.key]" class="text-xs text-slate-400 truncate hidden sm:inline">— {{ f.def.label }}</span>
            <!-- Flags inline -->
            <span v-if="f.def.required" class="text-[10px] font-medium text-red-400 flex-shrink-0">required</span>
            <span v-if="f.def.unique" class="text-[10px] font-medium text-amber-400 flex-shrink-0">unique</span>
            <span v-if="f.def.type === 'relation'" class="text-[10px] font-medium text-indigo-400 flex-shrink-0">relation</span>
            <span v-if="f.def.hidden" class="text-[10px] font-medium text-slate-500 flex-shrink-0">hidden</span>
          </div>
          <!-- Actions -->
          <div class="flex items-center gap-0.5 text-slate-500 flex-shrink-0 ml-2">
            <button @click.stop="toggleVisibility(f.key)" class="p-1 rounded-md hover:bg-white/[0.06] hover:text-slate-300 transition-colors duration-100" :title="f.def.hidden ? 'Tampilkan field' : 'Sembunyikan field'">
              <EyeOff v-if="f.def.hidden" :size="14" class="text-slate-600" />
              <Eye v-else :size="14" />
            </button>
            <button @click.stop="moveUp(idx)" class="p-1 rounded-md hover:bg-white/[0.06] hover:text-slate-300 transition-colors duration-100" :disabled="idx === 0" :class="{ 'opacity-30 pointer-events-none': idx === 0 }">
              <ArrowUp :size="14" />
            </button>
            <button @click.stop="moveDown(idx)" class="p-1 rounded-md hover:bg-white/[0.06] hover:text-slate-300 transition-colors duration-100" :disabled="idx === fieldList.length - 1" :class="{ 'opacity-30 pointer-events-none': idx === fieldList.length - 1 }">
              <ArrowDown :size="14" />
            </button>
            <button @click.stop="duplicateField(f)" class="p-1 rounded-md hover:bg-white/[0.06] hover:text-slate-300 transition-colors duration-100" title="Duplikat">
              <Copy :size="14" />
            </button>
            <button @click.stop="copyFieldJson(f)" class="p-1 rounded-md hover:bg-white/[0.06] hover:text-slate-300 transition-colors duration-100" title="Salin JSON field">
              <ClipboardCopy :size="14" />
            </button>
            <button @click.stop="removeField(f.key)" class="p-1 rounded-md hover:bg-red-500/10 hover:text-red-400 transition-colors duration-100" title="Hapus field">
              <Trash2 :size="14" />
            </button>
            <span class="text-slate-600 ml-0.5">
              <ChevronDown v-if="open[f.key]" :size="14" />
              <ChevronRight v-else :size="14" />
            </span>
          </div>
        </div>

        <!-- ── Expanded Body ── -->
        <div v-show="open[f.key]" class="px-4 pb-4 border-t border-white/[0.04] pt-3">
          <div class="grid md:grid-cols-3 gap-4 text-sm">
            <!-- Left: Core -->
            <div class="space-y-3 md:col-span-1">
              <div>
                <label class="section-label">Nama field</label>
                <input :value="f.key" @change="renameField(f.key, $event.target.value)" class="input text-sm font-mono" />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="section-label">Tipe</label>
                  <select v-model="modelValue[f.key].type" class="input text-sm appearance-none cursor-pointer">
                    <optgroup label="Text" class="text-slate-500">
                      <option v-for="t in typeGroups.text" :key="t" :value="t">{{ t }}</option>
                    </optgroup>
                    <optgroup label="Numbers" class="text-slate-500">
                      <option v-for="t in typeGroups.numbers" :key="t" :value="t">{{ t }}</option>
                    </optgroup>
                    <optgroup label="Boolean" class="text-slate-500">
                      <option v-for="t in typeGroups.boolean" :key="t" :value="t">{{ t }}</option>
                    </optgroup>
                    <optgroup label="Structured" class="text-slate-500">
                      <option v-for="t in typeGroups.structured" :key="t" :value="t">{{ t }}</option>
                    </optgroup>
                    <optgroup label="Selection" class="text-slate-500">
                      <option v-for="t in typeGroups.selection" :key="t" :value="t">{{ t }}</option>
                    </optgroup>
                    <optgroup label="Date / Time" class="text-slate-500">
                      <option v-for="t in typeGroups.datetime" :key="t" :value="t">{{ t }}</option>
                    </optgroup>
                    <optgroup label="Reference" class="text-slate-500">
                      <option v-for="t in typeGroups.reference" :key="t" :value="t">{{ t }}</option>
                    </optgroup>
                  </select>
                </div>
                <div>
                  <label class="section-label">Label</label>
                  <input v-model="modelValue[f.key].label" class="input text-sm" :placeholder="toLabel(f.key)" />
                </div>
              </div>
              <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-slate-300 pt-1">
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" v-model="modelValue[f.key].required" class="rounded" /> required</label>
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" v-model="modelValue[f.key].unique" class="rounded" /> unique</label>
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" v-model="modelValue[f.key].readonly" class="rounded" /> readonly</label>
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" v-model="modelValue[f.key].hidden" class="rounded" /> hidden</label>
              </div>
              <div class="grid grid-cols-3 gap-2">
                <div><label class="section-label">min</label>
                  <input type="number" v-model.number="modelValue[f.key].min" class="input text-sm" /></div>
                <div><label class="section-label">max</label>
                  <input type="number" v-model.number="modelValue[f.key].max" class="input text-sm" /></div>
                <div><label class="section-label">default</label>
                  <input v-model="defaultInputs[f.key]" @change="applyDefault(f.key)" class="input text-sm" placeholder="auto" /></div>
              </div>
              <div>
                <label class="section-label">regex</label>
                <input v-model="modelValue[f.key].regex" class="input text-sm font-mono" placeholder="/^...$/" />
              </div>
            </div>

            <!-- Middle: UI & Table -->
            <div class="space-y-3">
              <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider">UI / Tabel</div>
              <div class="grid grid-cols-2 gap-2">
                <input v-model="modelValue[f.key].placeholder" @input="ensureUi(f.key)" placeholder="placeholder" class="input text-sm" />
                <input v-model="modelValue[f.key].icon" @input="ensureUi(f.key)" placeholder="icon name" class="input text-sm" />
              </div>
              <div v-if="isTextLike(f.def)">
                <label class="section-label">rows</label>
                <input type="number" v-model.number="modelValue[f.key].rows" min="1" max="20" class="input text-sm w-24" />
              </div>
              <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-slate-300">
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" v-model="modelValue[f.key].filterable" class="rounded" /> filterable</label>
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" v-model="modelValue[f.key].sortable" class="rounded" /> sortable</label>
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" v-model="modelValue[f.key].index" class="rounded" /> index</label>
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" v-model="modelValue[f.key].searchable" class="rounded" /> searchable</label>
              </div>

              <!-- Enum options -->
              <div v-if="isEnumType(f.def)" class="space-y-2">
                <label class="section-label">Opsi enum (pisahkan koma)</label>
                <input
                  :value="(modelValue[f.key].options || modelValue[f.key].enum || []).join(', ')"
                  @change="setEnumOptions(f.key, $event.target.value)"
                  class="input text-sm"
                  placeholder="rendah, sedang, tinggi"
                />
                <label class="flex items-center gap-2 text-xs cursor-pointer">
                  <input type="checkbox" :checked="!!modelValue[f.key].ui?.badge" @change="toggleBadge(f.key, $event.target.checked)" class="rounded" /> badge UI
                </label>
                <div v-if="modelValue[f.key].ui?.badge" class="space-y-1.5">
                  <div class="text-[11px] text-slate-500">Warna badge per opsi:</div>
                  <div v-for="opt in enumOpts(f.def)" :key="opt" class="flex items-center gap-2">
                    <span class="w-20 text-xs truncate text-slate-300">{{ opt }}</span>
                    <select :value="getBadgeColor(f.key, opt)" @change="setBadgeColor(f.key, opt, $event.target.value)" class="input-sm w-28">
                      <option v-for="c in badgeColors" :key="c" :value="c">{{ c }}</option>
                    </select>
                    <span :class="previewBadge(getBadgeColor(f.key, opt))">{{ opt }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right: Relation / Advanced -->
            <div class="space-y-3">
              <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Relasi / Lanjutan</div>

              <div v-if="f.def.type === 'relation'" class="space-y-2">
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="section-label">Database</label>
                    <input v-model="modelValue[f.key].relation.db" placeholder="db" class="input text-sm" />
                  </div>
                  <div>
                    <label class="section-label">Collection</label>
                    <input v-model="modelValue[f.key].relation.collection" placeholder="collection" class="input text-sm" />
                  </div>
                  <div>
                    <label class="section-label">Field</label>
                    <input v-model="modelValue[f.key].relation.field" placeholder="_id" class="input text-sm" />
                  </div>
                  <div>
                    <label class="section-label">Display</label>
                    <input v-model="modelValue[f.key].relation.display" placeholder="name" class="input text-sm" />
                  </div>
                </div>
                <p class="text-[11px] text-slate-600">Contoh: db=auth, collection=users, field=_id, display=name</p>
              </div>
              <div v-else class="text-xs text-slate-600">
                Ubah tipe ke <span class="text-indigo-400 font-mono">relation</span> untuk mengaktifkan konfigurasi join.
              </div>

              <details class="text-xs group">
                <summary class="cursor-pointer text-slate-500 hover:text-slate-300 transition-colors select-none">Raw field JSON</summary>
                <pre class="code-block mt-2 max-h-40 text-[11px]">{{ JSON.stringify(modelValue[f.key], null, 2) }}</pre>
              </details>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ Empty State ═══ -->
      <div v-if="!fieldList.length" class="empty-state py-20">
        <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center mx-auto mb-5">
          <Layers :size="28" class="text-indigo-400" />
        </div>
        <h3 class="text-base font-semibold text-slate-300 mb-1">Belum ada field</h3>
        <p class="text-sm text-slate-600 max-w-md mx-auto mb-6">
          Mulai bangun schema dengan menambahkan field. Pilih tipe dari berbagai kategori berikut:
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 max-w-lg mx-auto mb-6 text-xs">
          <div v-for="(group, label) in typeGroupList" :key="label"
            class="flex items-center gap-2 px-3 py-2 rounded-lg bg-[#161922] border border-white/[0.05]">
            <span :class="group.color"><component :is="group.icon" :size="14" /></span>
            <span class="text-slate-400">{{ label }}</span>
            <span class="text-slate-600 ml-auto">{{ group.count }}</span>
          </div>
        </div>
        <button class="btn" @click="$refs.nameInput && $refs.nameInput.focus()">
          <Plus :size="15" /> Tambah Field Pertama
        </button>
      </div>
    </div>

    <!-- ═══ Footer Summary ═══ -->
    <div v-if="fieldList.length" class="flex flex-wrap justify-between items-center gap-3 text-xs text-slate-500 pt-2 border-t border-white/[0.04]">
      <div class="flex items-center gap-3 flex-wrap">
        <span class="text-slate-400 font-medium">{{ fieldList.length }} field</span>
        <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-red-400/60"></span>{{ requiredCount }} required</span>
        <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-amber-400/60"></span>{{ uniqueCount }} unique</span>
        <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-indigo-400/60"></span>{{ relationCount }} relasi</span>
        <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400/60"></span>{{ indexedCount }} terindeks</span>
      </div>
      <div class="flex gap-2">
        <button class="btn-ghost-sm" @click="sortAlpha">Urutkan A-Z</button>
        <button class="btn-ghost-sm" @click="$emit('validate')">Validasi Schema</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import {
  Plus, ArrowUp, ArrowDown, Copy, Trash2, ChevronDown, ChevronRight, Code2,
  Type, AlignLeft, Mail, Lock, Globe, Hash,
  Percent, ToggleLeft, CheckSquare, ToggleRight,
  Brackets, Braces,
  List, Tag,
  Calendar, CalendarClock, Clock, Link,
  Eye, EyeOff, ClipboardCopy, Layers,
} from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:modelValue', 'validate', 'import'])

// ── Type groups ──
const typeGroups = {
  text: ['string', 'text', 'email', 'password', 'url', 'slug'],
  numbers: ['int', 'integer', 'float', 'double', 'number', 'decimal'],
  boolean: ['bool', 'boolean', 'checkbox', 'switch'],
  structured: ['array', 'object', 'json'],
  selection: ['enum', 'tags'],
  datetime: ['date', 'datetime', 'time'],
  reference: ['relation'],
}

const allTypes = Object.values(typeGroups).flat()

// ── Type icon map ──
const typeIconMap = {
  string: Type, text: AlignLeft, email: Mail, password: Lock, url: Globe, slug: Hash,
  int: Hash, integer: Hash, float: Percent, double: Percent, number: Percent, decimal: Percent,
  bool: ToggleLeft, boolean: ToggleLeft, checkbox: CheckSquare, switch: ToggleRight,
  array: Brackets, object: Braces, json: Braces,
  enum: List, tags: Tag,
  date: Calendar, datetime: CalendarClock, time: Clock,
  relation: Link,
}

// ── Type → group mapping ──
const typeToGroup = {}
for (const [group, types] of Object.entries(typeGroups)) {
  for (const t of types) typeToGroup[t] = group
}

// ── Left border colors per group ──
const groupBorderColor = {
  text: 'border-l-blue-500',
  numbers: 'border-l-emerald-500',
  boolean: 'border-l-amber-500',
  structured: 'border-l-violet-500',
  selection: 'border-l-cyan-500',
  datetime: 'border-l-rose-500',
  reference: 'border-l-indigo-500',
}

const groupTextColor = {
  text: 'text-blue-400',
  numbers: 'text-emerald-400',
  boolean: 'text-amber-400',
  structured: 'text-violet-400',
  selection: 'text-cyan-400',
  datetime: 'text-rose-400',
  reference: 'text-indigo-400',
}

const groupBadgeClass = {
  text: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  numbers: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  boolean: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  structured: 'bg-violet-500/10 border-violet-500/20 text-violet-400',
  selection: 'bg-cyan-500/10 border-cyan-500/20 text-cyan-400',
  datetime: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
  reference: 'bg-indigo-500/10 border-indigo-500/20 text-indigo-400',
}

function typeGroupColor(type) {
  const g = typeToGroup[type] || 'text'
  return groupBorderColor[g] || ''
}

function typeGroupTextColor(type) {
  const g = typeToGroup[type] || 'text'
  return groupTextColor[g] || 'text-slate-400'
}

function typeBadgeClass(type) {
  const g = typeToGroup[type] || 'text'
  return groupBadgeClass[g] || ''
}

function typeIcon(type) {
  return typeIconMap[type] || Type
}

// ── Empty state type group list ──
const typeGroupList = {
  'Text': { icon: Type, color: 'text-blue-400', count: typeGroups.text.length },
  'Numbers': { icon: Hash, color: 'text-emerald-400', count: typeGroups.numbers.length },
  'Boolean': { icon: ToggleLeft, color: 'text-amber-400', count: typeGroups.boolean.length },
  'Structured': { icon: Braces, color: 'text-violet-400', count: typeGroups.structured.length },
  'Selection': { icon: List, color: 'text-cyan-400', count: typeGroups.selection.length },
  'Date/Time': { icon: Calendar, color: 'text-rose-400', count: typeGroups.datetime.length },
  'Reference': { icon: Link, color: 'text-indigo-400', count: typeGroups.reference.length },
}

// ── State ──
const open = reactive({})
const newFieldName = ref('')
const newFieldType = ref('string')
const defaultInputs = ref({})

const fieldList = computed(() => {
  return Object.entries(props.modelValue || {}).map(([key, def]) => ({ key, def }))
})

const requiredCount = computed(() => fieldList.value.filter(f => f.def.required).length)
const uniqueCount = computed(() => fieldList.value.filter(f => f.def.unique).length)
const relationCount = computed(() => fieldList.value.filter(f => f.def.type === 'relation').length)
const indexedCount = computed(() => fieldList.value.filter(f => f.def.index || f.def.sortable || f.def.filterable).length)

const badgeColors = ['slate', 'gray', 'blue', 'green', 'amber', 'red', 'violet']

// ── Actions ──
function toggleOpen(k) { open[k] = !open[k] }

function addField() {
  let name = (newFieldName.value || '').trim()
    .replace(/\s+/g, '_')
    .replace(/[^a-zA-Z0-9_]/g, '')
  if (!name) { alert('Nama field wajib diisi'); return }
  if (props.modelValue[name]) { alert('Field sudah ada'); return }
  const next = { ...props.modelValue }
  next[name] = {
    type: newFieldType.value,
    label: toLabel(name),
  }
  if (['enum'].includes(newFieldType.value)) {
    next[name].options = ['option1', 'option2']
  }
  if (newFieldType.value === 'relation') {
    next[name].relation = { db: 'app', collection: 'users', field: '_id', display: 'name' }
  }
  if (['text'].includes(newFieldType.value)) {
    next[name].rows = 4
  }
  emit('update:modelValue', next)
  open[name] = true
  newFieldName.value = ''
}

function removeField(key) {
  if (!confirm('Hapus field "' + key + '"?')) return
  const n = { ...props.modelValue }
  delete n[key]
  emit('update:modelValue', n)
}

function renameField(oldKey, newKey) {
  newKey = (newKey || '').trim().replace(/\s+/g, '_')
  if (!newKey || newKey === oldKey) return
  if (props.modelValue[newKey]) { alert('Nama sudah dipakai'); return }
  const n = {}
  for (const [k, v] of Object.entries(props.modelValue)) {
    n[k === oldKey ? newKey : k] = v
  }
  emit('update:modelValue', n)
  open[newKey] = open[oldKey]
  delete open[oldKey]
}

function moveUp(idx) {
  const keys = Object.keys(props.modelValue)
  if (idx <= 0) return
  const a = keys[idx - 1], b = keys[idx]
  const n = {}
  keys.forEach((k, i) => {
    if (i === idx - 1) n[b] = props.modelValue[b]
    else if (i === idx) n[a] = props.modelValue[a]
    else n[k] = props.modelValue[k]
  })
  emit('update:modelValue', n)
}

function moveDown(idx) {
  const keys = Object.keys(props.modelValue)
  if (idx >= keys.length - 1) return
  moveUp(idx + 1)
}

function duplicateField(f) {
  let base = f.key + '_copy'
  let n = base, i = 2
  while (props.modelValue[n]) { n = base + i++ }
  const next = { ...props.modelValue, [n]: JSON.parse(JSON.stringify(f.def)) }
  emit('update:modelValue', next)
  open[n] = true
}

function toggleVisibility(key) {
  const m = { ...props.modelValue }
  m[key] = { ...m[key], hidden: !m[key].hidden }
  emit('update:modelValue', m)
}

function sortAlpha() {
  const sorted = Object.keys(props.modelValue).sort()
  const n = {}
  sorted.forEach(k => n[k] = props.modelValue[k])
  emit('update:modelValue', n)
}

function copyJson() {
  navigator.clipboard.writeText(JSON.stringify(props.modelValue, null, 2))
    .then(() => alert('SSOT JSON berhasil disalin!'))
    .catch(() => {})
}

function copyFieldJson(f) {
  navigator.clipboard.writeText(JSON.stringify(f.def, null, 2))
    .then(() => alert('JSON field "' + f.key + '" berhasil disalin!'))
    .catch(() => {})
}

// ── Helpers ──
function isEnumType(def) { return def.type === 'enum' || def.enum || def.options }
function isTextLike(def) { return ['text', 'textarea'].includes(def.type) || def.rows }
function enumOpts(def) { return def.options || def.enum || [] }
function toLabel(s) { return s.replace(/_/g, ' ').replace(/\b\w/g, m => m.toUpperCase()) }

function setEnumOptions(key, str) {
  const arr = str.split(',').map(s => s.trim()).filter(Boolean)
  const m = { ...props.modelValue }
  m[key] = { ...m[key], options: arr }
  delete m[key].enum
  emit('update:modelValue', m)
}

function toggleBadge(key, on) {
  const m = { ...props.modelValue }
  m[key] = { ...m[key], ui: { ...(m[key].ui || {}), badge: on } }
  if (on && !m[key].ui.color) m[key].ui.color = {}
  emit('update:modelValue', m)
}

function getBadgeColor(key, opt) {
  return props.modelValue[key]?.ui?.color?.[opt] || 'slate'
}

function setBadgeColor(key, opt, color) {
  const m = { ...props.modelValue }
  const ui = { ...(m[key].ui || {}), badge: true, color: { ...(m[key].ui?.color || {}), [opt]: color } }
  m[key] = { ...m[key], ui }
  emit('update:modelValue', m)
}

function previewBadge(color) {
  const map = {
    gray: 'bg-slate-700 text-slate-200 px-2 py-0.5 rounded-full text-[10px]',
    blue: 'bg-blue-900 text-blue-200 px-2 py-0.5 rounded-full text-[10px]',
    green: 'bg-emerald-900 text-emerald-200 px-2 py-0.5 rounded-full text-[10px]',
    amber: 'bg-amber-900 text-amber-200 px-2 py-0.5 rounded-full text-[10px]',
    red: 'bg-red-900 text-red-200 px-2 py-0.5 rounded-full text-[10px]',
    violet: 'bg-violet-900 text-violet-200 px-2 py-0.5 rounded-full text-[10px]',
    slate: 'bg-slate-800 text-slate-200 px-2 py-0.5 rounded-full text-[10px]',
  }
  return map[color] || map.slate
}

function ensureUi(key) {
  const m = { ...props.modelValue }
  if (!m[key].ui) m[key].ui = {}
  emit('update:modelValue', m)
}

function applyDefault(key) {
  const v = defaultInputs.value[key]
  if (v === undefined || v === '') return
  const def = props.modelValue[key]
  let parsed = v
  if (['int', 'integer'].includes(def.type)) parsed = parseInt(v, 10)
  else if (['float', 'double', 'number', 'decimal'].includes(def.type)) parsed = parseFloat(v)
  else if (['bool', 'boolean', 'checkbox', 'switch'].includes(def.type)) parsed = ['true', '1', 'yes', 'on'].includes(String(v).toLowerCase())
  else if (['array', 'object', 'json'].includes(def.type)) { try { parsed = JSON.parse(v) } catch { } }
  const m = { ...props.modelValue }
  m[key] = { ...m[key], default: parsed }
  emit('update:modelValue', m)
}
</script>