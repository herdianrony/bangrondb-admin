<template>
  <div class="space-y-5 animate-fade-in">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-lg bg-blue-500/10 border border-blue-500/20 grid place-items-center">
        <Database class="w-4 h-4 text-blue-400" />
      </div>
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-white">Databases</h1>
        <p class="text-slate-500 text-sm mt-0.5">Create, rename, and manage your databases</p>
      </div>
    </div>
    <div class="card flex flex-wrap gap-3 items-end">
      <div class="flex-1 min-w-[200px]">
        <label class="section-label">Database Name</label>
        <div class="relative">
          <Database class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
          <input v-model="name" class="input !pl-10" placeholder="app_v2" @keyup.enter="createDB" />
        </div>
      </div>
      <button class="btn" @click="createDB">
        <Plus class="w-4 h-4" />
        Create DB
      </button>
      <button class="btn-ghost" @click="load">
        <RefreshCw class="w-4 h-4" />
        Refresh
      </button>
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="db in list" :key="db" class="card-hover">
        <div class="flex justify-between items-start mb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 grid place-items-center">
              <Database class="w-5 h-5 text-indigo-400" />
            </div>
            <div>
              <div class="font-bold">{{ db }}</div>
              <div class="text-xs text-slate-500 font-mono">{{ db }}.bangron</div>
            </div>
          </div>
          <span class="badge">SQLite</span>
        </div>
        <div class="flex gap-2">
          <button class="btn-ghost-sm flex-1" @click="openDb(db)">
            <ExternalLink class="w-3.5 h-3.5" />
            Open
          </button>
          <button class="btn-ghost-sm" @click="renameDb(db)">
            <Pencil class="w-3.5 h-3.5" />
          </button>
          <button class="btn-ghost-sm !text-red-400 !border-red-800/40 hover:!bg-red-950/40" @click="drop(db)">
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>

    <div v-if="!list.length" class="empty-state">
      <Database class="w-12 h-12 text-slate-700 mb-3" />
      <div class="font-medium text-slate-400">No databases yet</div>
      <div class="text-sm text-slate-600 mt-1">Create your first database above</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { Database, Plus, RefreshCw, ExternalLink, Pencil, Trash2 } from 'lucide-vue-next'

const list = ref([])
const name = ref('')

async function load() {
  const r = await axios.get('/databases')
  list.value = r.data.data
}
async function createDB() {
  if (!name.value) return
  await axios.post('/databases', { name: name.value })
  name.value = ''
  load()
}
async function drop(db) {
  if (!confirm('Drop ' + db + ' ?')) return
  await axios.delete('/databases/' + db)
  load()
}
async function renameDb(old) {
  const nn = prompt('Rename ' + old + ' to:', old + '_v2')
  if (!nn) return
  await axios.post(`/databases/${old}/rename`, { new_name: nn })
  load()
}
function openDb(db) {
  location.href = '/collections?db=' + db
}
onMounted(load)
</script>