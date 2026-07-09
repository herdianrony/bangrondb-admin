<template>
  <div class="space-y-5 animate-fade-in">
    <!-- ── Page Header ── -->
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-violet-500/10 border border-violet-500/20 grid place-items-center">
            <Database class="w-4 h-4 text-violet-400" />
          </div>
          Collections
        </h1>
        <p class="text-slate-500 text-sm mt-1">Manage collections and their schemas across your databases</p>
      </div>
      <button class="btn" @click="openCreateModal">
        <Plus class="w-4 h-4" />
        New Collection
      </button>
    </div>

    <!-- ── Toolbar ── -->
    <div class="card">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="w-full sm:w-52">
          <label class="section-label flex items-center gap-1.5">
            <Database :size="13" class="text-slate-500" />
            Database
          </label>
          <select v-model="db" @change="load" class="select">
            <option value="">Select database…</option>
            <option v-for="d in dbs" :key="d" :value="d">{{ d }}</option>
          </select>
        </div>
        <div v-if="db && collections.length" class="flex items-center gap-2 ml-auto text-xs text-slate-500">
          <span class="badge badge-info">{{ collections.length }} collection{{ collections.length !== 1 ? 's' : '' }}</span>
        </div>
      </div>
    </div>

    <!-- ── KPI Row ── -->
    <div v-if="db && collections.length" class="grid grid-cols-2 sm:grid-cols-4 gap-3 animate-fade-in">
      <div class="kpi-card">
        <div class="text-xs text-slate-500 mb-1">Collections</div>
        <div class="text-xl font-bold text-white">{{ collections.length }}</div>
      </div>
      <div class="kpi-card">
        <div class="text-xs text-slate-500 mb-1">Total Documents</div>
        <div class="text-xl font-bold text-white">{{ totalDocs }}</div>
      </div>
      <div class="kpi-card">
        <div class="text-xs text-slate-500 mb-1">Schema Fields</div>
        <div class="text-xl font-bold text-white">{{ totalFields }}</div>
      </div>
      <div class="kpi-card">
        <div class="text-xs text-slate-500 mb-1">Relations</div>
        <div class="text-xl font-bold text-white">{{ totalRelations }}</div>
      </div>
    </div>

    <!-- ── Collection Grid ── -->
    <div v-if="collections.length" class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="col in collections"
        :key="col.name"
        class="card-hover animate-scale-in group"
        :class="{ 'border-red-500/30': deleteTarget === col.name }"
      >
        <!-- Delete confirmation -->
        <div v-if="deleteTarget === col.name" class="animate-fade-in">
          <div class="flex items-start gap-3 mb-4">
            <div class="w-9 h-9 rounded-xl bg-red-500/10 border border-red-500/20 grid place-items-center flex-shrink-0 mt-0.5">
              <AlertCircle class="w-5 h-5 text-red-400" />
            </div>
            <div>
              <div class="font-semibold text-white text-sm">Delete "{{ col.name }}"?</div>
              <div class="text-xs text-slate-500 mt-1">Semua dokumen dan schema akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.</div>
            </div>
          </div>
          <div class="flex gap-2 justify-end">
            <button class="btn-ghost-sm" @click="deleteTarget = null">Cancel</button>
            <button class="btn-sm !bg-red-600 hover:!bg-red-500" @click="confirmDelete(col.name)">Delete</button>
          </div>
        </div>

        <!-- Card content -->
        <template v-else>
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-violet-500/10 border border-violet-500/20 grid place-items-center">
                <FolderOpen class="w-5 h-5 text-violet-400" />
              </div>
              <div>
                <div class="font-bold text-white">{{ col.name }}</div>
                <div class="text-xs text-slate-500 font-mono">{{ db }}.{{ col.name }}</div>
              </div>
            </div>
            <button
              class="btn-ghost-sm opacity-0 group-hover:opacity-100 !text-red-400 !border-red-800/40 hover:!bg-red-950/40 transition-opacity"
              @click="deleteTarget = col.name"
              title="Delete collection"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Stats row -->
          <div class="flex flex-wrap gap-2 mb-4">
            <span class="badge">
              <Hash class="w-3 h-3 mr-1 text-slate-500" />
              {{ col.docCount ?? '–' }} docs
            </span>
            <span class="badge">
              <Layers class="w-3 h-3 mr-1 text-slate-500" />
              {{ col.fieldCount ?? 0 }} fields
            </span>
            <span v-if="col.relationCount" class="badge badge-info">
              <Link class="w-3 h-3 mr-1" />
              {{ col.relationCount }} relation{{ col.relationCount > 1 ? 's' : '' }}
            </span>
          </div>

          <!-- Quick actions -->
          <div class="flex gap-2">
            <a
              :href="`/app/documents?db=${db}&col=${col.name}`"
              class="btn-ghost-sm flex-1 text-center flex items-center justify-center gap-1.5"
            >
              <ExternalLink class="w-3.5 h-3.5" />
              Documents
            </a>
            <a
              :href="`/app/schema?db=${db}&col=${col.name}`"
              class="btn-ghost-sm flex-1 text-center flex items-center justify-center gap-1.5"
            >
              <Pencil class="w-3.5 h-3.5" />
              Edit Schema
            </a>
          </div>
        </template>
      </div>
    </div>

    <!-- ── Empty State ── -->
    <div v-if="!collections.length && db" class="empty-state py-24">
      <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 grid place-items-center mb-5 mx-auto">
        <FolderPlus class="w-8 h-8 text-indigo-400" />
      </div>
      <div class="font-semibold text-slate-300 text-lg mb-1">No collections yet</div>
      <div class="text-sm text-slate-500 mb-5">Create your first collection in <span class="text-slate-400 font-mono">{{ db }}</span></div>
      <button class="btn" @click="openCreateModal">
        <Plus class="w-4 h-4" />
        Create your first collection
      </button>
    </div>

    <!-- ── No DB selected ── -->
    <div v-if="!db" class="empty-state py-24">
      <div class="w-16 h-16 rounded-2xl bg-slate-500/10 border border-slate-500/20 grid place-items-center mb-5 mx-auto">
        <Database class="w-8 h-8 text-slate-500" />
      </div>
      <div class="font-semibold text-slate-400 text-lg mb-1">Select a database</div>
      <div class="text-sm text-slate-600">Choose a database from the dropdown above to view its collections</div>
    </div>

    <!-- ── Create Collection Modal ── -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto"
      @click.self="closeCreateModal"
    >
      <div class="card w-full max-w-2xl my-8 animate-scale-in">
        <div class="flex items-center justify-between mb-5">
          <div>
            <h3 class="font-bold text-lg text-white flex items-center gap-2">
              <FolderPlus class="w-5 h-5 text-indigo-400" />
              New Collection
            </h3>
            <p class="text-xs text-slate-500 mt-1">Create in <span class="font-mono text-slate-400">{{ db }}</span></p>
          </div>
          <button class="btn-ghost-sm" @click="closeCreateModal">
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Collection name -->
        <div class="mb-5">
          <label class="section-label">Collection Name</label>
          <div class="relative">
            <FolderOpen class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
            <input
              v-model="newColName"
              class="input !pl-11"
              placeholder="e.g. products, users, articles"
              @keyup.enter="createCollection"
              ref="nameInput"
            />
          </div>
          <p v-if="createError" class="text-red-400 text-xs mt-1.5 flex items-center gap-1">
            <AlertCircle :size="12" />
            {{ createError }}
          </p>
        </div>

        <!-- Template selector -->
        <div class="mb-5">
          <label class="section-label">Starter Template</label>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 mt-2">
            <button
              v-for="(tpl, key) in templates"
              :key="key"
              class="text-left p-3.5 rounded-xl border transition-all duration-150"
              :class="selectedTemplate === key
                ? 'bg-indigo-500/10 border-indigo-500/40 ring-1 ring-indigo-500/20'
                : 'bg-[#0f1117] border-white/[0.06] hover:border-white/[0.12] hover:bg-[#161922]'"
              @click="selectedTemplate = key"
            >
              <component
                :is="tpl.icon"
                class="w-5 h-5 mb-2"
                :class="{
                  'text-slate-500': selectedTemplate !== key,
                  'text-indigo-400': selectedTemplate === key
                }"
              />
              <div class="text-sm font-semibold text-white">{{ tpl.label }}</div>
              <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">{{ tpl.desc }}</div>
              <div v-if="Object.keys(tpl.schema).length" class="text-[10px] text-slate-600 mt-1.5">
                {{ Object.keys(tpl.schema).length }} fields
              </div>
            </button>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-2 pt-2 border-t border-white/[0.06]">
          <button class="btn-ghost" @click="closeCreateModal">Cancel</button>
          <button class="btn flex items-center gap-2" :disabled="!newColName.trim() || creating" @click="createCollection">
            <Plus v-if="!creating" class="w-4 h-4" />
            <span v-if="creating" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            Create Collection
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'
import { useToast } from '@/composables/useToast'
import {
  FolderOpen,
  FolderPlus,
  Plus,
  ExternalLink,
  Pencil,
  Trash2,
  FileText,
  BookOpen,
  Package,
  ListChecks,
  Layout,
  X,
  Database,
  ChevronRight,
  Hash,
  Link,
  Layers,
  Search,
  AlertCircle,
} from 'lucide-vue-next'

const api = axios.create({ baseURL: '' })
const toast = useToast()

// ── State ──
const dbs = ref([])
const db = ref('app')
const collections = ref([])
const loading = ref(false)

// Create modal
const showCreateModal = ref(false)
const newColName = ref('')
const selectedTemplate = ref('blank')
const creating = ref(false)
const createError = ref('')
const nameInput = ref(null)

// Delete
const deleteTarget = ref(null)

// ── Templates ──
const templates = {
  blank: {
    label: 'Blank',
    icon: FileText,
    desc: 'Start from scratch',
    color: 'slate',
    schema: {},
  },
  blog: {
    label: 'Blog Post',
    icon: BookOpen,
    desc: 'Title, content, author, status, tags',
    color: 'blue',
    schema: {
      title: { type: 'string', label: 'Title', required: true, min: 3, max: 200, searchable: true, sortable: true, index: true },
      slug: { type: 'string', label: 'Slug', required: true, unique: true, regex: '/^[a-z0-9-]+$/', ui: { placeholder: 'auto-generated-from-title' } },
      content: { type: 'text', label: 'Content', required: true, rows: 8 },
      excerpt: { type: 'text', label: 'Excerpt', rows: 3 },
      status: { type: 'enum', label: 'Status', options: ['draft', 'published', 'archived'], default: 'draft', filterable: true, sortable: true, index: true, ui: { badge: true, color: { draft: 'gray', published: 'green', archived: 'amber' } } },
      author_id: { type: 'relation', label: 'Author', required: true, relation: { db: 'auth', collection: 'users', field: '_id', display: 'name' }, filterable: true },
      category: { type: 'string', label: 'Category', filterable: true, sortable: true },
      tags: { type: 'tags', label: 'Tags', filterable: true },
      published_at: { type: 'datetime', label: 'Published At', sortable: true },
      featured_image: { type: 'url', label: 'Featured Image', ui: { placeholder: 'https://...' } },
    },
  },
  product: {
    label: 'Product',
    icon: Package,
    desc: 'Name, price, category, stock, images',
    color: 'violet',
    schema: {
      name: { type: 'string', label: 'Product Name', required: true, min: 1, max: 200, searchable: true, sortable: true, index: true },
      description: { type: 'text', label: 'Description', rows: 5 },
      price: { type: 'float', label: 'Price', required: true, min: 0, sortable: true, filterable: true },
      compare_price: { type: 'float', label: 'Compare Price', min: 0 },
      category: { type: 'string', label: 'Category', filterable: true, sortable: true, index: true },
      sku: { type: 'string', label: 'SKU', unique: true, ui: { placeholder: 'e.g. PRD-001' } },
      stock: { type: 'int', label: 'Stock', default: 0, min: 0, sortable: true, filterable: true },
      in_stock: { type: 'bool', label: 'In Stock', default: true, filterable: true, sortable: true },
      images: { type: 'array', label: 'Images' },
      tags: { type: 'tags', label: 'Tags', filterable: true },
      status: { type: 'enum', label: 'Status', options: ['active', 'draft', 'archived'], default: 'active', filterable: true, sortable: true, ui: { badge: true, color: { active: 'green', draft: 'gray', archived: 'amber' } } },
    },
  },
  task: {
    label: 'Task',
    icon: ListChecks,
    desc: 'Title, status, priority, assignee, due date',
    color: 'amber',
    schema: {
      title: { type: 'string', label: 'Title', required: true, min: 2, searchable: true, sortable: true, index: true },
      description: { type: 'text', label: 'Description', rows: 4 },
      status: { type: 'enum', label: 'Status', options: ['backlog', 'todo', 'in_progress', 'review', 'done'], default: 'todo', filterable: true, sortable: true, index: true, ui: { badge: true, color: { backlog: 'gray', todo: 'slate', in_progress: 'blue', review: 'amber', done: 'green' } } },
      priority: { type: 'enum', label: 'Priority', options: ['low', 'medium', 'high', 'urgent'], default: 'medium', filterable: true, sortable: true, ui: { badge: true, color: { low: 'gray', medium: 'blue', high: 'amber', urgent: 'red' } } },
      assignee_id: { type: 'relation', label: 'Assignee', relation: { db: 'auth', collection: 'users', field: '_id', display: 'name' }, filterable: true },
      due_date: { type: 'date', label: 'Due Date', sortable: true, filterable: true },
      completed_at: { type: 'datetime', label: 'Completed At', sortable: true, hidden: true },
      tags: { type: 'tags', label: 'Labels', filterable: true },
    },
  },
  page: {
    label: 'Page',
    icon: Layout,
    desc: 'Title, slug, content, meta data',
    color: 'emerald',
    schema: {
      title: { type: 'string', label: 'Title', required: true, min: 1, max: 200, searchable: true, sortable: true },
      slug: { type: 'string', label: 'Slug', required: true, unique: true, regex: '/^[a-z0-9-]+$/' },
      content: { type: 'text', label: 'Content', required: true, rows: 10 },
      meta_title: { type: 'string', label: 'Meta Title', max: 60 },
      meta_description: { type: 'string', label: 'Meta Description', max: 160 },
      status: { type: 'enum', label: 'Status', options: ['draft', 'published'], default: 'draft', filterable: true, sortable: true, ui: { badge: true, color: { draft: 'gray', published: 'green' } } },
    },
  },
}

// ── KPI Computed ──
const totalDocs = computed(() =>
  collections.value.reduce((sum, c) => sum + (c.docCount || 0), 0)
)
const totalFields = computed(() =>
  collections.value.reduce((sum, c) => sum + (c.fieldCount || 0), 0)
)
const totalRelations = computed(() =>
  collections.value.reduce((sum, c) => sum + (c.relationCount || 0), 0)
)

// ── Lifecycle ──
onMounted(async () => {
  const r = await api.get('/databases')
  dbs.value = r.data.data
  if (dbs.value.length) {
    db.value = dbs.value[0]
    await load()
  }
})

// ── Load Collections ──
async function load() {
  if (!db.value) {
    collections.value = []
    return
  }
  loading.value = true
  try {
    const r = await api.get(`/databases/${db.value}/collections`)
    const names = r.data.data || []

    collections.value = await Promise.all(
      names.map(async (name) => {
        const meta = { name, docCount: 0, fieldCount: 0, relationCount: 0 }
        try {
          // Fetch documents with limit=1 to get total count
          const dr = await api.get(`/databases/${db.value}/collections/${name}/documents`, {
            params: { limit: 1, skip: 0 },
          })
          meta.docCount = dr.data.total ?? dr.data.data?.length ?? 0
        } catch (_) {
          // collection may exist but be empty or inaccessible
        }
        try {
          // Fetch schema to count fields and relations
          const sr = await api.get(`/databases/${db.value}/collections/${name}/schema`)
          const schema = sr.data.schema || {}
          meta.fieldCount = Object.keys(schema).length
          meta.relationCount = Object.values(schema).filter(
            (f) => f.type === 'relation' && f.relation
          ).length
        } catch (_) {
          // no schema
        }
        return meta
      })
    )
  } catch (e) {
    collections.value = []
  } finally {
    loading.value = false
  }
}

// ── Create Modal ──
function openCreateModal() {
  newColName.value = ''
  selectedTemplate.value = 'blank'
  createError.value = ''
  showCreateModal.value = true
  nextTick(() => {
    if (nameInput.value) nameInput.value.focus()
  })
}
function closeCreateModal() {
  showCreateModal.value = false
  createError.value = ''
}

async function createCollection() {
  const name = newColName.value.trim()
  if (!name) {
    createError.value = 'Nama collection tidak boleh kosong'
    return
  }
  // Validate name: alphanumeric + underscore
  if (!/^[a-z0-9_]+$/.test(name)) {
    createError.value = 'Nama collection hanya boleh berisi huruf kecil, angka, dan underscore'
    return
  }
  if (collections.value.some((c) => c.name === name)) {
    createError.value = `Collection "${name}" sudah ada di database "${db.value}"`
    return
  }

  creating.value = true
  createError.value = ''
  try {
    await api.post(`/databases/${db.value}/collections`, { name })

    // If template has schema, save it
    const tpl = templates[selectedTemplate.value]
    if (tpl && Object.keys(tpl.schema).length) {
      try {
        await api.post(`/databases/${db.value}/collections/${name}/schema`, { schema: tpl.schema })
      } catch (e) {
        // schema save failed but collection was created — continue
        console.warn('Schema save failed:', e)
      }
    }

    closeCreateModal()
    await load()
  } catch (e) {
    const msg = e.response?.data?.message || e.response?.data?.error || e.message
    createError.value = msg || 'Gagal membuat collection'
  } finally {
    creating.value = false
  }
}

// ── Delete ──
async function confirmDelete(name) {
  try {
    await api.delete(`/databases/${db.value}/collections/${name}`)
    deleteTarget.value = null
    await load()
  } catch (e) {
    toast.error('Gagal menghapus collection: ' + (e.response?.data?.message || e.message))
  }
}
</script>