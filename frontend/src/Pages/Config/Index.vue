<template>
  <div class="space-y-4">
    <div class="card">
      <h2 class="font-bold text-lg flex items-center gap-2"><Settings :size="20" class="text-slate-300" /> Settings</h2>
      <div class="flex gap-3 flex-wrap">
        <input v-model="db" class="input w-40" placeholder="db"/>
        <input v-model="col" class="input w-48" placeholder="collection"/>
        <button class="btn-ghost flex items-center gap-2" @click="load"><RefreshCw :size="16" /> Load Config</button>
        <button class="btn flex items-center gap-2" @click="save"><Save :size="16" /> Save</button>
      </div>
    </div>
    <div class="grid lg:grid-cols-2 gap-4">
      <div class="card">
        <h3 class="font-semibold mb-2">ID Mode</h3>
        <select v-model="idMode" class="input mb-2">
          <option value="auto">auto (UUID)</option>
          <option value="manual">manual</option>
          <option value="prefix">prefix</option>
        </select>
        <input v-if="idMode==='prefix'" v-model="prefix" class="input" placeholder="USR"/>
        <button class="btn w-full mt-2" @click="applyId">Apply ID Mode</button>
        <p class="text-xs text-slate-400 mt-2">Configuration is persisted via saveConfiguration()</p>
      </div>
      <div class="card">
        <h3 class="font-semibold mb-2">Current Configuration</h3>
        <pre class="code-block max-h-[320px] overflow-auto">{{ JSON.stringify(cfg, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { Settings, Save, RefreshCw } from 'lucide-vue-next'
const db=ref('app'); const col=ref('users'); const cfg=ref({})
const idMode=ref('auto'); const prefix=ref('USR')
async function load(){ const r=await axios.get(`/api/${db.value}/${col.value}/config`); cfg.value=r.data }
async function save(){ await axios.post(`/api/${db.value}/${col.value}/config/save`); load() }
async function applyId(){ await axios.post(`/api/${db.value}/${col.value}/id-mode`, {mode:idMode.value, prefix: prefix.value}); load() }
</script>