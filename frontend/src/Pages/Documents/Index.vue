<template>
  <div class="space-y-5 animate-fade-in">
    <!-- ═══ Breadcrumb & Header ═══ -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3 min-w-0">
        <a href="/app/collections" class="btn-ghost-sm !p-2 flex-shrink-0" title="Back to Collections">
          <ArrowLeft class="w-4 h-4" />
        </a>
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <h1 class="page-title truncate">{{ collection || 'Documents' }}</h1>
            <span v-if="loading" class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
          </div>
          <p class="page-desc flex items-center gap-2">
            <Database class="w-3 h-3" />
            <span class="font-mono text-slate-400">{{ db }}</span>
            <span v-if="collection" class="text-slate-600">/</span>
            <span v-if="collection" class="font-mono text-slate-400">{{ collection }}</span>
            <span v-if="schema && Object.keys(schema).length" class="text-slate-700">|</span>
            <span v-if="schema && Object.keys(schema).length" class="text-slate-500">{{ Object.keys(schema).length }} fields</span>
          </p>
        </div>
      </div>
      <button class="btn flex-shrink-0" @click="openCreate">
        <Plus class="w-4 h-4" />
        <span class="hidden sm:inline">New Record</span>
      </button>
    </div>

    <!-- ═══ Selector Card ═══ -->
    <div v-if="!collection" class="card">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="w-full sm:w-56">
          <label class="section-label flex items-center gap-1.5">
            <Database :size="13" class="text-slate-500" />
            Database
          </label>
          <select v-model="db" @change="loadCollections" class="select">
            <option value="">Select database...</option>
            <option v-for="d in databases" :key="d" :value="d">{{ d }}</option>
          </select>
        </div>
        <div class="w-full sm:w-64">
          <label class="section-label flex items-center gap-1.5">
            <FolderOpen :size="13" class="text-slate-500" />
            Collection
          </label>
          <select v-model="collection" @change="onCollectionChange" class="select">
            <option value="">Select collection...</option>
            <option v-for="c in collections" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- ═══ Toolbar ═══ -->
    <div v-if="collection" class="card !p-3">
      <div class="flex flex-wrap items-center gap-3">
        <!-- Search -->
        <div class="relative flex-1 min-w-[200px] max-w-sm">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
          <input
            v-model="searchText"
            @keyup.enter="applySearch"
            :placeholder="`Search in ${collection}...`"
            class="input !pl-10 !py-2 !rounded-lg text-sm"
          />
          <button
            v-if="searchText"
            class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-md hover:bg-white/[0.06] text-slate-500 hover:text-slate-300 transition-colors"
            @click="clearSearch"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>

        <!-- Spacer -->
        <div class="flex-1"></div>

        <!-- Soft delete toggles -->
        <div class="hidden md:flex items-center gap-3 text-xs border-l border-white/[0.07] pl-3">
          <label class="flex items-center gap-1.5 cursor-pointer text-slate-400 hover:text-slate-300 transition-colors">
            <input type="checkbox" v-model="withTrashed" @change="loadDocs" class="rounded" />
            withTrashed
          </label>
          <label class="flex items-center gap-1.5 cursor-pointer text-slate-400 hover:text-slate-300 transition-colors">
            <input type="checkbox" v-model="onlyTrashed" @change="loadDocs" class="rounded" />
            onlyTrashed
          </label>
        </div>

        <!-- Filter toggle -->
        <button
          class="btn-ghost-sm flex items-center gap-1.5"
          :class="{ '!bg-indigo-500/10 !border-indigo-500/30 !text-indigo-300': showFilters }"
          @click="showFilters = !showFilters"
        >
          <Filter class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Filters</span>
        </button>

        <!-- Column toggle -->
        <div class="relative" ref="colMenuRef">
          <button class="btn-ghost-sm flex items-center gap-1.5" @click="colMenuOpen = !colMenuOpen">
            <Columns3 class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Columns</span>
          </button>
          <Transition name="dropdown">
            <div v-if="colMenuOpen" class="col-dropdown">
              <div class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">
                Toggle Columns ({{ visibleColCount }}/{{ allColumns.length }})
              </div>
              <div class="max-h-60 overflow-y-auto">
                <label
                  v-for="col in allColumns"
                  :key="col.field"
                  class="col-dropdown-item"
                >
                  <input type="checkbox" v-model="col.visible" class="rounded" />
                  <span class="truncate">{{ col.label }}</span>
                  <span class="text-[10px] text-slate-600 font-mono ml-auto flex-shrink-0">{{ shortType(col.type) }}</span>
                </label>
              </div>
              <div class="border-t border-white/[0.06] px-3 py-2 flex gap-2">
                <button class="text-[11px] text-indigo-400 hover:text-indigo-300" @click="showAllCols">Show all</button>
                <button class="text-[11px] text-slate-500 hover:text-slate-400" @click="hideAllCols">Hide all</button>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Export -->
        <div class="relative" ref="exportMenuRef">
          <button class="btn-ghost-sm flex items-center gap-1.5" @click="exportMenuOpen = !exportMenuOpen">
            <Download class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Export</span>
          </button>
          <Transition name="dropdown">
            <div v-if="exportMenuOpen" class="col-dropdown !w-44">
              <button class="col-dropdown-item w-full" @click="exportJson">
                <FileJson class="w-4 h-4 text-blue-400" />
                <span>Export JSON</span>
              </button>
              <button class="col-dropdown-item w-full" @click="exportCsv">
                <FileSpreadsheet class="w-4 h-4 text-emerald-400" />
                <span>Export CSV</span>
              </button>
            </div>
          </Transition>
        </div>

        <!-- Refresh -->
        <button class="btn-ghost-sm !p-2" @click="loadDocs" title="Refresh data">
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <!-- ═══ Status Chips ═══ -->
    <div v-if="collection" class="flex flex-wrap items-center gap-2">
      <span class="badge badge-info">
        <Hash class="w-3 h-3 mr-1" />
        {{ total }} records
      </span>
      <span v-if="searchText" class="badge badge-warning">
        <Search class="w-3 h-3 mr-1" />
        Filtered: "{{ searchText }}"
        <button @click="clearSearch" class="ml-1 hover:text-white"><X class="w-3 h-3 inline" /></button>
      </span>
      <span v-if="activeFilterCount" class="badge">
        <Filter class="w-3 h-3 mr-1" />
        {{ activeFilterCount }} filter{{ activeFilterCount > 1 ? 's' : '' }}
        <button @click="clearAllFilters" class="ml-1 hover:text-white"><X class="w-3 h-3 inline" /></button>
      </span>
      <span v-if="relationFields.length" class="badge badge-violet">
        <Link class="w-3 h-3 mr-1" />
        {{ relationFields.length }} relation{{ relationFields.length > 1 ? 's' : '' }}
      </span>
    </div>

    <!-- ═══ Data Table ═══ -->
    <div v-if="collection" class="card !p-0 overflow-hidden">
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
        :empty-subtitle="searchText ? 'Try a different search term or clear filters' : 'Create your first record in this collection'"
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
            <Trash2 class="w-3.5 h-3.5" />
            Delete ({{ ids.length }})
          </button>
        </template>
      </DataTable>
    </div>

    <!-- ═══ Empty State (no collection selected) ═══ -->
    <div v-if="!collection" class="empty-state py-24">
      <div class="w-20 h-20 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 grid place-items-center mb-5 mx-auto">
        <Table2 class="w-9 h-9 text-indigo-400" />
      </div>
      <div class="font-semibold text-slate-300 text-lg mb-1">Select a collection</div>
      <div class="text-sm text-slate-500 mb-6 max-w-sm mx-auto">
        Choose a database and collection from the selectors above to browse and manage your documents
      </div>
      <a href="/app/collections" class="btn-ghost inline-flex items-center gap-2">
        <FolderOpen class="w-4 h-4" />
        Go to Collections
      </a>
    </div>

    <!-- ═══ Document Form Modal ═══ -->
    <DocumentFormModal
      :visible="showModal"
      :document="editingDoc"
      :schema="schema"
      :db="db"
      :collection="collection"
      @close="showModal = false"
      @saved="onSaved"
    />

    <!-- ═══ Delete Confirmation Modal ═══ -->
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
              <p class="text-xs text-slate-400 mt-1">
                This will permanently delete
                <span class="font-mono text-slate-300">{{ deleteTarget._id }}</span>.
                This action cannot be undone.
              </p>
            </div>
          </div>
          <div class="flex justify-end gap-2">
            <button class="btn-ghost-sm" @click="deleteTarget = null">Cancel</button>
            <button class="btn-sm !bg-red-600 hover:!bg-red-500" @click="doDelete" :disabled="deleting">
              <Loader2 v-if="deleting" class="w-3.5 h-3.5 animate-spin" />
              <Trash2 v-else class="w-3.5 h-3.5" />
              Delete
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import axios from 'axios'
import DataTable from '@/Components/DataTable.vue'
import DocumentFormModal from '@/Components/DocumentFormModal.vue'
import { useToast } from '@/composables/useToast'
import {
  Database, FolderOpen, Search, Plus, ArrowLeft, RefreshCw, Filter,
  Columns3, Download, FileJson, FileSpreadsheet, Hash, Link,
  Trash2, X, AlertTriangle, Loader2, Table2,
} from 'lucide-vue-next'

const api = axios.create({ baseURL: '' })
const toast = useToast()

// ── State ──
const databases = ref([])
const collections = ref([])
const db = ref('')
const collection = ref('')
const docs = ref([])
const total = ref(0)
const loading = ref(false)
const schema = ref({})
const relationCache = ref({})

// Sorting
const sortField = ref('_id')
const sortDir = ref(-1)

// Pagination
const pageSize = ref(25)
const currentPage = ref(1)

// Filters
const showFilters = ref(false)
const columnFilters = ref({})
const searchText = ref('')
const withTrashed = ref(false)
const onlyTrashed = ref(false)

// UI
const showModal = ref(false)
const editingDoc = ref(null)
const deleteTarget = ref(null)
const deleting = ref(false)
const colMenuOpen = ref(false)
const exportMenuOpen = ref(false)
const colMenuRef = ref(null)
const exportMenuRef = ref(null)

// ── Computed ──
const allColumns = computed(() => {
  if (!Object.keys(schema.value).length) {
    const first = docs.value[0] || {}
    return Object.keys(first).filter(k => k !== '_id').slice(0, 8).map(f => ({
      field: f, label: f, type: typeof first[f],
      sortable: true, filterable: true, visible: ref(true),
    }))
  }
  return Object.entries(schema.value).map(([field, def]) => ({
    field,
    label: def.label || field,
    type: def.type || 'string',
    sortable: !!def.sortable,
    filterable: !!def.filterable,
    badge: def.ui?.badge || false,
    colorMap: def.ui?.color || null,
    relation: def.relation || (def.type === 'relation' ? def.relation : null),
    readonly: !!def.readonly,
    enumOptions: def.options || [],
    visible: ref(!def.hidden),
  }))
})

const visibleColumns = computed(() =>
  allColumns.value.filter(c => c.visible.value).map(c => ({ ...c, hidden: false }))
)

const visibleColCount = computed(() => allColumns.value.filter(c => c.visible.value).length)
const relationFields = computed(() => visibleColumns.value.filter(c => c.relation))
const activeFilterCount = computed(() => Object.values(columnFilters.value).filter(Boolean).length)

// ── Lifecycle ──
onMounted(async () => {
  const r = await api.get('/databases')
  databases.value = r.data.data
  // Auto-restore from URL params
  const url = new URL(window.location.href)
  const qdb = url.searchParams.get('db')
  const qcol = url.searchParams.get('col')
  if (qdb) {
    db.value = qdb
    await loadCollections()
    if (qcol) {
      collection.value = qcol
      await onCollectionChange()
    }
  }
})

// Close dropdown menus on outside click
function handleClickOutside(e) {
  if (colMenuRef.value && !colMenuRef.value.contains(e.target)) colMenuOpen.value = false
  if (exportMenuRef.value && !exportMenuRef.value.contains(e.target)) exportMenuOpen.value = false
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

// ── Data Loading ──
async function loadCollections() {
  if (!db.value) return
  try {
    const r = await api.get(`/databases/${db.value}/collections`)
    collections.value = r.data.data
  } catch { collections.value = [] }
  collection.value = ''
  docs.value = []
  schema.value = {}
}

async function onCollectionChange() {
  await loadSchema()
  columnFilters.value = {}
  searchText.value = ''
  sortField.value = '_id'
  sortDir.value = -1
  currentPage.value = 1
  await loadDocs()
}

async function loadSchema() {
  if (!db.value || !collection.value) { schema.value = {}; return }
  try {
    const r = await api.get(`/databases/${db.value}/collections/${collection.value}/schema`)
    schema.value = r.data.schema || {}
  } catch { schema.value = {} }
}

async function loadDocs() {
  if (!db.value || !collection.value) return
  loading.value = true
  try {
    const sort = sortField.value ? { [sortField.value]: sortDir.value } : {}
    let filter = buildFilter()
    const params = {
      filter: JSON.stringify(filter),
      sort: JSON.stringify(sort),
      limit: pageSize.value,
      skip: (currentPage.value - 1) * pageSize.value,
      with_trashed: withTrashed.value ? 1 : 0,
      only_trashed: onlyTrashed.value ? 1 : 0,
    }
    const r = await api.get(`/databases/${db.value}/collections/${collection.value}/documents`, { params })
    docs.value = r.data.data
    total.value = r.data.total
    // Populate relations
    if (relationFields.value.length) {
      await populateRelations()
    }
  } catch (e) {
    toast.error('Gagal memuat dokumen: ' + (e.response?.data?.message || e.message))
  } finally {
    loading.value = false
  }
}

function buildFilter() {
  const parts = []
  // Column filters
  for (const [k, v] of Object.entries(columnFilters.value)) {
    if (!v) continue
    if (!isNaN(v) && v.trim() !== '') {
      parts.push({ [k]: Number(v) })
    } else {
      parts.push({ [k]: { $regex: v } })
    }
  }
  // Search text (search across all string fields)
  if (searchText.value) {
    const searchParts = []
    for (const col of visibleColumns.value) {
      if (['string', 'text', 'email', 'url', 'slug'].includes(col.type)) {
        searchParts.push({ [col.field]: { $regex: searchText.value } })
      }
    }
    if (searchParts.length === 1) {
      parts.push(searchParts[0])
    } else if (searchParts.length > 1) {
      parts.push({ $or: searchParts })
    }
  }
  if (parts.length === 0) return {}
  if (parts.length === 1) return parts[0]
  return { $and: parts }
}

async function populateRelations() {
  for (const col of relationFields.value) {
    const rel = col.relation
    if (!rel?.db || !rel?.collection) continue
    const ids = [...new Set(docs.value.map(d => d[col.field]).filter(Boolean))]
    if (!ids.length) continue
    const cacheKey = `${rel.db}.${rel.collection}`
    if (!relationCache.value[cacheKey]) relationCache.value[cacheKey] = {}
    const missing = ids.filter(id => !(id in relationCache.value[cacheKey]))
    if (missing.length) {
      try {
        const res = await api.post(`/databases/${rel.db}/collections/${rel.collection}/query`, {
          filter: { [rel.field || '_id']: { $in: missing } },
          limit: 200,
        })
        for (const item of (res.data.data || [])) {
          const id = item[rel.field || '_id']
          const label = item[rel.display] || item.name || item.title || id
          relationCache.value[cacheKey][id] = label
        }
      } catch {}
    }
  }
}

// ── Sort ──
function onSort({ field, dir }) {
  sortField.value = field
  sortDir.value = dir
  currentPage.value = 1
  loadDocs()
}

// ── Filters ──
function onApplyFilters(filters) {
  columnFilters.value = { ...filters }
  currentPage.value = 1
  loadDocs()
}

function onClearFilters() {
  columnFilters.value = {}
  loadDocs()
}

function clearAllFilters() {
  columnFilters.value = {}
  searchText.value = ''
  loadDocs()
}

function applySearch() {
  currentPage.value = 1
  loadDocs()
}

function clearSearch() {
  searchText.value = ''
  currentPage.value = 1
  loadDocs()
}

// ── Pagination ──
function goToPage(p) {
  const maxPage = Math.max(1, Math.ceil(total.value / pageSize.value))
  currentPage.value = Math.max(1, Math.min(p, maxPage))
  loadDocs()
}

function onPageSizeChange(size) {
  pageSize.value = size
  currentPage.value = 1
  loadDocs()
}

// ── Selection ──
function onSelect(ids) {}
function onSelectAll(ids) {}

// ── Column Management ──
function showAllCols() { allColumns.value.forEach(c => c.visible.value = true) }
function hideAllCols() { allColumns.value.forEach(c => c.visible.value = false) }

// ── CRUD ──
function openCreate() {
  editingDoc.value = null
  showModal.value = true
}

function openEdit(doc) {
  editingDoc.value = { ...doc }
  showModal.value = true
}

function confirmDelete(doc) {
  deleteTarget.value = doc
}

async function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await api.delete(`/databases/${db.value}/collections/${collection.value}/documents`, {
      params: { filter: JSON.stringify({ _id: deleteTarget.value._id }) },
    })
    toast.success('Dokumen berhasil dihapus')
    deleteTarget.value = null
    loadDocs()
  } catch (e) {
    toast.error('Gagal menghapus: ' + (e.response?.data?.message || e.message))
  } finally {
    deleting.value = false
  }
}

async function bulkDelete(ids, clear) {
  if (!confirm(`Hapus ${ids.length} dokumen?`)) return
  try {
    await api.delete(`/databases/${db.value}/collections/${collection.value}/documents`, {
      params: { filter: JSON.stringify({ _id: { $in: ids } }) },
    })
    toast.success(`${ids.length} dokumen berhasil dihapus`)
    clear()
    loadDocs()
  } catch (e) {
    toast.error('Gagal menghapus: ' + (e.response?.data?.message || e.message))
  }
}

// ── Inline Cell Edit ──
async function onCellEdit({ id, field, value, row }) {
  try {
    await api.post(`/databases/${db.value}/collections/${collection.value}/save`, { _id: id, [field]: value })
    toast.success('Field updated')
    loadDocs()
  } catch (e) {
    toast.error('Update gagal: ' + (e.response?.data?.message || e.message))
  }
}

function onSaved() {
  toast.success(editingDoc.value ? 'Dokumen berhasil diperbarui' : 'Dokumen berhasil dibuat')
  loadDocs()
}

// ── Export ──
function exportJson() {
  exportMenuOpen.value = false
  const blob = new Blob([JSON.stringify(docs.value, null, 2)], { type: 'application/json' })
  downloadBlob(blob, `${collection}_export.json`)
  toast.info(`Exported ${docs.value.length} records as JSON`)
}

function exportCsv() {
  exportMenuOpen.value = false
  if (!docs.value.length) { toast.warning('No data to export'); return }
  const cols = visibleColumns.value.map(c => c.field)
  const header = ['_id', ...cols].join(',')
  const rows = docs.value.map(doc =>
    ['_id', ...cols].map(f => {
      let v = doc[f]
      if (v === null || v === undefined) return ''
      v = String(v)
      if (v.includes(',') || v.includes('"') || v.includes('\n')) {
        v = '"' + v.replace(/"/g, '""') + '"'
      }
      return v
    }).join(',')
  )
  const csv = [header, ...rows].join('\n')
  const blob = new Blob([csv], { type: 'text/csv' })
  downloadBlob(blob, `${collection}_export.csv`)
  toast.info(`Exported ${docs.value.length} records as CSV`)
}

function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url; a.download = filename; a.click()
  URL.revokeObjectURL(url)
}

// ── Helpers ──
function shortType(t) {
  const map = {
    string: 'str', text: 'txt', email: 'mail', url: 'url', slug: 'slug',
    int: 'int', float: 'flt', number: 'num', decimal: 'dec',
    bool: 'bool', array: 'arr', object: 'obj', json: 'json',
    enum: 'enum', tags: 'tags', date: 'date', datetime: 'dt',
    time: 'time', relation: 'rel',
  }
  return map[t] || (t || '').slice(0, 4)
}
</script>

<style scoped>
/* Dropdown animation */
.dropdown-enter-active { transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1); }
.dropdown-leave-active { transition: all 0.1s ease-in; }
.dropdown-enter-from { opacity: 0; transform: translateY(-4px) scale(0.95); }
.dropdown-leave-to { opacity: 0; transform: translateY(-4px) scale(0.95); }
</style>