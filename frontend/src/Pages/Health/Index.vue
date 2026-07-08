<template>
  <div class="space-y-4">
    <div class="card flex gap-3 items-end flex-wrap">
      <input v-model="db" class="input w-56" placeholder="database name"/>
      <button class="btn flex items-center gap-2" @click="load"><Heart :size="16" /> Check Health</button>
      <button class="btn-ghost flex items-center gap-2" @click="vacuum"><HardDrive :size="16" /> Vacuum</button>
      <span class="badge" v-if="last">updated {{ last }}</span>
    </div>
    <div class="grid md:grid-cols-3 gap-4" v-if="health.metrics">
      <div class="kpi-card" v-for="(v,k) in health.metrics" :key="k">
        <div class="text-slate-400 text-xs">{{ k }}</div>
        <div class="text-2xl font-bold">{{ typeof v === 'object' ? JSON.stringify(v) : v }}</div>
      </div>
    </div>
    <div class="grid lg:grid-cols-2 gap-4">
      <div class="card">
        <h3 class="font-semibold mb-2 flex items-center gap-2"><Heart :size="16" class="text-rose-400" /> Health Report</h3>
        <pre class="code-block overflow-auto max-h-[380px]">{{ JSON.stringify(health.report, null, 2) }}</pre>
      </div>
      <div class="card">
        <h3 class="font-semibold mb-2 flex items-center gap-2"><RefreshCw :size="16" class="text-indigo-400" /> Performance</h3>
        <pre class="code-block overflow-auto max-h-[380px]">{{ JSON.stringify(metrics, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { Heart, RefreshCw, HardDrive } from 'lucide-vue-next'
const db = ref('app')
const health = ref({})
const metrics = ref({})
const last = ref('')
async function load(){
  const h = await axios.get(`/api/${db.value}/health`)
  health.value = h.data
  const m = await axios.get(`/api/${db.value}/metrics`)
  metrics.value = m.data
  last.value = new Date().toLocaleTimeString()
}
async function vacuum(){ await axios.post(`/api/${db.value}/vacuum`); load() }
</script>