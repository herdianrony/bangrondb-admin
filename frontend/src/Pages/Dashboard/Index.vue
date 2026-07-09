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
        <button class="btn" @click="actions.openCreate()">
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
            <button class="btn-ghost-sm !p-1.5" @click="actions.openRename(db)" title="Rename"><Pencil class="w-3.5 h-3.5" /></button>
            <button class="btn-ghost-sm !p-1.5 !text-red-400 !border-red-800/40 hover:!bg-red-950/40" @click="actions.openDrop(db)" title="Delete"><Trash2 class="w-3.5 h-3.5" /></button>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import { Box, Database, Plus, RefreshCw, ExternalLink, Pencil, Trash2, Zap, FolderOpen, FileText } from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'
import { useDatabaseActions, DB_CHANGED_EVENT } from '@/composables/useDatabaseActions'

const page = usePage()
const stats = computed(() => page.props.stats || {})

const list = ref([])
const loading = ref(false)

const actions = useDatabaseActions()

const kpis = computed(() => [
  { label: 'Databases', value: stats.value.databases ?? list.value.length ?? 0, icon: Database },
  { label: 'Collections', value: stats.value.collections ?? 0, icon: FolderOpen },
  { label: 'Documents', value: stats.value.documents ?? 0, icon: FileText },
  { label: 'Health', value: stats.value.health?.status || 'OK', icon: Zap },
])

onMounted(() => {
  load()
  window.addEventListener(DB_CHANGED_EVENT, load)
})
onBeforeUnmount(() => window.removeEventListener(DB_CHANGED_EVENT, load))

async function load() {
  loading.value = true
  try {
    const r = await axios.get('/databases')
    list.value = r.data.data || []
  } catch { list.value = [] }
  finally { loading.value = false }
}
</script>
