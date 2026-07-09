<template>
  <div class="space-y-5 animate-fade-in">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-3">
      <a href="/" class="btn-ghost-sm !p-2 flex-shrink-0" title="Back to Dashboard">
        <ArrowLeft class="w-4 h-4" />
      </a>
      <div class="min-w-0">
        <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-lg bg-blue-500/10 border border-blue-500/20 grid place-items-center flex-shrink-0">
            <Database class="w-4 h-4 text-blue-400" />
          </div>
          <span class="truncate">{{ db }}</span>
        </h1>
        <p class="text-slate-500 text-sm mt-0.5 font-mono">{{ db }}.bangron</p>
      </div>
      <div class="ml-auto flex items-center gap-2">
        <button class="btn" @click="openCreateModal">
          <Plus class="w-4 h-4" />
          <span class="hidden sm:inline">New Collection</span>
        </button>
        <button class="btn-ghost" @click="load">
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <!-- KPI Row -->
    <div v-if="collections.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div class="kpi-card">
        <div class="text-xs text-slate-500 mb-1">Collections</div>
        <div class="text-xl font-bold text-white">{{ collections.length }}</div>
      </div>
      <div class="kpi-card">
        <div class="text-xs text-slate-500 mb-1">Total Documents</div>
        <div class="text-xl font-bold text-white">{{ totalDocs }}</div>
      </div>
      <div class="kpi-card hidden sm:block">
        <div class="text-xs text-slate-500 mb-1">Engine</div>
        <div class="text-xl font-bold text-white">SQLite</div>
      </div>
    </div>

    <!-- Collection Grid -->
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
              <div class="text-xs text-slate-500 mt-1">All documents and schema will be permanently deleted.</div>
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
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
          <div class="flex flex-wrap gap-2 mb-4">
            <span class="badge"><Hash class="w-3 h-3 mr-1 text-slate-500" />{{ col.docCount ?? 0 }} docs</span>
            <span class="badge"><Layers class="w-3 h-3 mr-1 text-slate-500" />{{ col.fieldCount ?? 0 }} fields</span>
          </div>
          <a :href="`/databases/${db}/collections/${col.name}`" class="btn-ghost-sm w-full text-center flex items-center justify-center gap-1.5">
            <ExternalLink class="w-3.5 h-3.5" />
            Open Documents
          </a>
        </template>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!collections.length && !loading" class="empty-state py-24">
      <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 grid place-items-center mb-5 mx-auto">
        <FolderPlus class="w-8 h-8 text-indigo-400" />
      </div>
      <div class="font-semibold text-slate-300 text-lg mb-1">No collections yet</div>
      <div class="text-sm text-slate-500 mb-5">Create your first collection in <span class="text-slate-400 font-mono">{{ db }}</span></div>
      <button class="btn" @click="openCreateModal"><Plus class="w-4 h-4" />Create Collection</button>
    </div>

    <!-- Create Collection Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50" @click.self="showCreateModal = false">
      <div class="card w-full max-w-md my-8 animate-scale-in">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-lg text-white flex items-center gap-2">
            <FolderPlus class="w-5 h-5 text-indigo-400" />
            New Collection
          </h3>
          <button class="btn-ghost-sm" @click="showCreateModal = false"><X class="w-4 h-4" /></button>
        </div>
        <div class="mb-5">
          <label class="section-label">Collection Name</label>
          <div class="relative">
            <FolderOpen class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
            <input v-model="newColName" class="input !pl-11" placeholder="e.g. posts, users" @keyup.enter="createCollection" ref="nameInput" />
          </div>
          <p v-if="createError" class="text-red-400 text-xs mt-1.5 flex items-center gap-1">
            <AlertCircle :size="12" />{{ createError }}
          </p>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-white/[0.06]">
          <button class="btn-ghost" @click="showCreateModal = false">Cancel</button>
          <button class="btn" :disabled="!newColName.trim() || creating" @click="createCollection">
            <Loader2 v-if="creating" class="w-4 h-4 animate-spin" />
            <Plus v-else class="w-4 h-4" />
            Create
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'
import {
  Database, FolderOpen, FolderPlus, Plus, ExternalLink, Trash2,
  RefreshCw, Hash, Layers, X, AlertCircle, Loader2, ArrowLeft,
} from 'lucide-vue-next'

const page = usePage()
const db = computed(() => page.props.db || '')
const api = axios.create({ baseURL: '' })
const toast = useToast()

const collections = ref([])
const loading = ref(false)
const deleteTarget = ref(null)
const showCreateModal = ref(false)
const newColName = ref('')
const creating = ref(false)
const createError = ref('')
const nameInput = ref(null)

const totalDocs = computed(() => collections.value.reduce((s, c) => s + (c.docCount || 0), 0))

onMounted(load)

async function load() {
  if (!db.value) return
  loading.value = true
  try {
    const r = await api.get(`/databases/${db.value}/collections`)
    const names = r.data.data || []
    collections.value = await Promise.all(
      names.map(async (name) => {
        const meta = { name, docCount: 0, fieldCount: 0 }
        try {
          const dr = await api.get(`/databases/${db.value}/collections/${name}/documents`, { params: { limit: 1, skip: 0 } })
          meta.docCount = dr.data.total ?? 0
        } catch {}
        try {
          const sr = await api.get(`/databases/${db.value}/collections/${name}/schema`)
          meta.fieldCount = Object.keys(sr.data.schema || {}).length
        } catch {}
        return meta
      })
    )
  } catch { collections.value = [] }
  finally { loading.value = false }
}

function openCreateModal() {
  newColName.value = ''
  createError.value = ''
  showCreateModal.value = true
  nextTick(() => nameInput.value?.focus())
}

async function createCollection() {
  const name = newColName.value.trim()
  if (!name) { createError.value = 'Nama collection wajib diisi'; return }
  if (!/^[a-z0-9_]+$/.test(name)) { createError.value = 'Hanya huruf kecil, angka, dan underscore'; return }
  creating.value = true
  createError.value = ''
  try {
    await api.post(`/databases/${db.value}/collections`, { name })
    showCreateModal.value = false
    await load()
  } catch (e) {
    createError.value = e.response?.data?.message || 'Gagal membuat collection'
  } finally { creating.value = false }
}

async function confirmDelete(name) {
  try {
    await api.delete(`/databases/${db.value}/collections/${name}`)
    deleteTarget.value = null
    await load()
  } catch (e) {
    toast.error('Gagal menghapus: ' + (e.response?.data?.message || e.message))
  }
}
</script>