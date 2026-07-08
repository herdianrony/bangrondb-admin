<template>
  <div class="space-y-4">
    <div class="card">
      <h2 class="font-bold text-lg flex items-center gap-2"><Anchor :size="20" class="text-indigo-400" /> Hooks</h2>
      <p class="text-slate-400 text-sm">6 lifecycle events available per collection</p>
      <div class="grid md:grid-cols-3 gap-3 mt-4 text-sm">
        <div v-for="h in hooks" :key="h.event" class="p-3 rounded-xl bg-slate-950 border border-slate-800">
          <div class="font-semibold text-indigo-300 flex items-center gap-2"><Code2 :size="14" /> {{ h.event }}</div>
          <div class="text-slate-400 text-xs mt-1">{{ h.desc }}</div>
          <pre class="code-block mt-2">{{ h.code }}</pre>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="text-sm text-slate-300 flex items-center gap-2">
        Hooks loaded from API for <code>{{ db }}/{{ col }}</code>:
        <button class="btn-ghost-sm flex items-center gap-1" @click="load"><RefreshCw :size="14" /> Load</button>
      </div>
      <pre class="code-block mt-2">{{ out }}</pre>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { Anchor, Code2, RefreshCw } from 'lucide-vue-next'
const db=ref('app'); const col=ref('users'); const out=ref('[]')
const hooks=[
  {event:'beforeInsert', desc:'Mutate document before insert', code:"$c->on('beforeInsert', fn($doc)=>[...])"},
  {event:'afterInsert', desc:'Logging / notification', code:"afterInsert($doc,$id)"},
  {event:'beforeUpdate', desc:'Add updated_at timestamp', code:"return ['criteria'=>$c,'data'=>$d]"},
  {event:'afterUpdate', desc:'Audit trail', code:"afterUpdate(...)"},
  {event:'beforeRemove', desc:'Prevent deletion of protected records', code:"return false = cancel"},
  {event:'afterRemove', desc:'Cleanup', code:"afterRemove($doc)"},
]
async function load(){ const r=await axios.get(`/databases/${db.value}/collections/${col.value}/hooks`); out.value=JSON.stringify(r.data,null,2)}
</script>