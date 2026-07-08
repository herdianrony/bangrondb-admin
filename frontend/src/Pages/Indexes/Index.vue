<template>
  <div class="space-y-5 animate-fade-in">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-lg bg-amber-500/10 border border-amber-500/20 grid place-items-center">
        <Zap class="w-4 h-4 text-amber-400" />
      </div>
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-white">Indexes</h1>
        <p class="text-slate-500 text-sm mt-0.5">Create indexes to optimize query performance</p>
      </div>
    </div>
    <div class="card flex gap-3 flex-wrap items-end">
      <input v-model="db" placeholder="db" class="input w-44"/>
      <input v-model="col" placeholder="collection" class="input w-44"/>
      <input v-model="field" placeholder="field e.g. email / address.city" class="input flex-1"/>
      <input v-model="idxName" placeholder="index name (optional)" class="input w-48"/>
      <button class="btn flex items-center gap-2" @click="create"><Plus :size="16" /> Create Index</button>
      <button class="btn-ghost flex items-center gap-2" @click="load"><RefreshCw :size="16" /> Refresh</button>
    </div>
    <div class="card">
      <h3 class="font-semibold mb-2 flex items-center gap-2"><Zap :size="18" class="text-amber-400" /> Indexes</h3>
      <pre class="code-block overflow-auto">{{ JSON.stringify(metrics, null, 2) }}</pre>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { Zap, Plus, RefreshCw } from 'lucide-vue-next'
const db=ref('app'); const col=ref('users'); const field=ref('email'); const idxName=ref('')
const metrics=ref({})
async function load(){ const r=await axios.get(`/databases/${db.value}/indexes`); metrics.value=r.data }
async function create(){ await axios.post(`/databases/${db.value}/collections/${col.value}/indexes`, {field: field.value, name: idxName.value||null}); load() }
</script>