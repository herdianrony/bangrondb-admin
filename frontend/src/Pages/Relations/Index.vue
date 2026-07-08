<template>
  <div class="space-y-4">
    <div class="card">
      <h2 class="font-bold text-lg flex items-center gap-2"><Link :size="20" class="text-cyan-400" /> Relations</h2>
      <p class="text-sm text-slate-400">Populate relations across collections and databases.</p>
    </div>
    <div class="grid lg:grid-cols-2 gap-4">
      <div class="card space-y-3">
        <div class="section-label">Source</div>
        <div class="grid grid-cols-2 gap-2">
          <input v-model="db" class="input" placeholder="db"/>
          <input v-model="col" class="input" placeholder="collection e.g. posts"/>
        </div>
        <div class="section-label">Relation Config</div>
        <input v-model="localField" class="input" placeholder="local_field e.g. author_id"/>
        <input v-model="foreign" class="input" placeholder="foreign e.g. app.users"/>
        <input v-model="as" class="input" placeholder="as e.g. author (optional)"/>
        <div class="section-label">Filter</div>
        <textarea v-model="filter" rows="3" class="input font-mono text-sm" placeholder='{}'></textarea>
        <button class="btn w-full flex items-center justify-center gap-2" @click="run"><Play :size="16" /> Populate</button>
      </div>
      <div class="card">
        <div class="section-label mb-2 flex items-center gap-2"><Database :size="14" /> Output</div>
        <pre class="code-block h-[300px] overflow-auto">{{ out }}</pre>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { Link, Play, Database } from 'lucide-vue-next'
const db=ref('app'); const col=ref('posts'); const localField=ref('author_id'); const foreign=ref('app.users'); const as=ref('author'); const filter=ref('{}')
const out=ref('[]')
async function run(){
  const r = await axios.post(`/databases/${db.value}/collections/${col.value}/populate`, {
    filter: JSON.parse(filter.value||'{}'),
    local_field: localField.value,
    foreign: foreign.value,
    as: as.value || undefined
  })
  out.value = JSON.stringify(r.data.data, null, 2)
}
</script>