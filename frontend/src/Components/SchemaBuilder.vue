<template>
  <div class="space-y-4">
    <div class="flex justify-between items-center">
      <div class="text-sm text-slate-300">Fields: <b>{{ fieldList.length }}</b> •
        <span class="text-slate-400">types supported: {{ allTypes.join(', ') }}</span>
      </div>
      <div class="flex gap-2 items-center">
        <input v-model="newFieldName" @keyup.enter="addField" placeholder="new_field_name" class="input-sm w-48"/>
        <select v-model="newFieldType" class="input-sm">
          <option v-for="t in allTypes" :key="t" :value="t">{{ t }}</option>
        </select>
        <button class="btn-sm flex items-center gap-1" @click="addField"><Plus :size="14" /> Add Field</button>
        <button class="btn-ghost-sm flex items-center gap-1" @click="$emit('import')"><Code2 :size="14" /> Import JSON</button>
        <button class="btn-ghost-sm flex items-center gap-1" @click="copyJson"><Copy :size="14" /> Copy</button>
      </div>
    </div>

    <div class="grid gap-3">
      <div v-for="(f, idx) in fieldList" :key="f.key"
           class="bg-slate-950/70 border border-slate-800 rounded-xl overflow-hidden">
        <!-- header -->
        <div class="flex items-center justify-between px-3 py-2 bg-slate-900/60 cursor-pointer"
             @click="toggleOpen(f.key)">
          <div class="flex items-center gap-3">
            <span class="text-slate-500 text-xs">#{{ idx+1 }}</span>
            <span class="font-mono text-indigo-300">{{ f.key }}</span>
            <span class="badge">{{ f.def.type || 'string' }}</span>
            <span v-if="f.def.label" class="text-sm text-slate-200">— {{ f.def.label }}</span>
            <span v-if="f.def.required" class="text-[10px] text-red-300">required</span>
            <span v-if="f.def.unique" class="text-[10px] text-amber-300">unique</span>
            <span v-if="f.def.relation" class="text-[10px] text-cyan-300">relation</span>
            <span v-if="f.def.hidden" class="text-[10px] text-slate-500">hidden</span>
          </div>
          <div class="flex items-center gap-1 text-xs">
            <button @click.stop="moveUp(idx)" class="btn-ghost-sm !p-1"><ArrowUp :size="14" /></button>
            <button @click.stop="moveDown(idx)" class="btn-ghost-sm !p-1"><ArrowDown :size="14" /></button>
            <button @click.stop="duplicateField(f)" class="btn-ghost-sm !p-1"><Copy :size="14" /></button>
            <button @click.stop="removeField(f.key)" class="btn-ghost-sm !p-1 text-red-400"><Trash2 :size="14" /></button>
            <span class="text-slate-500">
              <ChevronDown v-if="open[f.key]" :size="14" />
              <ChevronRight v-else :size="14" />
            </span>
          </div>
        </div>

        <!-- body -->
        <div v-show="open[f.key]" class="p-4 grid md:grid-cols-3 gap-3 text-sm">
          <!-- left: core -->
          <div class="space-y-2 md:col-span-1">
            <div>
              <label class="text-[11px] text-slate-400">Field name</label>
              <input :value="f.key" @change="renameField(f.key, $event.target.value)"
                     class="input text-sm font-mono"/>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[11px] text-slate-400">Type</label>
                <select v-model="modelValue[f.key].type" class="input text-sm">
                  <option v-for="t in allTypes" :key="t" :value="t">{{ t }}</option>
                </select>
              </div>
              <div>
                <label class="text-[11px] text-slate-400">Label</label>
                <input v-model="modelValue[f.key].label" class="input text-sm" :placeholder="f.key"/>
              </div>
            </div>
            <div class="flex flex-wrap gap-3 text-xs pt-1">
              <label class="flex items-center gap-1"><input type="checkbox" v-model="modelValue[f.key].required"/> required</label>
              <label class="flex items-center gap-1"><input type="checkbox" v-model="modelValue[f.key].unique"/> unique</label>
              <label class="flex items-center gap-1"><input type="checkbox" v-model="modelValue[f.key].readonly"/> readonly</label>
              <label class="flex items-center gap-1"><input type="checkbox" v-model="modelValue[f.key].hidden"/> hidden</label>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <div><label class="text-[11px] text-slate-400">min</label>
                <input type="number" v-model.number="modelValue[f.key].min" class="input text-sm"/></div>
              <div><label class="text-[11px] text-slate-400">max</label>
                <input type="number" v-model.number="modelValue[f.key].max" class="input text-sm"/></div>
              <div><label class="text-[11px] text-slate-400">default</label>
                <input v-model="defaultInputs[f.key]" @change="applyDefault(f.key)" class="input text-sm" placeholder="auto"/></div>
            </div>
            <div>
              <label class="text-[11px] text-slate-400">regex</label>
              <input v-model="modelValue[f.key].regex" class="input text-sm font-mono" placeholder="/^...$/"/>
            </div>
          </div>

          <!-- middle: UI & table -->
          <div class="space-y-2">
            <div class="text-[11px] text-slate-400 font-semibold">UI / Table</div>
            <div class="grid grid-cols-2 gap-2">
              <input v-model="modelValue[f.key].placeholder" @input="ensureUi(f.key)" placeholder="placeholder" class="input text-sm"/>
              <input v-model="modelValue[f.key].icon" @input="ensureUi(f.key)" placeholder="icon name" class="input text-sm"/>
            </div>
            <div v-if="isTextLike(f.def)" >
              <label class="text-[11px] text-slate-400">rows</label>
              <input type="number" v-model.number="modelValue[f.key].rows" min="1" max="20" class="input text-sm w-24"/>
            </div>
            <div class="flex flex-wrap gap-3 text-xs">
              <label><input type="checkbox" v-model="modelValue[f.key].filterable"/> filterable</label>
              <label><input type="checkbox" v-model="modelValue[f.key].sortable"/> sortable</label>
              <label><input type="checkbox" v-model="modelValue[f.key].index"/> index</label>
              <label><input type="checkbox" v-model="modelValue[f.key].searchable"/> searchable</label>
            </div>

            <!-- enum options -->
            <div v-if="isEnumType(f.def)">
              <label class="text-[11px] text-slate-400">Enum options (comma separated)</label>
              <input :value="(modelValue[f.key].options||modelValue[f.key].enum||[]).join(', ')"
                     @change="setEnumOptions(f.key, $event.target.value)"
                     class="input text-sm" placeholder="low, medium, high"/>
              <label class="flex items-center gap-2 text-xs mt-2"><input type="checkbox"
                :checked="!!modelValue[f.key].ui?.badge"
                @change="toggleBadge(f.key,$event.target.checked)"/> badge UI</label>
              <div v-if="modelValue[f.key].ui?.badge" class="mt-2 space-y-1">
                <div class="text-[11px] text-slate-400">Badge colors per option:</div>
                <div v-for="opt in enumOpts(f.def)" :key="opt" class="flex items-center gap-2">
                  <span class="w-20 text-xs truncate">{{ opt }}</span>
                  <select :value="getBadgeColor(f.key,opt)"
                          @change="setBadgeColor(f.key,opt,$event.target.value)"
                          class="input-sm">
                    <option v-for="c in badgeColors" :key="c" :value="c">{{ c }}</option>
                  </select>
                  <span :class="previewBadge(getBadgeColor(f.key,opt))">{{ opt }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- right: relation -->
          <div class="space-y-2">
            <div class="text-[11px] text-slate-400 font-semibold">Relation / Advanced</div>

            <div v-if="f.def.type==='relation'">
              <div class="grid grid-cols-2 gap-2">
                <input v-model="modelValue[f.key].relation.db" placeholder="db" class="input text-sm"/>
                <input v-model="modelValue[f.key].relation.collection" placeholder="collection" class="input text-sm"/>
                <input v-model="modelValue[f.key].relation.field" placeholder="field (default _id)" class="input text-sm"/>
                <input v-model="modelValue[f.key].relation.display" placeholder="display field" class="input text-sm"/>
              </div>
              <div class="text-[10px] text-slate-500 mt-1">Example: db=auth, collection=users, field=_id, display=name</div>
            </div>
            <div v-else class="text-[11px] text-slate-500">
              Set type to <b>relation</b> to enable join configuration.<br/>
              Supported types:
              <div class="mt-1 text-[10px] leading-relaxed">
                string, text, email, password, url, slug,<br/>
                int/integer, float/double/number,<br/>
                bool/boolean/checkbox/switch,<br/>
                array, object/json,<br/>
                enum, date, datetime, time,<br/>
                relation, tags
              </div>
            </div>

            <details class="text-xs">
              <summary class="cursor-pointer text-slate-400">Raw field JSON</summary>
              <pre class="code-block mt-1 max-h-40">{{ JSON.stringify(modelValue[f.key], null, 2) }}</pre>
            </details>
          </div>
        </div>
      </div>

      <div v-if="!fieldList.length" class="empty-state">
        No fields yet — add your first field above, or import a JSON schema.
      </div>
    </div>

    <!-- footer summary -->
    <div class="flex justify-between items-center text-xs text-slate-400">
      <div>
        <span class="mr-3">Total fields: {{ fieldList.length }}</span>
        <span class="mr-3">required: {{ requiredCount }}</span>
        <span class="mr-3">unique: {{ uniqueCount }}</span>
        <span class="mr-3">relations: {{ relationCount }}</span>
        <span>indexed: {{ indexedCount }}</span>
      </div>
      <div class="flex gap-2">
        <button class="btn-ghost-sm" @click="sortAlpha">Sort A-Z</button>
        <button class="btn-ghost-sm" @click="$emit('validate')">Validate Schema</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { Plus, ArrowUp, ArrowDown, Copy, Trash2, ChevronDown, ChevronRight, Code2 } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: Object, default: ()=>({}) }
})
const emit = defineEmits(['update:modelValue','validate','import'])

const allTypes = [
  'string','text','email','password','url','slug',
  'int','integer','float','double','number','decimal',
  'bool','boolean','checkbox','switch',
  'array','object','json',
  'enum',
  'date','datetime','time',
  'relation',
  'tags'
]

const open = reactive({})
const newFieldName = ref('')
const newFieldType = ref('string')
const defaultInputs = ref({})

const fieldList = computed(()=>{
  return Object.entries(props.modelValue || {}).map(([key,def])=>({key, def}))
})

const requiredCount = computed(()=> fieldList.value.filter(f=>f.def.required).length)
const uniqueCount = computed(()=> fieldList.value.filter(f=>f.def.unique).length)
const relationCount = computed(()=> fieldList.value.filter(f=>f.def.type==='relation').length)
const indexedCount = computed(()=> fieldList.value.filter(f=>f.def.index||f.def.sortable||f.def.filterable).length)

const badgeColors = ['slate','gray','blue','green','amber','red','violet']

function toggleOpen(k){ open[k] = !open[k] }

function addField(){
  let name = (newFieldName.value || '').trim()
    .replace(/\s+/g,'_')
    .replace(/[^a-zA-Z0-9_]/g,'')
  if(!name){ alert('Nama field wajib'); return }
  if(props.modelValue[name]){ alert('Field sudah ada'); return }
  const next = {...props.modelValue}
  next[name] = {
    type: newFieldType.value,
    label: toLabel(name),
  }
  // sensible defaults per type
  if(['enum'].includes(newFieldType.value)){
    next[name].options = ['option1','option2']
  }
  if(newFieldType.value==='relation'){
    next[name].relation = { db:'app', collection:'users', field:'_id', display:'name' }
  }
  if(['text'].includes(newFieldType.value)){
    next[name].rows = 4
  }
  emit('update:modelValue', next)
  open[name]=true
  newFieldName.value=''
}
function removeField(key){
  if(!confirm('Hapus field "'+key+'"?')) return
  const n = {...props.modelValue}; delete n[key]; emit('update:modelValue', n)
}
function renameField(oldKey, newKey){
  newKey = (newKey||'').trim().replace(/\s+/g,'_')
  if(!newKey || newKey===oldKey) return
  if(props.modelValue[newKey]){ alert('Nama sudah dipakai'); return }
  const n = {}
  for(const [k,v] of Object.entries(props.modelValue)){
    n[k===oldKey ? newKey : k] = v
  }
  emit('update:modelValue', n)
  open[newKey]=open[oldKey]; delete open[oldKey]
}
function moveUp(idx){
  const keys = Object.keys(props.modelValue)
  if(idx<=0) return
  const a = keys[idx-1], b = keys[idx]
  const n = {}
  keys.forEach((k,i)=>{
    if(i===idx-1) n[b]=props.modelValue[b]
    else if(i===idx) n[a]=props.modelValue[a]
    else n[k]=props.modelValue[k]
  })
  emit('update:modelValue', n)
}
function moveDown(idx){
  const keys = Object.keys(props.modelValue)
  if(idx>=keys.length-1) return
  moveUp(idx+1)
}
function duplicateField(f){
  let base = f.key + '_copy'
  let n = base, i=2
  while(props.modelValue[n]){ n = base + i++; }
  const next = {...props.modelValue, [n]: JSON.parse(JSON.stringify(f.def))}
  emit('update:modelValue', next)
  open[n]=true
}
function sortAlpha(){
  const sorted = Object.keys(props.modelValue).sort()
  const n = {}; sorted.forEach(k=> n[k]=props.modelValue[k])
  emit('update:modelValue', n)
}

function isEnumType(def){ return def.type==='enum' || def.enum || def.options }
function isTextLike(def){ return ['text','textarea'].includes(def.type) || def.rows }
function enumOpts(def){ return def.options || def.enum || [] }
function toLabel(s){ return s.replace(/_/g,' ').replace(/\b\w/g, m=>m.toUpperCase()) }

function setEnumOptions(key, str){
  const arr = str.split(',').map(s=>s.trim()).filter(Boolean)
  const m = {...props.modelValue}
  m[key] = {...m[key], options: arr}
  delete m[key].enum
  emit('update:modelValue', m)
}
function toggleBadge(key, on){
  const m = {...props.modelValue}
  m[key] = {...m[key], ui: {...(m[key].ui||{}), badge: on}}
  if(on && !m[key].ui.color) m[key].ui.color = {}
  emit('update:modelValue', m)
}
function getBadgeColor(key, opt){
  return props.modelValue[key]?.ui?.color?.[opt] || 'slate'
}
function setBadgeColor(key, opt, color){
  const m = {...props.modelValue}
  const ui = {...(m[key].ui||{}), badge:true, color:{...(m[key].ui?.color||{}), [opt]:color}}
  m[key] = {...m[key], ui}
  emit('update:modelValue', m)
}
function previewBadge(color){
  const map = {
    gray:'bg-slate-700 text-slate-200 px-2 py-0.5 rounded-full text-[10px]',
    blue:'bg-blue-900 text-blue-200 px-2 py-0.5 rounded-full text-[10px]',
    green:'bg-emerald-900 text-emerald-200 px-2 py-0.5 rounded-full text-[10px]',
    amber:'bg-amber-900 text-amber-200 px-2 py-0.5 rounded-full text-[10px]',
    red:'bg-red-900 text-red-200 px-2 py-0.5 rounded-full text-[10px]',
    violet:'bg-violet-900 text-violet-200 px-2 py-0.5 rounded-full text-[10px]',
    slate:'bg-slate-800 text-slate-200 px-2 py-0.5 rounded-full text-[10px]',
  }
  return map[color] || map.slate
}
function ensureUi(key){
  const m = {...props.modelValue}
  if(!m[key].ui) m[key].ui = {}
  emit('update:modelValue', m)
}
function applyDefault(key){
  const v = defaultInputs.value[key]
  if(v===undefined || v==='') return
  const def = props.modelValue[key]
  let parsed = v
  if(['int','integer'].includes(def.type)) parsed = parseInt(v,10)
  else if(['float','double','number','decimal'].includes(def.type)) parsed = parseFloat(v)
  else if(['bool','boolean','checkbox','switch'].includes(def.type)) parsed = ['true','1','yes','on'].includes(String(v).toLowerCase())
  else if(['array','object','json'].includes(def.type)){ try{ parsed = JSON.parse(v) }catch{} }
  const m = {...props.modelValue}
  m[key] = {...m[key], default: parsed}
  emit('update:modelValue', m)
}
function copyJson(){
  navigator.clipboard.writeText(JSON.stringify(props.modelValue, null, 2))
    .then(()=>alert('SSOT JSON copied!'))
    .catch(()=>{})
}
</script>