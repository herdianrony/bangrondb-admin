<template>
  <div class="space-y-5 animate-fade-in">
    <!-- Breadcrumb & Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3 min-w-0">
        <a :href="`/databases/${db}`" class="btn-ghost-sm !p-2 flex-shrink-0" title="Back to Database">
          <ArrowLeft class="w-4 h-4" />
        </a>
        <div class="min-w-0">
          <h1 class="page-title truncate">{{ col }}</h1>
          <p class="page-desc flex items-center gap-2">
            <Database class="w-3 h-3" />
            <a :href="`/databases/${db}`" class="font-mono text-slate-400 hover:text-indigo-400 transition-colors">{{ db }}</a>
            <span class="text-slate-600">/</span>
            <span class="font-mono text-slate-400">{{ col }}</span>
            <span v-if="Object.keys(schema).length" class="text-slate-700">|</span>
            <span v-if="Object.keys(schema).length" class="text-slate-500">{{ Object.keys(schema).length }} fields</span>
          </p>
        </div>
      </div>
      <button class="btn flex-shrink-0" @click="openCreate">
        <Plus class="w-4 h-4" />
        <span class="hidden sm:inline">New Record</span>
      </button>
    </div>

    <!-- Toolbar -->
    <div class="card !p-3">
      <div class="flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[200px] max-w-sm">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
          <input v-model="searchText" @keyup.enter="applySearch" :placeholder="`Search in ${col}...`" class="input !pl-10 !py-2 !rounded-lg text-sm" />
          <button v-if="searchText" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-md hover:bg-white/[0.06] text-slate-500 hover:text-slate-300" @click="clearSearch">
            <X class="w-3.5 h-3.5" />
          </button>
        </div>
        <div class="flex-1"></div>
        <div class="hidden md:flex items-center gap-3 text-xs border-l border-white/[0.07] pl-3">
          <label class="flex items-center gap-1.5 cursor-pointer text-slate-400 hover:text-slate-300">
            <input type="checkbox" v-model="withTrashed" @change="loadDocs" class="rounded" />
            withTrashed
          </label>
          <label class="flex items-center gap-1.5 cursor-pointer text-slate-400 hover:text-slate-300">
            <input type="checkbox" v-model="onlyTrashed" @change="loadDocs" class="rounded" />
            onlyTrashed
          </label>
        </div>
        <button class="btn-ghost-sm" @click="showFilters = !showFilters" :class="{ '!bg-indigo-500/10 !border-indigo-500/30 !text-indigo-300': showFilters }">
          <Filter class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Filters</span>
        </button>
        <div class="relative" ref="colMenuRef">
          <button class="btn-ghost-sm" @click="colMenuOpen = !colMenuOpen">
            <Columns3 class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Columns</span>
          </button>
          <Transition name="dropdown">
            <div v-if="colMenuOpen" class="col-dropdown">
              <div class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">
                Toggle Columns ({{ visibleColCount }}/{{ allColumns.length }})
              </div>
              <div class="max-h-60 overflow-y-auto">
                <label v-for="c in allColumns" :key="c.field" class="col-dropdown-item">
                  <input type="checkbox" v-model="c.visible" class="rounded" />
                  <span class="truncate">{{ c.label }}</span>
                  <span class="text-[10px] text-slate-600 font-mono ml-auto flex-shrink-0">{{ shortType(c.type) }}</span>
                </label>
              </div>
            </div>
          </Transition>
        </div>
        <button class="btn-ghost-sm !p-2" @click="loadDocs" title="Refresh">
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <!-- Status Chips -->
    <div class="flex flex-wrap items-center gap-2">
      <span class="badge badge-info"><Hash class="w-3 h-3 mr-1" />{{ total }} records</span>
      <span v-if="searchText" class="badge badge-warning">
        <Search class="w-3 h-3 mr-1" />Filtered: "{{ searchText }}"
        <button @click="clearSearch" class="ml-1 hover:text-white"><X class="w-3 h-3 inline" /></button>
      </span>
    </div>

    <!-- Data Table -->
    <div class="card !p-0 overflow-hidden">
      <DataTable
        :columns="visibleColumns"
        :data="docs"
        :total="total"
        :loading="loading"
        :selectable="true"
        :expandable="true"
        :show-filter-row="showFilters"
        :sort-field="sortField"
        :sort-dir="sortDir"
        :page-size="pageSize"
        :current-page="currentPage"
        :offset="(currentPage - 1) * pageSize"
        :relation-cache="relationCache"
        empty-title="No records found"
        :empty-subtitle="searchText ? 'Try a different search term' : 'Create your first record in this collection'"
        @sort="onSort"
        @apply-filters="onApplyFilters"
        @clear-filters="onClearFilters"
        @edit="openEdit"
        @delete="confirmDelete"
        @cell-edit="onCellEdit"
        @go-to-page="goToPage"
        @update:page-size="onPageSizeChange"
        @select="onSelect"
        @select-all="onSelectAll"
      >
        <template #bulk-actions="{ ids, clear }">
          <button class="btn-sm !bg-red-600/90 hover:!bg-red-500 !text-white flex items-center gap-1.5" @click="bulkDelete(ids, clear)">
            <Trash2 class="w-3.5 h-3.5" />Delete ({{ ids.length }})
          </button>
        </template>
      </DataTable>
    </div>

    <!-- Document Form Modal -->
    <DocumentFormModal :visible="showModal" :document="editingDoc" :schema="schema" :db="db" :collection="col" @close="showModal = false" @saved="onSaved" />

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape="deleteTarget = null">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="deleteTarget = null"></div>
        <div class="relative card w-full max-w-sm animate-scale-in">
          <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-red-500/10 border border-red-500/20 grid place-items-center flex-shrink-0">
              <AlertTriangle class="w-5 h-5 text-red-400" />
            </div>
            <div>
              <h3 class="font-bold text-white text-sm">Delete Document</h3>
              <p class="text-xs text-slate-400 mt-1">Permanently delete <span class="font-mono text-slate-300">{{ deleteTarget._id }}</span>?</p>
            </div>
          </div>
          <div class="flex justify-end gap-2">
            <button class="btn-ghost-sm" @click="deleteTarget = null">Cancel</button>
            <button class="btn-sm !bg-red-600 hover:!bg-red-500" @click="doDelete" :disabled="deleting">
              <Loader2 v-if="deleting" class="w-3.5 h-3.5 animate-spin" />
              <Trash2 v-else class="w-3.5 h-3.5" />Delete
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import DataTable from '@/Components/DataTable.vue'
import DocumentFormModal from '@/Components/DocumentFormModal.vue'
import { useToast } from '@/composables/useToast'
import { confirm as confirmDialog } from '@/composables/useConfirm'
import {
  Database, Search, Plus, ArrowLeft, RefreshCw, Filter,
  Columns3, Hash, Trash2, X, AlertTriangle, Loader2,
} from 'lucide-vue-next'

const page = usePage()
const db = computed(() => page.props.db || '')
const col = computed(() => page.props.col || '')
const api = axios.create({ baseURL: '' })
const toast = useToast()

const docs = ref([])
const total = ref(0)
const loading = ref(false)
const schema = ref({})
const relationCache = ref({})
const sortField = ref('_id')
const sortDir = ref(-1)
const pageSize = ref(25)
const currentPage = ref(1)
const showFilters = ref(false)
const columnFilters = ref({})
const searchText = ref('')
const withTrashed = ref(false)
const onlyTrashed = ref(false)
const showModal = ref(false)
const editingDoc = ref(null)
const deleteTarget = ref(null)
const deleting = ref(false)
const colMenuOpen = ref(false)
const colMenuRef = ref(null)

const allColumns = computed(() => {
  if (!Object.keys(schema.value).length) {
    const first = docs.value[0] || {}
    return Object.keys(first).filter(k => k !== '_id').slice(0, 8).map(f => ({
      field: f, label: f, type: typeof first[f], sortable: true, filterable: true, visible: ref(true),
    }))
  }
  return Object.entries(schema.value).map(([field, def]) => ({
    field, label: def.label || field, type: def.type || 'string',
    sortable: !!def.sortable, filterable: !!def.filterable,
    badge: def.ui?.badge || false, colorMap: def.ui?.color || null,
    relation: def.relation || null, readonly: !!def.readonly,
    enumOptions: def.options || [], visible: ref(!def.hidden),
  }))
})
const visibleColumns = computed(() => allColumns.value.filter(c => c.visible.value).map(c => ({ ...c, hidden: false })))
const visibleColCount = computed(() => allColumns.value.filter(c => c.visible.value).length)
const relationFields = computed(() => visibleColumns.value.filter(c => c.relation))

onMounted(async () => {
  await loadSchema()
  await loadDocs()
})

function handleClickOutside(e) {
  if (colMenuRef.value && !colMenuRef.value.contains(e.target)) colMenuOpen.value = false
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

async function loadSchema() {
  if (!db.value || !col.value) { schema.value = {}; return }
  try {
    const r = await api.get(`/databases/${db.value}/collections/${col.value}/schema`)
    schema.value = r.data.schema || {}
  } catch { schema.value = {} }
}

async function loadDocs() {
  if (!db.value || !col.value) return
  loading.value = true
  try {
    const sort = sortField.value ? { [sortField.value]: sortDir.value } : {}
    let filter = buildFilter()
    const params = {
      filter: JSON.stringify(filter), sort: JSON.stringify(sort),
      limit: pageSize.value, skip: (currentPage.value - 1) * pageSize.value,
      with_trashed: withTrashed.value ? 1 : 0, only_trashed: onlyTrashed.value ? 1 : 0,
    }
    const r = await api.get(`/databases/${db.value}/collections/${col.value}/documents`, { params })
    docs.value = r.data.data
    total.value = r.data.total
    if (relationFields.value.length) await populateRelations()
  } catch (e) {
    toast.error('Gagal memuat dokumen: ' + (e.response?.data?.message || e.message))
  } finally { loading.value = false }
}

function buildFilter() {
  const parts = []
  for (const [k, v] of Object.entries(columnFilters.value)) {
    if (!v) continue
    if (!isNaN(v) && v.trim() !== '') parts.push({ [k]: Number(v) })
    else parts.push({ [k]: { $regex: v } })
  }
  if (searchText.value) {
    const searchParts = []
    for (const c of visibleColumns.value) {
      if (['string','text','email','url','slug'].includes(c.type)) searchParts.push({ [c.field]: { $regex: searchText.value } })
    }
    if (searchParts.length === 1) parts.push(searchParts[0])
    else if (searchParts.length > 1) parts.push({ $or: searchParts })
  }
  if (!parts.length) return {}
  return parts.length === 1 ? parts[0] : { $and: parts }
}

async function populateRelations() {
  for (const c of relationFields.value) {
    const rel = c.relation
    if (!rel?.db || !rel?.collection) continue
    const ids = [...new Set(docs.value.map(d => d[c.field]).filter(Boolean))]
    if (!ids.length) continue
    const cacheKey = `${rel.db}.${rel.collection}`
    if (!relationCache.value[cacheKey]) relationCache.value[cacheKey] = {}
    const missing = ids.filter(id => !(id in relationCache.value[cacheKey]))
    if (missing.length) {
      try {
        const res = await api.post(`/databases/${rel.db}/collections/${rel.collection}/query`, { filter: { [rel.field || '_id']: { $in: missing } }, limit: 200 })
        for (const item of (res.data.data || [])) {
          relationCache.value[cacheKey][item[rel.field || '_id']] = item[rel.display] || item.name || item.title || item[rel.field || '_id']
        }
      } catch {}
    }
  }
}

function onSort({ field, dir }) { sortField.value = field; sortDir.value = dir; currentPage.value = 1; loadDocs() }
function onApplyFilters(f) { columnFilters.value = { ...f }; currentPage.value = 1; loadDocs() }
function onClearFilters() { columnFilters.value = {}; loadDocs() }
function applySearch() { currentPage.value = 1; loadDocs() }
function clearSearch() { searchText.value = ''; currentPage.value = 1; loadDocs() }
function goToPage(p) { const max = Math.max(1, Math.ceil(total.value / pageSize.value)); currentPage.value = Math.max(1, Math.min(p, max)); loadDocs() }
function onPageSizeChange(s) { pageSize.value = s; currentPage.value = 1; loadDocs() }
function onSelect() {}
function onSelectAll() {}
function showAllCols() { allColumns.value.forEach(c => c.visible.value = true) }
function hideAllCols() { allColumns.value.forEach(c => c.visible.value = false) }

function openCreate() { editingDoc.value = null; showModal.value = true }
function openEdit(doc) { editingDoc.value = { ...doc }; showModal.value = true }
function confirmDelete(doc) { deleteTarget.value = doc }

async function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await api.delete(`/databases/${db.value}/collections/${col.value}/documents`, { params: { filter: JSON.stringify({ _id: deleteTarget.value._id }) } })
    toast.success('Dokumen berhasil dihapus'); deleteTarget.value = null; loadDocs()
  } catch (e) { toast.error('Gagal: ' + (e.response?.data?.message || e.message)) }
  finally { deleting.value = false }
}

async function bulkDelete(ids, clear) {
  if (!(await confirmDialog({ title: 'Hapus Dokumen', message: `Hapus ${ids.length} dokumen?`, confirmText: 'Hapus', danger: true }))) return
  try {
    await api.delete(`/databases/${db.value}/collections/${col.value}/documents`, { params: { filter: JSON.stringify({ _id: { $in: ids } }) } })
    toast.success(`${ids.length} dokumen dihapus`); clear(); loadDocs()
  } catch (e) { toast.error('Gagal: ' + (e.response?.data?.message || e.message)) }
}

async function onCellEdit({ id, field, value }) {
  try {
    await api.post(`/databases/${db.value}/collections/${col.value}/save`, { _id: id, [field]: value })
    toast.success('Field updated'); loadDocs()
  } catch (e) { toast.error('Update gagal: ' + (e.response?.data?.message || e.message)) }
}

function onSaved() {
  toast.success(editingDoc.value ? 'Dokumen diperbarui' : 'Dokumen dibuat')
  loadDocs()
}

function shortType(t) {
  const m = { string:'str', text:'txt', email:'mail', url:'url', int:'int', float:'flt', bool:'bool', array:'arr', object:'obj', enum:'enum', tags:'tags', date:'date', datetime:'dt', relation:'rel' }
  return m[t] || (t||'').slice(0,4)
}
</script>

<style scoped>
.dropdown-enter-active { transition: all 0.15s cubic-bezier(0.16,1,0.3,1); }
.dropdown-leave-active { transition: all 0.1s ease-in; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px) scale(0.95); }
</style>