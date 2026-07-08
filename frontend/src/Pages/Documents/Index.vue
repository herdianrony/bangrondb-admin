<template>
  <div class="space-y-5">
    <!-- selector -->
    <div class="card">
      <div class="flex flex-wrap gap-3 items-end">
        <div><label class="section-label flex items-center gap-1"><Database :size="13"/> Database</label>
          <select v-model="db" @change="loadCols" class="input w-44">
            <option value="">Select database…</option>
            <option v-for="d in databases" :key="d" :value="d">{{ d }}</option>
          </select>
        </div>
        <div><label class="section-label flex items-center gap-1"><FolderOpen :size="13"/> Collection</label>
          <select v-model="collection" @change="onCollectionChange" class="input w-48">
            <option value="">Select collection…</option>
            <option v-for="c in collections" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>
        <div class="flex items-center gap-3 text-xs ml-auto">
          <label class="flex items-center gap-1"><input type="checkbox" v-model="autoPopulate"> auto populate relations</label>
          <label class="flex items-center gap-1"><input type="checkbox" v-model="tableMode"> table mode</label>
          <button class="btn-ghost-sm flex items-center gap-1" @click="loadDocs"><RefreshCw :size="14"/> Refresh</button>
          <button class="btn-sm flex items-center gap-1" @click="openInsert"><Plus :size="14"/> Insert</button>
        </div>
      </div>
      <div class="flex gap-3 mt-3 text-xs flex-wrap items-center">
        <label><input type="checkbox" v-model="withTrashed"> withTrashed</label>
        <label><input type="checkbox" v-model="onlyTrashed"> onlyTrashed</label>
        <span class="badge badge-info">total: {{ total }}</span>
        <span class="badge badge-info">limit {{ limit }} skip {{ skip }}</span>
        <span v-if="Object.keys(schema).length" class="badge badge-success">schema: {{ Object.keys(schema).length }} fields</span>
        <span v-if="relationFields.length" class="badge badge-info">relations: {{ relationFields.map(r=>r.field).join(', ') }}</span>
      </div>
    </div>

    <!-- Table auto columns -->
    <div class="card overflow-auto">
      <div v-if="tableMode && columns.length" class="table-container">
        <table class="data-table w-full text-sm min-w-[800px]">
          <thead>
            <tr class="text-slate-400 border-b border-slate-800">
              <th class="text-left py-2 px-2 w-12">#</th>
              <th v-for="col in columns" :key="col.field"
                  class="text-left py-2 px-3 cursor-pointer select-none whitespace-nowrap"
                  :class="col.sortable ? 'hover:text-white' : ''"
                  @click="col.sortable && toggleSort(col.field)">
                <div class="flex items-center gap-1">
                  {{ col.label }}
                  <span v-if="col.sortable" class="text-[10px] inline-flex items-center">
                    <ChevronUp v-if="sortField===col.field && sortDir===1" :size="12"/>
                    <ChevronDown v-else-if="sortField===col.field && sortDir===-1" :size="12"/>
                    <ArrowUpDown v-else :size="12" class="opacity-40"/>
                  </span>
                </div>
                <div class="text-[10px] text-slate-500">{{ col.type }}<span v-if="col.relation"> • rel</span></div>
              </th>
              <th class="text-right px-2 w-28">Actions</th>
            </tr>
            <!-- filter row -->
            <tr v-if="hasFilterable" class="border-b border-slate-800 bg-slate-950/50">
              <td class="px-2 py-1 text-[10px] text-slate-500 flex items-center gap-1"><Filter :size="10"/></td>
              <td v-for="col in columns" :key="'f-'+col.field" class="px-2 py-1">
                <input v-if="col.filterable"
                  v-model="columnFilters[col.field]"
                  @keyup.enter="applyColumnFilter"
                  :placeholder="col.label"
                  class="w-full bg-slate-900 border border-slate-800 rounded px-2 py-1 text-xs"/>
                <span v-else class="text-slate-700 text-[10px]">–</span>
              </td>
              <td class="text-right px-2">
                <button class="text-[10px] text-indigo-300" @click="applyColumnFilter">Apply</button>
                <button class="text-[10px] text-slate-500 ml-2" @click="clearColumnFilter">Clear</button>
              </td>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(doc,i) in docs" :key="doc._id" class="border-b border-slate-900 card-hover">
              <td class="px-2 py-2 text-slate-500 text-xs">{{ skip + i +1 }}</td>
              <td v-for="col in columns" :key="col.field" class="px-3 py-2 align-top max-w-[260px]">
                <!-- relation display -->
                <template v-if="col.relation">
                  <span class="text-cyan-300">
                    {{ getRelationDisplay(col, doc[col.field]) || doc[col.field] || '–' }}
                  </span>
                  <div class="text-[10px] text-slate-500 font-mono">{{ doc[col.field] }}</div>
                </template>
                <!-- enum badge -->
                <template v-else-if="col.badge && doc[col.field]">
                  <span :class="badgeClass(col, doc[col.field])">{{ doc[col.field] }}</span>
                </template>
                <!-- tags -->
                <template v-else-if="col.type==='tags' && Array.isArray(doc[col.field])">
                  <span v-for="t in doc[col.field]" :key="t" class="text-[10px] bg-slate-800 px-2 py-0.5 rounded-full mr-1">{{ t }}</span>
                </template>
                <!-- bool -->
                <template v-else-if="col.type==='bool' || col.type==='boolean'">
                  <Check v-if="doc[col.field]" :size="15" class="text-emerald-400"/>
                  <X v-else :size="15" class="text-slate-500"/>
                </template>
                <!-- default -->
                <template v-else>
                  <span class="truncate block" :title="String(doc[col.field] ?? '')">
                    {{ formatCell(doc[col.field]) }}
                  </span>
                </template>
              </td>
              <td class="text-right px-2">
                <button class="btn-ghost-sm inline-flex items-center justify-center p-1.5 mr-1" title="Edit" @click="edit(doc)"><Pencil :size="14" class="text-indigo-300"/></button>
                <button class="btn-ghost-sm inline-flex items-center justify-center p-1.5" title="Delete" @click="removeDoc(doc)"><Trash2 :size="14" class="text-red-400"/></button>
              </td>
            </tr>
            <tr v-if="!docs.length"><td :colspan="columns.length+2" class="py-8 text-center text-slate-400 empty-state">{{ collection ? 'No data found' : 'Select a database and collection' }}</td></tr>
          </tbody>
        </table>
      </div>

      <!-- fallback JSON view -->
      <div v-else class="table-container">
        <table class="data-table w-full text-sm">
          <thead><tr class="text-slate-400"><th class="text-left">_id</th><th class="text-left">Document</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            <tr v-for="doc in docs" :key="doc._id" class="border-t border-slate-800 card-hover">
              <td class="py-2 pr-3 font-mono text-xs text-indigo-300">{{ doc._id }}</td>
              <td><pre class="text-xs whitespace-pre-wrap code-block">{{ prettyPopulated(doc) }}</pre></td>
              <td class="text-right">
                <button class="btn-ghost-sm inline-flex items-center justify-center p-1.5 mr-1" title="Edit" @click="edit(doc)"><Pencil :size="14" class="text-indigo-300"/></button>
                <button class="btn-ghost-sm inline-flex items-center justify-center p-1.5" title="Delete" @click="removeDoc(doc)"><Trash2 :size="14" class="text-red-300"/></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex justify-between items-center mt-3 text-xs">
        <div class="flex gap-2">
          <button class="btn-ghost-sm inline-flex items-center gap-1" :disabled="skip===0" @click="prevPage"><ChevronLeft :size="14"/> Prev</button>
          <button class="btn-ghost-sm inline-flex items-center gap-1" @click="nextPage">Next <ChevronRight :size="14"/></button>
          <select v-model.number="limit" @change="skip=0;loadDocs()" class="bg-slate-950 border border-slate-800 rounded px-2 py-1">
            <option :value="10">10</option><option :value="25">25</option><option :value="50">50</option><option :value="100">100</option>
          </select>
        </div>
        <div class="text-slate-400">page {{ Math.floor(skip/limit)+1 }} • {{ total }} total</div>
      </div>
    </div>

    <!-- Insert/Edit Modal – Schema Aware -->
    <div v-if="showInsert || editing" class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50 overflow-y-auto" @click.self="closeModal">
      <div class="card w-full max-w-3xl my-8">
        <div class="flex justify-between items-start mb-3">
          <div>
            <h3 class="font-bold text-lg">{{ editing ? 'Edit Document' : 'Insert Document' }}</h3>
            <div class="text-xs text-slate-400">{{ db }} / {{ collection }} • 
              <span v-if="Object.keys(schema).length">{{ Object.keys(schema).length }} field schema • {{ relationFields.length }} relation</span>
              <span v-else>no schema – free JSON</span>
            </div>
          </div>
          <button class="btn-ghost-sm flex items-center gap-1" @click="useRaw=!useRaw">
            <Table2 v-if="useRaw" :size="14"/>
            <FileJson v-else :size="14"/>
            {{ useRaw ? 'Form Schema' : 'Raw JSON' }}
          </button>
        </div>

        <div v-if="!useRaw && Object.keys(schema).length">
          <SchemaForm :schema="schema" v-model="formModel" :api-error="error" @validate="validateServer"/>
        </div>
        <div v-else>
          <textarea v-model="editorText" rows="14" class="input font-mono text-sm"></textarea>
        </div>

        <div class="flex justify-between items-center mt-4 text-xs">
          <div>
            <button class="btn-ghost-sm inline-flex items-center gap-1" @click="validateServer(getPayload())"><Check :size="14"/> Validate</button>
            <span v-if="validationMsg" :class="validationOk ? 'text-emerald-400' : 'text-amber-300'">{{ validationMsg }}</span>
          </div>
          <div class="flex gap-2">
            <button class="btn-ghost-sm" @click="closeModal">Cancel</button>
            <button class="btn-sm flex items-center gap-1" @click="saveDoc"><Save :size="14"/> Save</button>
          </div>
        </div>
        <p v-if="error" class="text-red-400 text-sm mt-2 bg-red-950/20 p-2 rounded-lg">{{ error }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import SchemaForm from '@/Components/SchemaForm.vue'
import {
  Database,
  FolderOpen,
  FileText,
  Search,
  RefreshCw,
  Plus,
  ChevronLeft,
  ChevronRight,
  ChevronUp,
  ChevronDown,
  ArrowUpDown,
  Pencil,
  Trash2,
  X,
  Table,
  FileJson,
  Filter,
  Check,
  Save,
  Table2,
  ToggleLeft,
  ToggleRight,
  Columns3,
  Link,
} from 'lucide-vue-next'
const api = axios.create({ baseURL: '/api' })

const databases = ref([])
const collections = ref([])
const db = ref('')
const collection = ref('')
const docs = ref([])
const total = ref(0)
const filterText = ref('{}')
const limit = ref(25)
const skip = ref(0)
const withTrashed = ref(false)
const onlyTrashed = ref(false)

const showInsert = ref(false)
const editing = ref(null)
const editorText = ref('{\n  "name": "",\n  "email": ""\n}')
const error = ref('')

// schema-aware
const schema = ref({})
const formModel = ref({})
const useRaw = ref(false)
const validationMsg = ref('')
const validationOk = ref(false)

// table mode
const tableMode = ref(true)
const sortField = ref('_id')
const sortDir = ref(-1)
const columnFilters = ref({})
const autoPopulate = ref(true)
const relationCache = ref({}) // { "auth.users": {id:display,...} }

const columns = computed(()=>{
  if(!Object.keys(schema.value).length){
    // fallback: infer from first doc
    const first = docs.value[0] || {}
    return Object.keys(first).filter(k=>k!=='_id').slice(0,6).map(f=>({
      field:f, label:f, type: typeof first[f], sortable:true, filterable:true
    }))
  }
  return Object.entries(schema.value)
    .filter(([f,def])=> !def.hidden)
    .map(([field,def])=>({
      field,
      label: def.label || field,
      type: def.type || 'string',
      sortable: !!def.sortable,
      filterable: !!def.filterable,
      badge: def.ui?.badge || false,
      colorMap: def.ui?.color || null,
      relation: def.relation || (def.type==='relation' ? def.relation : null),
      readonly: !!def.readonly,
    }))
})

const relationFields = computed(()=> columns.value.filter(c=>c.relation))
const hasFilterable = computed(()=> columns.value.some(c=>c.filterable))

function toggleSort(field){
  if(sortField.value===field){ sortDir.value = -sortDir.value }
  else { sortField.value=field; sortDir.value=1 }
  loadDocs()
}
function applyColumnFilter(){
  // build $and filter from columnFilters
  const and = []
  for(const [k,v] of Object.entries(columnFilters.value)){
    if(!v) continue
    // try smart: number? regex?
    if(!isNaN(v) && v.trim()!==''){ and.push({[k]: Number(v)}) }
    else { and.push({[k]: { $regex: v }}) }
  }
  let base = {}
  try{ base = JSON.parse(filterText.value||'{}') }catch{}
  if(and.length){
    filterText.value = JSON.stringify( and.length===1 ? and[0] : { $and: and } )
  } else {
    filterText.value = JSON.stringify(base && Object.keys(base).length && !and.length ? base : {})
  }
  skip.value=0
  loadDocs()
}
function clearColumnFilter(){ columnFilters.value={}; filterText.value='{}'; loadDocs() }

onMounted(async()=>{
  const r = await api.get('/databases'); databases.value = r.data.data
  // auto try load ?db & ?col from URL
  const url = new URL(window.location.href)
  const qdb = url.searchParams.get('db'); const qcol = url.searchParams.get('col')
  if(qdb){ db.value=qdb; await loadCols(); if(qcol){ collection.value=qcol; await onCollectionChange() } }
})

async function loadCols(){
  if(!db.value) return
  const r = await api.get(`/${db.value}/collections`)
  collections.value = r.data.data
  collection.value = ''
  docs.value = []
  schema.value = {}
}
async function onCollectionChange(){
  await loadSchema()
  columnFilters.value = {}
  sortField.value = '_id'; sortDir.value = -1
  skip.value=0
  await loadDocs()
}
async function loadSchema(){
  if(!db.value || !collection.value){ schema.value={}; return }
  try{
    // enhanced schema IS native now – BangronDB core patched
    const r = await api.get(`/${db.value}/${collection.value}/schema`)
    let s = r.data.schema || {}
    schema.value = s
    useRaw.value = Object.keys(s).length===0
  }catch(e){ schema.value={}; useRaw.value=true }
}

async function loadDocs(){
  if(!db.value || !collection.value) return
  const sort = sortField.value ? { [sortField.value]: sortDir.value } : {}
  let filter = {}
  try{ filter = JSON.parse(filterText.value || '{}') }catch{}
  const params = {
    filter: JSON.stringify(filter),
    sort: JSON.stringify(sort),
    limit: limit.value,
    skip: skip.value,
    with_trashed: withTrashed.value ? 1 : 0,
    only_trashed: onlyTrashed.value ? 1 : 0
  }
  const r = await api.get(`/${db.value}/${collection.value}/documents`, { params })
  docs.value = r.data.data
  total.value = r.data.total
  if(autoPopulate.value && relationFields.value.length){
    await populateRelations()
  }
}

async function populateRelations(){
  for(const col of relationFields.value){
    const rel = col.relation
    if(!rel?.db || !rel?.collection) continue
    const ids = [...new Set(docs.value.map(d=>d[col.field]).filter(Boolean))]
    if(!ids.length) continue
    const cacheKey = `${rel.db}.${rel.collection}`
    if(!relationCache.value[cacheKey]) relationCache.value[cacheKey] = {}
    const missing = ids.filter(id => !(id in relationCache.value[cacheKey]))
    if(missing.length){
      try{
        // query $in
        const res = await api.post(`/${rel.db}/${rel.collection}/query`, {
          filter: { [rel.field || '_id']: { $in: missing } },
          limit: 200
        })
        for(const item of res.data.data || []){
          const id = item[rel.field || '_id']
          const label = item[rel.display] || item.name || item.title || id
          relationCache.value[cacheKey][id] = label
        }
      }catch(e){}
    }
  }
}
function getRelationDisplay(col, id){
  if(!id || !col.relation) return null
  const cacheKey = `${col.relation.db}.${col.relation.collection}`
  return relationCache.value[cacheKey]?.[id] || null
}

function pretty(o){ return JSON.stringify(o,null,2) }
function prettyPopulated(doc){
  const out = {...doc}
  for(const col of relationFields.value){
    const disp = getRelationDisplay(col, doc[col.field])
    if(disp) out[col.field + '__display'] = disp
  }
  return JSON.stringify(out,null,2)
}
function formatCell(v){
  if(v===null || v===undefined) return '–'
  if(typeof v==='object') return Array.isArray(v) ? `[${v.length}]` : '{…}'
  if(typeof v==='boolean') return v ? '✓' : '✗'
  const s = String(v)
  return s.length > 60 ? s.slice(0,57)+'…' : s
}
function badgeClass(col, value){
  const color = col.colorMap?.[value] || 'slate'
  const map = {
    gray:'bg-slate-700 text-slate-200 px-2 py-0.5 rounded-full text-[11px]',
    blue:'bg-blue-900 text-blue-200 px-2 py-0.5 rounded-full text-[11px]',
    green:'bg-emerald-900 text-emerald-200 px-2 py-0.5 rounded-full text-[11px]',
    amber:'bg-amber-900 text-amber-200 px-2 py-0.5 rounded-full text-[11px]',
    red:'bg-red-900 text-red-200 px-2 py-0.5 rounded-full text-[11px]',
    violet:'bg-violet-900 text-violet-200 px-2 py-0.5 rounded-full text-[11px]',
    slate:'bg-slate-800 text-slate-200 px-2 py-0.5 rounded-full text-[11px]',
  }
  return map[color] || map.slate
}

function openInsert(){
  editing.value=null
  formModel.value={}
  // apply defaults from schema
  for(const [f,def] of Object.entries(schema.value)){
    if(def.default !== undefined) formModel.value[f]=def.default
  }
  editorText.value='{}'
  error.value=''; validationMsg.value=''; showInsert.value=true
}
function edit(doc){
  editing.value = doc
  formModel.value = {...doc}
  delete formModel.value._id
  editorText.value = JSON.stringify(doc,null,2)
  error.value=''; validationMsg.value=''; showInsert.value=true
}
function closeModal(){ showInsert.value=false; editing.value=null; error.value=''; validationMsg.value='' }

function getPayload(){
  if(useRaw.value || !Object.keys(schema.value).length){
    try { return JSON.parse(editorText.value) } catch(e){ throw new Error('JSON tidak valid: '+e.message) }
  }
  return {...formModel.value}
}
async function validateServer(payload=null){
  validationMsg.value='validating…'
  try{
    const body = payload || getPayload()
    const r = await api.post(`/${db.value}/${collection.value}/validate`, body)
    validationOk.value = r.data.valid
    validationMsg.value = r.data.valid ? 'Dokumen valid sesuai schema' : (r.data.error||'invalid')
    return r.data.valid
  }catch(e){
    validationOk.value=false
    validationMsg.value='Error: '+(e.response?.data?.error||e.message)
    return false
  }
}
async function saveDoc(){
  error.value=''
  try{
    const payload = getPayload()
    await validateServer(payload)
    if(editing.value && editing.value._id){
      payload._id = editing.value._id
      await api.post(`/${db.value}/${collection.value}/save`, payload)
    } else {
      await api.post(`/${db.value}/${collection.value}/documents`, payload)
    }
    closeModal(); loadDocs()
  }catch(e){ error.value = e.response?.data?.message || e.message }
}
async function removeDoc(doc){
  if(!confirm('Yakin ingin menghapus '+doc._id+' ?')) return
  await api.delete(`/${db.value}/${collection.value}/documents`, { params:{ filter: JSON.stringify({_id:doc._id}) } })
  loadDocs()
}
function nextPage(){ skip.value += limit.value; loadDocs() }
function prevPage(){ skip.value = Math.max(0, skip.value-limit.value); loadDocs() }

watch(formModel, (v)=>{
  if(!useRaw.value) editorText.value = JSON.stringify(v, null, 2)
}, {deep:true})
</script>