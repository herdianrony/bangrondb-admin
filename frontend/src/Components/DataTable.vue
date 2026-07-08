<template>
  <div class="dt-wrapper">
    <!-- ── Table Scroll Area ── -->
    <div class="dt-scroll" ref="scrollRef">
      <table class="dt-table" :style="{ minWidth: tableMinWidth }">
        <!-- ═══ Header ═══ -->
        <thead>
          <tr class="dt-head-row">
            <!-- Checkbox -->
            <th v-if="selectable" class="dt-col-check">
              <div class="dt-th-inner">
                <label class="dt-checkbox-label">
                  <input
                    type="checkbox"
                    class="dt-checkbox"
                    :checked="allSelected"
                    :indeterminate="someSelected && !allSelected"
                    @change="toggleAll"
                  />
                </label>
              </div>
            </th>
            <!-- Expand -->
            <th v-if="expandable" class="dt-col-expand">
              <div class="dt-th-inner"></div>
            </th>
            <!-- # -->
            <th class="dt-col-num">
              <div class="dt-th-inner text-slate-600">#</div>
            </th>
            <!-- Data columns -->
            <th
              v-for="col in visibleColumns"
              :key="col.field"
              class="dt-col"
              :class="{ 'dt-col-sortable': col.sortable }"
              :style="colWidthStyle(col)"
              @click="col.sortable && toggleSort(col.field)"
            >
              <div class="dt-th-inner" :title="col.label">
                <span class="dt-th-label truncate">{{ col.label }}</span>
                <span v-if="col.sortable" class="dt-sort-icon">
                  <ChevronUp v-if="sortField === col.field && sortDir === 1" class="w-3.5 h-3.5 text-indigo-400" />
                  <ChevronDown v-else-if="sortField === col.field && sortDir === -1" class="w-3.5 h-3.5 text-indigo-400" />
                  <ChevronsUpDown v-else class="w-3 h-3 text-slate-600" />
                </span>
                <span class="dt-th-type">{{ shortType(col.type) }}</span>
              </div>
            </th>
            <!-- Actions -->
            <th class="dt-col-actions">
              <div class="dt-th-inner">Actions</div>
            </th>
          </tr>

          <!-- ═══ Filter Row ═══ -->
          <tr v-if="showFilterRow" class="dt-filter-row">
            <td v-if="selectable" class="dt-col-check">
              <div class="flex items-center justify-center">
                <Filter class="w-3 h-3 text-slate-600" />
              </div>
            </td>
            <td v-if="expandable"></td>
            <td></td>
            <td
              v-for="col in visibleColumns"
              :key="'f-' + col.field"
              class="dt-filter-cell"
            >
              <input
                v-if="col.filterable"
                v-model="localFilters[col.field]"
                @keyup.enter="$emit('apply-filters', localFilters)"
                :placeholder="col.label"
                class="dt-filter-input"
              />
              <span v-else class="text-slate-700 text-[10px]">--</span>
            </td>
            <td class="text-right px-2 py-1.5">
              <button class="text-[11px] text-indigo-400 hover:text-indigo-300 font-medium" @click="$emit('apply-filters', localFilters)">Apply</button>
              <button class="text-[11px] text-slate-500 hover:text-slate-400 ml-2" @click="clearFilters">Clear</button>
            </td>
          </tr>
        </thead>

        <!-- ═══ Body ═══ -->
        <tbody>
          <!-- Loading skeleton -->
          <template v-if="loading">
            <tr v-for="n in skeletonRows" :key="'sk-' + n" class="dt-row dt-row-loading">
              <td v-if="selectable" class="dt-col-check">
                <div class="skeleton-box w-4 h-4 rounded"></div>
              </td>
              <td v-if="expandable" class="dt-col-expand"></td>
              <td><div class="skeleton-box w-6 h-4 rounded"></div></td>
              <td v-for="col in visibleColumns" :key="'skc-' + col.field + '-' + n">
                <div class="skeleton-box h-4 rounded" :style="{ width: skeletonWidth(n, col.field) }"></div>
              </td>
              <td><div class="skeleton-box w-16 h-4 rounded"></div></td>
            </tr>
          </template>

          <!-- Data rows -->
          <template v-else>
            <tr
              v-for="(row, rowIdx) in data"
              :key="row._id"
              class="dt-row"
              :class="{
                'dt-row-selected': isSelected(row._id),
                'dt-row-expanded': expandedId === row._id,
                'dt-row-editing': editingCell?.id === row._id,
              }"
            >
              <!-- Checkbox -->
              <td v-if="selectable" class="dt-col-check">
                <label class="dt-checkbox-label">
                  <input
                    type="checkbox"
                    class="dt-checkbox"
                    :checked="isSelected(row._id)"
                    @change="toggleSelect(row._id)"
                  />
                </label>
              </td>
              <!-- Expand -->
              <td v-if="expandable" class="dt-col-expand">
                <button
                  class="dt-expand-btn"
                  :class="{ 'dt-expand-active': expandedId === row._id }"
                  @click="toggleExpand(row._id)"
                >
                  <ChevronRight class="w-3.5 h-3.5 transition-transform duration-200"
                    :style="{ transform: expandedId === row._id ? 'rotate(90deg)' : '' }" />
                </button>
              </td>
              <!-- Row number -->
              <td class="dt-col-num text-slate-600 text-xs tabular-nums">
                {{ offset + rowIdx + 1 }}
              </td>
              <!-- Data cells -->
              <td
                v-for="col in visibleColumns"
                :key="col.field"
                class="dt-cell"
                :class="cellClass(col, row)"
                @dblclick="startEdit(row, col)"
                tabindex="0"
                @keydown="onCellKeydown($event, row, col)"
              >
                <!-- Editing mode -->
                <template v-if="editingCell?.id === row._id && editingCell?.field === col.field">
                  <div class="dt-cell-editor" @click.stop>
                    <select
                      v-if="col.type === 'enum'"
                      v-model="editingCell.value"
                      class="dt-edit-input"
                      @blur="saveEdit(row, col)"
                      @keyup.escape="cancelEdit"
                    >
                      <option value="">--</option>
                      <option v-for="opt in (col.enumOptions || [])" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                    <select
                      v-else-if="col.type === 'bool' || col.type === 'boolean'"
                      v-model="editingCell.value"
                      class="dt-edit-input"
                      @blur="saveEdit(row, col)"
                      @keyup.escape="cancelEdit"
                    >
                      <option :value="true">true</option>
                      <option :value="false">false</option>
                    </select>
                    <input
                      v-else-if="isNumberType(col.type)"
                      v-model.number="editingCell.value"
                      type="number"
                      class="dt-edit-input"
                      @blur="saveEdit(row, col)"
                      @keyup.enter="saveEdit(row, col)"
                      @keyup.escape="cancelEdit"
                      ref="editInputRef"
                    />
                    <input
                      v-else
                      v-model="editingCell.value"
                      type="text"
                      class="dt-edit-input"
                      @blur="saveEdit(row, col)"
                      @keyup.enter="saveEdit(row, col)"
                      @keyup.escape="cancelEdit"
                      ref="editInputRef"
                    />
                  </div>
                </template>
                <!-- Display mode -->
                <template v-else>
                  <!-- Relation -->
                  <template v-if="col.relation">
                    <div class="dt-relation-cell">
                      <span class="dt-relation-label">
                        {{ getRelationDisplay(col, row[col.field]) || '--' }}
                      </span>
                      <span class="dt-relation-id">{{ truncate(String(row[col.field] ?? ''), 12) }}</span>
                    </div>
                  </template>
                  <!-- Enum badge -->
                  <template v-else-if="col.badge && row[col.field]">
                    <span :class="badgeClass(col, row[col.field])">{{ row[col.field] }}</span>
                  </template>
                  <!-- Tags -->
                  <template v-else-if="col.type === 'tags' && Array.isArray(row[col.field])">
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="tag in row[col.field].slice(0, 3)"
                        :key="tag"
                        class="dt-tag"
                      >{{ tag }}</span>
                      <span v-if="row[col.field].length > 3" class="dt-tag-dots">
                        +{{ row[col.field].length - 3 }}
                      </span>
                    </div>
                  </template>
                  <!-- Boolean -->
                  <template v-else-if="col.type === 'bool' || col.type === 'boolean'">
                    <span class="dt-bool" :class="row[col.field] ? 'dt-bool-on' : 'dt-bool-off'">
                      <component :is="row[col.field] ? Check : X" class="w-3.5 h-3.5" />
                    </span>
                  </template>
                  <!-- Array count -->
                  <template v-else-if="Array.isArray(row[col.field])">
                    <span class="text-slate-400 text-xs font-mono">[{{ row[col.field].length }}]</span>
                  </template>
                  <!-- Object indicator -->
                  <template v-else-if="isObjectValue(row[col.field])">
                    <span class="text-slate-500 text-xs font-mono">{...}</span>
                  </template>
                  <!-- Date/DateTime -->
                  <template v-else-if="col.type === 'date' || col.type === 'datetime' || col.type === 'time'">
                    <span class="dt-date-cell" :title="String(row[col.field] ?? '')">
                      {{ formatDate(row[col.field]) }}
                    </span>
                  </template>
                  <!-- URL -->
                  <template v-else-if="col.type === 'url' && row[col.field]">
                    <a :href="row[col.field]" target="_blank" rel="noopener" class="dt-url-link" @click.stop>
                      <ExternalLink class="w-3 h-3 inline mr-1" />
                      {{ truncate(String(row[col.field]), 30) }}
                    </a>
                  </template>
                  <!-- Default string/number -->
                  <template v-else>
                    <span class="dt-cell-text" :title="String(row[col.field] ?? '')">
                      {{ formatCell(row[col.field], col) }}
                    </span>
                  </template>
                  <!-- Edit hint on hover -->
                  <span v-if="col.sortable && !col.readonly && !col.relation" class="dt-edit-hint">
                    <Pencil class="w-2.5 h-2.5" />
                  </span>
                </template>
              </td>
              <!-- Actions -->
              <td class="dt-col-actions">
                <div class="dt-actions">
                  <button class="dt-action-btn" title="Edit" @click="$emit('edit', row)">
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                  <button class="dt-action-btn dt-action-danger" title="Delete" @click="$emit('delete', row)">
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>

            <!-- Expanded row detail -->
            <tr v-if="expandable && expandedId && !data.find(r => r._id === expandedId)" :key="'exp-empty'">
              <td :colspan="totalCols"></td>
            </tr>
            <tr
              v-if="expandable && expandedId === row._id"
              v-for="row in data.filter(r => r._id === expandedId)"
              :key="'exp-' + row._id"
              class="dt-expand-row"
            >
              <td :colspan="totalCols" class="p-0">
                <div class="dt-expand-content animate-fade-in">
                  <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                      <FileJson class="w-4 h-4 text-slate-500" />
                      <span class="text-xs font-medium text-slate-400">Document {{ row._id }}</span>
                    </div>
                    <button class="btn-ghost-sm text-[11px]" @click="copyJson(row)">
                      <ClipboardCopy class="w-3 h-3 mr-1" /> Copy JSON
                    </button>
                  </div>
                  <pre class="dt-json-preview">{{ formatJson(row) }}</pre>
                </div>
              </td>
            </tr>

            <!-- Empty state -->
            <tr v-if="!data.length && !loading">
              <td :colspan="totalCols" class="dt-empty">
                <div class="dt-empty-inner">
                  <component :is="emptyIcon" class="w-10 h-10 text-slate-600 mb-3" />
                  <div class="text-slate-400 font-medium mb-1">{{ emptyTitle }}</div>
                  <div class="text-slate-600 text-xs">{{ emptySubtitle }}</div>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- ═══ Pagination Footer ═══ -->
    <div v-if="!loading" class="dt-footer">
      <div class="flex items-center gap-3">
        <!-- Selection info -->
        <div v-if="selectable && selectedIds.size" class="flex items-center gap-2">
          <span class="text-xs text-indigo-300 font-medium">{{ selectedIds.size }} selected</span>
          <slot name="bulk-actions" :ids="[...selectedIds]" :clear="clearSelection" />
        </div>
        <div v-else class="text-xs text-slate-500">
          <template v-if="total > 0">
            <span class="text-slate-400 font-medium">{{ total }}</span> records
          </template>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <!-- Page size -->
        <select
          :value="pageSize"
          @change="$emit('update:pageSize', Number($event.target.value))"
          class="dt-page-size-select"
        >
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>

        <!-- Page navigation -->
        <div class="dt-page-nav">
          <button
            class="dt-page-btn"
            :disabled="currentPage <= 1"
            @click="$emit('go-to-page', currentPage - 1)"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>

          <template v-for="p in pageNumbers" :key="p">
            <span v-if="p === '...'" class="dt-page-dots">...</span>
            <button
              v-else
              class="dt-page-btn dt-page-num"
              :class="{ 'dt-page-active': p === currentPage }"
              @click="$emit('go-to-page', p)"
            >
              {{ p }}
            </button>
          </template>

          <button
            class="dt-page-btn"
            :disabled="currentPage >= totalPages"
            @click="$emit('go-to-page', currentPage + 1)"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>

        <!-- Jump to page -->
        <div class="hidden sm:flex items-center gap-1.5 text-xs text-slate-500">
          <span>Go to</span>
          <input
            type="number"
            :value="currentPage"
            @keyup.enter="$emit('go-to-page', Math.max(1, Math.min(Number($event.target.value), totalPages)))"
            class="dt-jump-input"
            :min="1"
            :max="totalPages"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, nextTick, watch } from 'vue'
import {
  ChevronUp, ChevronDown, ChevronsUpDown, ChevronLeft, ChevronRight, ChevronRight as ChevronRightIcon,
  Pencil, Trash2, Check, X, Filter, FileJson, ClipboardCopy, ExternalLink,
  Inbox, Search,
} from 'lucide-vue-next'

const props = defineProps({
  columns: { type: Array, default: () => [] },
  data: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  selectable: { type: Boolean, default: true },
  expandable: { type: Boolean, default: true },
  showFilterRow: { type: Boolean, default: false },
  sortField: { type: String, default: '' },
  sortDir: { type: Number, default: -1 },
  pageSize: { type: Number, default: 25 },
  currentPage: { type: Number, default: 1 },
  offset: { type: Number, default: 0 },
  emptyTitle: { type: String, default: 'No records found' },
  emptySubtitle: { type: String, default: 'Try adjusting your filters or create a new record' },
  emptyIcon: { type: Object, default: () => Inbox },
  relationCache: { type: Object, default: () => ({}) },
})

const emit = defineEmits([
  'sort', 'apply-filters', 'clear-filters', 'edit', 'delete',
  'cell-edit', 'toggle-expand',
  'go-to-page', 'update:pageSize',
  'select', 'select-all',
])

// ── Visible columns ──
const visibleColumns = computed(() => props.columns.filter(c => !c.hidden))
const totalCols = computed(() => {
  let n = visibleColumns.value.length + 1 + 1 // # + actions
  if (props.selectable) n++
  if (props.expandable) n++
  return n
})
const tableMinWidth = computed(() => Math.max(700, visibleColumns.value.length * 160 + 200) + 'px')

// ── Selection ──
const selectedIds = ref(new Set())
const allSelected = computed(() => props.data.length > 0 && props.data.every(r => selectedIds.value.has(r._id)))
const someSelected = computed(() => props.data.some(r => selectedIds.value.has(r._id)) && !allSelected.value)

function isSelected(id) { return selectedIds.value.has(id) }
function toggleSelect(id) {
  const s = new Set(selectedIds.value)
  if (s.has(id)) s.delete(id); else s.add(id)
  selectedIds.value = s
  emit('select', [...s])
}
function toggleAll() {
  if (allSelected.value) {
    selectedIds.value = new Set()
  } else {
    selectedIds.value = new Set(props.data.map(r => r._id))
  }
  emit('select', [...selectedIds.value])
  emit('select-all', [...selectedIds.value])
}
function clearSelection() { selectedIds.value = new Set() }

// ── Expand ──
const expandedId = ref(null)
function toggleExpand(id) {
  expandedId.value = expandedId.value === id ? null : id
  emit('toggle-expand', expandedId.value)
}

// ── Sort ──
function toggleSort(field) {
  let dir
  if (props.sortField === field) {
    dir = props.sortDir === 1 ? -1 : 1
  } else {
    dir = 1
  }
  emit('sort', { field, dir })
}

// ── Filters ──
const localFilters = reactive({})
function clearFilters() {
  Object.keys(localFilters).forEach(k => delete localFilters[k])
  emit('clear-filters')
}

// ── Inline Editing ──
const editingCell = ref(null)

function startEdit(row, col) {
  if (col.readonly || col.relation) return
  if (col.type === 'array' || col.type === 'object' || col.type === 'json') return
  editingCell.value = {
    id: row._id,
    field: col.field,
    value: row[col.field],
    type: col.type,
  }
  nextTick(() => {
    const inputs = document.querySelectorAll('.dt-edit-input')
    if (inputs.length) inputs[inputs.length - 1].focus()
  })
}

async function saveEdit(row, col) {
  if (!editingCell.value) return
  const { id, field, value } = editingCell.value
  editingCell.value = null
  if (row[field] === value) return
  emit('cell-edit', { id, field, value, row })
}

function cancelEdit() {
  editingCell.value = null
}

function onCellKeydown(e, row, col) {
  if (e.key === 'Enter' && !editingCell.value) {
    e.preventDefault()
    startEdit(row, col)
  }
}

watch(() => props.data, () => {
  editingCell.value = null
})

// ── Pagination ──
const totalPages = computed(() => Math.max(1, Math.ceil(props.total / props.pageSize)))

const pageNumbers = computed(() => {
  const pages = []
  const total = totalPages.value
  const curr = props.currentPage
  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (curr > 3) pages.push('...')
    for (let i = Math.max(2, curr - 1); i <= Math.min(total - 1, curr + 1); i++) {
      pages.push(i)
    }
    if (curr < total - 2) pages.push('...')
    pages.push(total)
  }
  return pages
})

// ── Helpers ──
function shortType(t) {
  const map = {
    string: 'str', text: 'txt', email: 'mail', password: 'pwd', url: 'url', slug: 'slug',
    int: 'int', integer: 'int', float: 'flt', double: 'flt', number: 'num', decimal: 'dec',
    bool: 'bool', boolean: 'bool',
    array: 'arr', object: 'obj', json: 'json',
    enum: 'enum', tags: 'tags',
    date: 'date', datetime: 'dt', time: 'time',
    relation: 'rel',
  }
  return map[t] || t?.slice(0, 4) || ''
}

function isNumberType(t) {
  return ['int', 'integer', 'float', 'double', 'number', 'decimal'].includes(t)
}

function isObjectValue(v) {
  return v !== null && typeof v === 'object' && !Array.isArray(v)
}

function truncate(s, len) {
  if (!s) return '--'
  return s.length > len ? s.slice(0, len - 1) + '\u2026' : s
}

function formatCell(v, col) {
  if (v === null || v === undefined) return '--'
  if (typeof v === 'boolean') return v ? 'true' : 'false'
  const s = String(v)
  return s.length > 80 ? s.slice(0, 77) + '\u2026' : s
}

function formatDate(v) {
  if (!v) return '--'
  try {
    const d = new Date(v)
    if (isNaN(d.getTime())) return String(v)
    const now = new Date()
    const isToday = d.toDateString() === now.toDateString()
    if (isToday) {
      return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
    }
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: d.getFullYear() !== now.getFullYear() ? 'numeric' : undefined })
  } catch {
    return String(v)
  }
}

function getRelationDisplay(col, id) {
  if (!id || !col.relation) return null
  const key = `${col.relation.db}.${col.relation.collection}`
  return props.relationCache?.[key]?.[id] || null
}

function badgeClass(col, value) {
  const color = col.colorMap?.[value] || 'slate'
  const map = {
    gray: 'bg-slate-700/80 text-slate-200',
    blue: 'bg-blue-900/80 text-blue-200',
    green: 'bg-emerald-900/80 text-emerald-200',
    amber: 'bg-amber-900/80 text-amber-200',
    red: 'bg-red-900/80 text-red-200',
    violet: 'bg-violet-900/80 text-violet-200',
    slate: 'bg-slate-800/80 text-slate-200',
  }
  return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium ' + (map[color] || map.slate)
}

function cellClass(col, row) {
  return {
    'dt-cell-editable': col.sortable && !col.readonly && !col.relation,
    'dt-cell-bool': col.type === 'bool' || col.type === 'boolean',
    'dt-cell-relation': col.relation,
    'dt-cell-enum': col.badge,
  }
}

function colWidthStyle(col) {
  if (col.type === 'bool' || col.type === 'boolean') return { width: '80px', minWidth: '80px' }
  if (col.type === 'tags') return { width: '180px', minWidth: '140px' }
  return {}
}

function formatJson(obj) {
  return JSON.stringify(obj, null, 2)
}

function copyJson(row) {
  navigator.clipboard.writeText(JSON.stringify(row, null, 2)).catch(() => {})
}

function skeletonWidth(n, field) {
  const widths = ['40%', '60%', '80%', '55%', '70%', '45%', '65%', '50%']
  return widths[(n + field.length) % widths.length]
}
const skeletonRows = 8

defineExpose({ clearSelection, selectedIds })
</script>