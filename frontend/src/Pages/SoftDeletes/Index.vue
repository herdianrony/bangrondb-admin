<template>
  <div class="space-y-4">
    <div class="card">
      <h2 class="font-bold text-lg mb-2 flex items-center gap-2"><Trash2 :size="20" class="text-slate-300" /> Soft Deletes</h2>
      <div class="grid md:grid-cols-4 gap-3 items-end">
        <input v-model="db" class="input" placeholder="db"/>
        <input v-model="col" class="input" placeholder="collection"/>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="enabled"/> useSoftDeletes</label>
        <button class="btn flex items-center justify-center gap-2" @click="toggle"><Save :size="16" /> Apply</button>
      </div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <div class="card">
        <h3 class="font-semibold mb-2 flex items-center gap-2"><RotateCcw :size="16" class="text-emerald-400" /> Restore</h3>
        <textarea v-model="filter" rows="4" class="input font-mono text-sm">{ "username": "johndoe" }</textarea>
        <button class="btn w-full mt-2 flex items-center justify-center gap-2" @click="restore"><RotateCcw :size="16" /> Restore</button>
      </div>
      <div class="card">
        <h3 class="font-semibold mb-2 text-red-300 flex items-center gap-2"><AlertTriangle :size="16" /> Force Delete</h3>
        <textarea v-model="filter2" rows="4" class="input font-mono text-sm">{ "status": "banned" }</textarea>
        <button class="badge-danger w-full mt-2 text-center cursor-pointer" @click="forceDelete"><Trash2 :size="16" class="inline" /> Force Delete Permanent</button>
      </div>
    </div>
    <pre class="code-block">{{ out }}</pre>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { Trash2, RotateCcw, AlertTriangle, Save } from 'lucide-vue-next'
const db=ref('app'); const col=ref('users'); const enabled=ref(true)
const filter=ref('{ "username": "johndoe" }')
const filter2=ref('{ "status": "banned" }')
const out=ref('')
async function toggle(){ const r=await axios.post(`/databases/${db.value}/collections/${col.value}/soft-deletes`, {enabled: enabled.value}); out.value=JSON.stringify(r.data,null,2)}
async function restore(){ const r=await axios.post(`/databases/${db.value}/collections/${col.value}/restore`, {filter: JSON.parse(filter.value)}); out.value=JSON.stringify(r.data,null,2)}
async function forceDelete(){ const r=await axios.post(`/databases/${db.value}/collections/${col.value}/force-delete`, {filter: JSON.parse(filter2.value)}); out.value=JSON.stringify(r.data,null,2)}
</script>