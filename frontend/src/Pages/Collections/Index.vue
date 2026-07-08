<template>
  <div class="space-y-5">
    <div class="card flex flex-wrap gap-3 items-end">
      <div class="w-full sm:w-48">
        <label class="section-label">Database</label>
        <select v-model="db" @change="load" class="input">
          <option value="">- select -</option>
          <option v-for="d in dbs" :key="d" :value="d">{{ d }}</option>
        </select>
      </div>
      <div class="flex-1 min-w-[180px]">
        <label class="section-label">Collection Name</label>
        <div class="relative">
          <FolderPlus class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
          <input v-model="newCol" class="input !pl-10" placeholder="users" @keyup.enter="create" />
        </div>
      </div>
      <button class="btn" @click="create">
        <Plus class="w-4 h-4" />
        Create
      </button>
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="c in cols" :key="c" class="card-hover">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-xl bg-violet-500/10 border border-violet-500/20 grid place-items-center">
            <FolderOpen class="w-5 h-5 text-violet-400" />
          </div>
          <div>
            <div class="font-bold">{{ c }}</div>
            <div class="text-xs text-slate-500 font-mono">{{ db }}.{{ c }}</div>
          </div>
        </div>
        <div class="flex gap-2">
          <a :href="`/app/documents?db=${db}&col=${c}`" class="btn-ghost-sm flex-1 text-center">
            <ExternalLink class="w-3.5 h-3.5" />
            Open
          </a>
          <button class="btn-ghost-sm" @click="rename(c)">
            <Pencil class="w-3.5 h-3.5" />
          </button>
          <button class="btn-ghost-sm !text-red-400 !border-red-800/40 hover:!bg-red-950/40" @click="drop(c)">
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>

    <div v-if="!cols.length && db" class="empty-state">
      <FolderOpen class="w-12 h-12 text-slate-700 mb-3" />
      <div class="font-medium text-slate-400">No collections</div>
      <div class="text-sm text-slate-600 mt-1">Create your first collection in {{ db }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { FolderOpen, FolderPlus, Plus, ExternalLink, Pencil, Trash2 } from 'lucide-vue-next'

const dbs = ref([])
const db = ref('app')
const cols = ref([])
const newCol = ref('')

onMounted(async () => {
  const r = await axios.get('/api/databases')
  dbs.value = r.data.data
  if (dbs.value[0]) { db.value = dbs.value[0]; load() }
})

async function load() {
  if (!db.value) return
  const r = await axios.get(`/api/${db.value}/collections`)
  cols.value = r.data.data
}
async function create() {
  if (!newCol.value) return
  await axios.post(`/api/${db.value}/collections`, { name: newCol.value })
  newCol.value = ''
  load()
}
async function drop(c) {
  if (!confirm('Drop ' + c + '?')) return
  await axios.delete(`/api/${db.value}/collections/${c}`)
  load()
}
async function rename(old) {
  const nn = prompt('Rename ' + old + ' to:', old + '_new')
  if (!nn) return
  await axios.post(`/api/${db.value}/collections/${old}/rename`, { new_name: nn })
  load()
}
</script>