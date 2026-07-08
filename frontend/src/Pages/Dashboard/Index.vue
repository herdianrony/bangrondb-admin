<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center shadow-lg shadow-indigo-500/20">
          <Box class="w-5 h-5 text-white" />
        </div>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-white">Databases</h1>
          <p class="text-slate-500 text-sm mt-0.5">Your BangronDB databases</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button class="btn" @click="openCreateModal">
          <Plus class="w-4 h-4" />
          <span class="hidden sm:inline">New Database</span>
        </button>
        <button class="btn-ghost" @click="load">
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="kpi-card" v-for="k in kpis" :key="k.label">
        <div class="flex items-center gap-2 text-slate-500 mb-2">
          <component :is="k.icon" class="w-4 h-4" />
          <span class="text-xs font-medium">{{ k.label }}</span>
        </div>
        <div class="text-2xl font-bold text-white tracking-tight">{{ k.value }}</div>
      </div>
    </div>

    <!-- Database Cards -->
    <div v-if="list.length" class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="db in list" :key="db" class="card-hover group">
        <div class="flex justify-between items-start mb-4">
          <a :href="`/databases/${db}`" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 grid place-items-center">
              <Database class="w-5 h-5 text-indigo-400" />
            </div>
            <div>
              <div class="font-bold text-white">{{ db }}</div>
              <div class="text-xs text-slate-500 font-mono">{{ db }}.bangron</div>
            </div>
          </a>
          <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button class="btn-ghost-sm !p-1.5" @click="renameDb(db)" title="Rename"><Pencil class="w-3.5 h-3.5" /></button>
            <button class="btn-ghost-sm !p-1.5 !text-red-400 !border-red-800/40 hover:!bg-red-950/40" @click="drop(db)" title="Delete"><Trash2 class="w-3.5 h-3.5" /></button>
          </div>
        </div>
        <a :href="`/databases/${db}`" class="btn-ghost-sm w-full text-center flex items-center justify-center gap-1.5">
          <ExternalLink class="w-3.5 h-3.5" />
          Open
        </a>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!list.length && !loading" class="empty-state py-24">
      <Database class="w-12 h-12 text-slate-700 mb-3" />
      <div class="font-medium text-slate-400">No databases yet</div>
      <div class="text-sm text-slate-600 mt-1">Create your first database to get started</div>
    </div>

    <!-- Create DB Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50" @click.self="showModal = false">
      <div class="card w-full max-w-sm my-8 animate-scale-in">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-lg text-white">New Database</h3>
          <button class="btn-ghost-sm" @click="showModal = false"><X class="w-4 h-4" /></button>
        </div>
        <div class="mb-5">
          <label class="section-label">Database Name</label>
          <div class="relative">
            <Database class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
            <input v-model="name" class="input !pl-10" placeholder="app_v2" @keyup.enter="createDB" ref="nameInput" />
          </div>
          <p v-if="error" class="text-red-400 text-xs mt-1.5">{{ error }}</p>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-white/[0.06]">
          <button class="btn-ghost" @click="showModal = false">Cancel</button>
          <button class="btn" :disabled="!name.trim()" @click="createDB"><Plus class="w-4 h-4" />Create</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import { Box, Database, Plus, RefreshCw, ExternalLink, Pencil, Trash2, X, Zap, FolderOpen, FileText } from 'lucide-vue-next'

const page = usePage()
const stats = computed(() => page.props.stats || {})

const list = ref([])
const loading = ref(false)
const name = ref('')
const showModal = ref(false)
const error = ref('')
const nameInput = ref(null)

const kpis = computed(() => [
  { label: 'Databases', value: stats.value.databases ?? list.value.length ?? 0, icon: Database },
  { label: 'Collections', value: stats.value.collections ?? 0, icon: FolderOpen },
  { label: 'Documents', value: stats.value.documents ?? 0, icon: FileText },
  { label: 'Health', value: stats.value.health?.status || 'OK', icon: Zap },
])

onMounted(load)

async function load() {
  loading.value = true
  try {
    const r = await axios.get('/databases')
    list.value = r.data.data || []
  } catch { list.value = [] }
  finally { loading.value = false }
}

function openCreateModal() {
  name.value = ''; error.value = ''; showModal.value = true
  nextTick(() => nameInput.value?.focus())
}

async function createDB() {
  if (!name.value.trim()) return
  error.value = ''
  try {
    await axios.post('/databases', { name: name.value.trim() })
    name.value = ''; showModal.value = false; load()
  } catch (e) { error.value = e.response?.data?.message || 'Gagal membuat database' }
}

async function drop(db) {
  if (!confirm('Drop ' + db + '?')) return
  try { await axios.delete('/databases/' + db); load() } catch (e) { alert(e.response?.data?.message || e.message) }
}

async function renameDb(old) {
  const nn = prompt('Rename ' + old + ' to:', old + '_v2')
  if (!nn) return
  try { await axios.post(`/databases/${old}/rename`, { new_name: nn }); load() } catch (e) { alert(e.response?.data?.message || e.message) }
}

function refresh() { router.reload({ only: ['stats'] }) }
</script>