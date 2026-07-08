<template>
  <div class="space-y-5">
    <div class="card">
      <div class="flex items-center gap-2 mb-3">
        <Database :size="20" class="text-indigo-400" />
        <h2 class="font-bold text-lg">Query Builder</h2>
      </div>
      <div class="grid md:grid-cols-3 gap-3">
        <div>
          <label class="section-label">Database</label>
          <input v-model="db" class="input" placeholder="app"/>
        </div>
        <div>
          <label class="section-label">Collection</label>
          <input v-model="col" class="input" placeholder="users"/>
        </div>
        <div class="flex items-end">
          <button class="btn w-full flex items-center justify-center gap-2" @click="run">
            <Play :size="16" />
            Run Query
          </button>
        </div>
      </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
      <div class="card">
        <div class="flex items-center gap-2 mb-2">
          <Filter :size="16" class="text-slate-400" />
          <label class="section-label">Filter (Mongo-style)</label>
        </div>
        <textarea v-model="filter" rows="10" class="input font-mono text-sm"></textarea>
        <div class="flex flex-wrap gap-2 mt-2 text-[11px]">
          <button v-for="s in samples" :key="s.label" @click="filter=s.code" class="badge hover:bg-slate-800">{{ s.label }}</button>
        </div>
      </div>
      <div class="card">
        <div class="grid grid-cols-2 gap-3 mb-2">
          <div>
            <label class="section-label">Projection</label>
            <input v-model="projection" class="input font-mono" placeholder='{"name":1}'/>
          </div>
          <div>
            <label class="section-label">Sort</label>
            <input v-model="sort" class="input font-mono" placeholder='{"age":-1}'/>
          </div>
        </div>
        <label class="section-label">Results ({{ results.length }})</label>
        <pre class="code-block h-[260px] overflow-auto">{{ JSON.stringify(results, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { Search, Play, Filter, Database, FolderOpen } from 'lucide-vue-next'

const db = ref('app')
const col = ref('users')
const filter = ref('{\n  "age": { "$gte": 21 },\n  "status": "active"\n}')
const projection = ref('')
const sort = ref('{"_id":-1}')
const results = ref([])

const samples = [
  {label:'$gt', code:'{\n  "age": { "$gt": 18 }\n}'},
  {label:'$in', code:'{\n  "role": { "$in": ["admin","editor"] }\n}'},
  {label:'$or', code:'{\n  "$or": [\n    {"age":{"$lt":18}},\n    {"age":{"$gt":65}}\n  ]\n}'},
  {label:'$regex', code:'{\n  "name": { "$regex": "^John" }\n}'},
  {label:'$exists', code:'{\n  "email": { "$exists": true }\n}'},
  {label:'dot notation', code:'{\n  "address.city": "Jakarta"\n}'},
  {label:'$fuzzy', code:'{\n  "description": {\n    "$fuzzy": { "$search": "important", "$minScore": 0.7 }\n  }\n}'},
]

async function run(){
  try{
    const body = {
      filter: JSON.parse(filter.value||'{}'),
      projection: projection.value ? JSON.parse(projection.value) : null,
      sort: sort.value ? JSON.parse(sort.value) : {},
      limit: 50, skip: 0
    }
    const r = await axios.post(`/databases/${db.value}/collections/${col.value}/query`, body)
    results.value = r.data.data
  }catch(e){ results.value = [{error: e.message}] }
}
</script>