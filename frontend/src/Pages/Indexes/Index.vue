<template>
  <div class="space-y-4">
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
async function load(){ const r=await axios.get(`/api/${db.value}/indexes`); metrics.value=r.data }
async function create(){ await axios.post(`/api/${db.value}/${col.value}/indexes`, {field: field.value, name: idxName.value||null}); load() }
</script>