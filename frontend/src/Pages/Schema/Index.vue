<template>
  <div class="space-y-5 animate-fade-in">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-lg bg-cyan-500/10 border border-cyan-500/20 grid place-items-center">
        <Puzzle class="w-4 h-4 text-cyan-400" />
      </div>
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-white">Schema Builder</h1>
        <p class="text-slate-500 text-sm mt-0.5">Design collection schemas with visual builder</p>
      </div>
    </div>
    <!-- top bar -->
    <div class="card">
      <div class="flex flex-wrap gap-3 items-end">
        <div>
          <label class="section-label">Database</label>
          <input v-model="db" placeholder="app" class="input w-36"/>
        </div>
        <div>
          <label class="section-label">Collection</label>
          <input v-model="col" placeholder="users" class="input w-44"/>
        </div>
        <div class="flex gap-2">
          <button class="btn-ghost" @click="loadSchema">Load Schema</button>
          <button class="btn flex items-center gap-2" @click="saveSchema">
            <Save :size="16" />
            Save Schema
          </button>
          <button class="btn-ghost-sm" @click="loadPreset('task')">Load Task Example</button>
        </div>
        <div class="ml-auto flex gap-2 text-xs">
          <button :class="tab==='builder' ? 'btn' : 'btn-ghost'" @click="tab='builder'" class="flex items-center gap-1">
            <Puzzle :size="14" />
            Builder
          </button>
          <button :class="tab==='json' ? 'btn' : 'btn-ghost'" @click="tab='json'" class="flex items-center gap-1">
            <FileJson :size="14" />
            JSON
          </button>
          <button :class="tab==='validate' ? 'btn' : 'btn-ghost'" @click="tab='validate'" class="flex items-center gap-1">
            <Check :size="14" />
            Validate
          </button>
        </div>
      </div>
      <div class="text-xs text-slate-500 mt-2">
        Design your collection schema. Changes are saved directly to BangronDB.
        <span class="text-emerald-300">Schema includes UI metadata for automatic form generation.</span>
      </div>
    </div>

    <!-- Builder -->
    <div v-if="tab==='builder'" class="card">
      <SchemaBuilder v-model="schema" @validate="validateDoc()" />
    </div>

    <!-- JSON -->
    <div v-if="tab==='json'" class="grid lg:grid-cols-2 gap-4">
      <div class="card">
        <div class="flex justify-between items-center mb-2">
          <h3 class="font-semibold">Enhanced Schema JSON</h3>
          <div class="flex gap-2 text-xs">
            <button class="btn-ghost-sm flex items-center gap-1" @click="syncFromBuilder">
              <ArrowLeftRight :size="12" />
              Sync from Builder
            </button>
            <button class="btn-ghost-sm flex items-center gap-1" @click="syncToBuilder">
              <ArrowLeftRight :size="12" />
              Sync to Builder
            </button>
          </div>
        </div>
        <textarea v-model="schemaText" rows="22" class="input font-mono text-xs"></textarea>
        <div class="text-[11px] text-slate-400 mt-2">
          Save via <b>Save Schema</b> — BangronDB validates automatically (enhanced types natively supported after patch).
        </div>
      </div>
      <div class="card">
        <div class="flex items-center gap-2 mb-2">
          <Code2 :size="16" class="text-slate-400" />
          <h3 class="font-semibold">Native validation preview</h3>
        </div>
        <pre class="code-block h-[420px] overflow-auto">{{ nativePreview }}</pre>
        <div class="text-[11px] text-slate-400 mt-2">
          Auto-derived from the enhanced schema.<br/>
          BangronDB core patched: supports relation/date/tags/text/enum types.
        </div>
      </div>
    </div>

    <!-- Validate -->
    <div v-if="tab==='validate'" class="grid lg:grid-cols-2 gap-4">
      <div class="card">
        <h3 class="font-semibold mb-2">Test Document</h3>
        <textarea v-model="docText" rows="14" class="input font-mono text-sm"></textarea>
        <button class="btn w-full mt-2" @click="validate">Validate via API</button>
        <div class="flex gap-2 mt-2 text-xs flex-wrap">
          <button class="btn-ghost-sm" @click="loadPreset('task')">Task Example</button>
          <button class="btn-ghost-sm" @click="loadPreset('user')">User Example</button>
          <button class="btn-ghost-sm" @click="seedExampleDoc">Auto fill defaults</button>
          <button class="btn-ghost-sm" @click="clearSchema">Clear</button>
        </div>
      </div>
      <div class="card">
        <h3 class="font-semibold mb-2">Result</h3>
        <pre class="code-block h-[380px] overflow-auto">{{ result }}</pre>
      </div>
    </div>

    <!-- summary footer -->
    <div class="card text-xs text-slate-400 flex items-center gap-4">
      <span><b class="text-slate-200">{{ Object.keys(schema).length }}</b> fields</span>
      <span class="text-emerald-300">{{ requiredCount }} required</span>
      <span class="text-amber-300">{{ uniqueCount }} unique</span>
      <span class="text-cyan-300">{{ relationCount }} relations</span>
      <span class="text-indigo-300">{{ indexedCount }} indexed</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import SchemaBuilder from '@/Components/SchemaBuilder.vue'
import { Puzzle, FileJson, Check, Save, Code2, ArrowLeftRight } from 'lucide-vue-next'

const db = ref('app')
const col = ref('users')
const tab = ref('builder')

const schema = ref({
  username: { type:'string', label:'Username', required:true, min:3, max:50, filterable:true, sortable:true },
  email: { type:'email', label:'Email', required:true, unique:true, regex:"/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/", searchable:true },
  age: { type:'int', label:'Age', min:13, max:120 },
  role: { type:'enum', label:'Role', options:['admin','user','moderator'], default:'user', filterable:true, ui:{badge:true, color:{admin:'red', moderator:'amber', user:'blue'}} }
})

const schemaText = ref(JSON.stringify(schema.value, null, 2))
const docText = ref(JSON.stringify({ username:'john', email:'john@example.com', age:25, role:'admin' }, null, 2))
const result = ref('{}')

// keep json <-> builder sync
watch(schema, v => { schemaText.value = JSON.stringify(v, null, 2) }, {deep:true})
function syncFromBuilder(){ schemaText.value = JSON.stringify(schema.value, null, 2) }
function syncToBuilder(){
  try{ schema.value = JSON.parse(schemaText.value); tab.value='builder' }
  catch(e){ alert('JSON tidak valid: '+e.message) }
}

const requiredCount = computed(()=> Object.values(schema.value).filter(f=>f?.required).length)
const uniqueCount = computed(()=> Object.values(schema.value).filter(f=>f?.unique).length)
const relationCount = computed(()=> Object.values(schema.value).filter(f=>f?.type==='relation').length)
const indexedCount = computed(()=> Object.values(schema.value).filter(f=>f?.index||f?.sortable||f?.filterable).length)

const nativePreview = computed(()=>{
  const out = {}
  const typeMap = {
    text:'string', email:'string', password:'string', url:'string', slug:'string', tags:'array',
    date:'string', datetime:'string', time:'string', relation:'string', enum:'string',
    int:'int', integer:'int',
    float:'float', double:'float', number:'float', decimal:'float',
    bool:'bool', boolean:'bool', checkbox:'bool', switch:'bool',
    array:'array', object:'object', json:'object'
  }
  for(const [field,def] of Object.entries(schema.value)){
    if(!def || typeof def!=='object') continue
    const nativeType = typeMap[def.type] || def.type || 'string'
    const r = { type: nativeType }
    ;['required','min','max','regex','unique'].forEach(k=>{ if(def[k]!==undefined) r[k]=def[k] })
    const enumVals = def.options || def.enum
    if(enumVals) r.enum = enumVals
    out[field]=r
  }
  return JSON.stringify(out, null, 2)
})

async function loadSchema(){
  try{
    const r = await axios.get(`/databases/${db.value}/collections/${col.value}/schema`)
    const s = r.data.schema || {}
    if(Object.keys(s).length){
      schema.value = s
      result.value = JSON.stringify({source:'bangrondb', schema:s, config:r.data.config}, null, 2)
      tab.value='builder'
      return
    }
    result.value = JSON.stringify({info:'schema kosong', ...r.data}, null, 2)
  }catch(e){
    result.value = JSON.stringify({error:e.response?.data?.message || e.message}, null, 2)
  }
}

async function saveSchema(){
  try{
    const payload = typeof schema.value === 'object' ? schema.value : JSON.parse(schemaText.value)
    const r = await axios.post(`/databases/${db.value}/collections/${col.value}/schema`, { schema: payload })
    result.value = JSON.stringify({saved:true, ...r.data}, null, 2)
    tab.value='validate'
    alert('Schema berhasil disimpan ke BangronDB!')
  }catch(e){
    result.value = JSON.stringify({error:e.response?.data?.message || e.message}, null, 2)
    alert('Gagal save: '+(e.response?.data?.message||e.message))
  }
}

async function validate(){
  try{
    const doc = JSON.parse(docText.value)
    const r = await axios.post(`/databases/${db.value}/collections/${col.value}/schema/validate`, doc)
    result.value = JSON.stringify(r.data, null, 2)
  }catch(e){
    result.value = JSON.stringify({error:e.response?.data?.message || e.message}, null, 2)
  }
}

function validateDoc(){ validate() }

const taskExample = {
  projectId: { type:'relation', label:'Project', required:true, relation:{db:'projects',collection:'projects',field:'_id',display:'name'}, filterable:true },
  title: { type:'string', label:'Task Title', required:true, min:3, searchable:true, sortable:true, index:true, ui:{placeholder:'Enter task title', icon:'list-checks'} },
  description: { type:'text', label:'Description', rows:4 },
  assignee: { type:'string', label:'Assignee Name', readonly:true, sortable:true },
  assigneeId: { type:'relation', label:'Assigned To', relation:{db:'auth',collection:'users',field:'_id',display:'name'}, filterable:true, index:true },
  due: { type:'date', label:'Due Date', sortable:true },
  priority: { type:'enum', label:'Priority', options:['low','medium','high','urgent'], default:'medium', filterable:true, sortable:true, ui:{badge:true, color:{low:'gray',medium:'blue',high:'amber',urgent:'red'}}},
  status: { type:'enum', label:'Status', options:['pending','progress','completed','overdue'], default:'pending', filterable:true, sortable:true, index:true, ui:{badge:true, color:{pending:'gray',progress:'blue',completed:'green',overdue:'red'}}},
  note: { type:'text', label:'Notes', rows:3 },
  parentId: { type:'relation', label:'Parent Task', relation:{db:'projects',collection:'tasks',field:'_id',display:'title'}, hidden:true },
  blockedBy: { type:'array', label:'Blocked By', hidden:true },
  estimatedHours: { type:'float', label:'Estimated Hours', min:0, default:0 },
  actualHours: { type:'float', label:'Actual Hours', min:0, default:0, readonly:true },
  labels: { type:'tags', label:'Labels', filterable:true },
  stage: { type:'string', label:'Stage', filterable:true }
}

async function loadPreset(name){
  if(name==='task'){
    schema.value = JSON.parse(JSON.stringify(taskExample))
    db.value='projects'; col.value='tasks'
    tab.value='builder'
    result.value = JSON.stringify({preset:'task', fields:Object.keys(taskExample).length, note:'Enhanced schema – langsung bisa di-save ke BangronDB'}, null, 2)
    return
  }
  if(name==='user'){
    schema.value = {
      username:{type:'string',label:'Username',required:true,min:3},
      email:{type:'email',label:'Email',required:true,unique:true},
      role:{type:'enum',label:'Role',options:['admin','user'],default:'user',ui:{badge:true}}
    }
    db.value='app'; col.value='users'
    tab.value='builder'
  }
}

function seedExampleDoc(){
  const out = {}
  for(const [f,def] of Object.entries(schema.value)){
    if(def.default !== undefined){ out[f]=def.default; continue }
    out[f] = (
      ['int','integer'].includes(def.type) ? (def.min||0) :
      ['float','double','number','decimal'].includes(def.type) ? 0 :
      ['bool','boolean','checkbox','switch'].includes(def.type) ? false :
      ['array','tags'].includes(def.type) ? [] :
      ['object','json'].includes(def.type) ? {} :
      (def.options?.[0] || def.enum?.[0] || 'sample')
    )
    if(def.type==='email') out[f]='test@example.com'
    if(f==='username') out[f]='john'
    if(f==='title') out[f]='Sample Task'
    if(f==='name') out[f]='Sample'
  }
  docText.value = JSON.stringify(out, null, 2)
}

function clearSchema(){
  if(confirm('Clear semua field?')){ schema.value = {}; }
}

// initial sync
syncFromBuilder()
</script>