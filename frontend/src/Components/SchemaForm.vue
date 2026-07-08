<template>
  <div class="space-y-3">
    <div v-if="!schema || Object.keys(visibleFields).length===0" class="text-slate-400 text-sm">
      Tidak ada schema aktif – fallback ke JSON editor.
      <textarea v-model="jsonFallback" rows="10" class="input font-mono text-sm mt-2 w-full"></textarea>
    </div>
    <div v-else class="grid gap-4">
      <div v-for="(rules, field) in visibleFields" :key="field" :class="fieldClass(rules)">
        <label class="text-xs text-slate-400 flex justify-between mb-1">
          <span>
            {{ rules.label || field }}
            <span v-if="rules.required" class="text-red-400">*</span>
            <span class="text-slate-500"> • {{ rules.type }}</span>
          </span>
          <span class="flex gap-2">
            <span v-if="rules.unique" class="badge-warning">UNIQUE</span>
            <span v-if="rules.readonly" class="badge">readonly</span>
            <span v-if="rules.relation" class="badge-info flex items-center gap-1"><Link :size="10"/> relation</span>
          </span>
        </label>

        <!-- RELATION -->
        <div v-if="isRelation(rules)" class="flex gap-2">
          <select v-model="model[field]" class="input flex-1" :disabled="rules.readonly">
            <option value="">{{ rules.label || field }}…</option>
            <option v-for="opt in relationOptions[field] || []" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
          <button type="button" class="btn-ghost-sm" @click="loadRelation(field, rules)" title="Reload relation"><RefreshCw :size="14"/></button>
        </div>

        <!-- ENUM with badge colors -->
        <div v-else-if="isEnum(rules)">
          <select v-model="model[field]" class="input" :disabled="rules.readonly">
            <option value="">-- pilih --</option>
            <option v-for="opt in enumOptions(rules)" :key="opt" :value="opt">{{ opt }}</option>
          </select>
          <div v-if="rules.ui?.badge && model[field]" class="mt-1">
            <span :class="badgeClass(rules, model[field])">{{ model[field] }}</span>
          </div>
        </div>

        <!-- TEXTAREA -->
        <textarea v-else-if="isTextarea(rules)" v-model="model[field]" :rows="rules.rows || 3"
          :placeholder="rules.ui?.placeholder || rules.label || field"
          :readonly="rules.readonly"
          class="input"></textarea>

        <!-- DATE -->
        <input v-else-if="isDate(rules)" v-model="model[field]" type="date" class="input" :readonly="rules.readonly"/>

        <!-- DATETIME -->
        <input v-else-if="isDateTime(rules)" v-model="model[field]" type="datetime-local" class="input" :readonly="rules.readonly"/>

        <!-- TAGS -->
        <div v-else-if="isTags(rules)" class="space-y-2">
          <div class="flex flex-wrap gap-1">
            <span v-for="(t,i) in (model[field]||[])" :key="i"
              class="px-2 py-1 bg-slate-800 rounded-full text-xs flex items-center gap-1">
              {{ t }} <button type="button" @click="removeTag(field,i)" class="text-slate-400 hover:text-white"><X :size="12"/></button>
            </span>
          </div>
          <div class="flex gap-2">
            <input v-model="tagInputs[field]" @keydown.enter.prevent="addTag(field)" class="input flex-1" placeholder="add tag + Enter"/>
            <button type="button" class="btn-ghost-sm" @click="addTag(field)"><Plus :size="14"/></button>
          </div>
        </div>

        <!-- STRING -->
        <input v-else-if="isString(rules)" v-model="model[field]"
          :type="guessInputType(field,rules)"
          :placeholder="rules.ui?.placeholder || ''"
          :readonly="rules.readonly"
          :minlength="rules.min" :maxlength="rules.max"
          class="input"/>

        <!-- INT -->
        <input v-else-if="isInt(rules)" v-model.number="model[field]" type="number" step="1"
          :min="rules.min" :max="rules.max" :readonly="rules.readonly" class="input"/>

        <!-- FLOAT -->
        <input v-else-if="isFloat(rules)" v-model.number="model[field]" type="number" step="any"
          :min="rules.min" :max="rules.max" :readonly="rules.readonly" class="input"/>

        <!-- BOOL -->
        <label v-else-if="isBool(rules)" class="flex items-center gap-2 py-1">
          <input type="checkbox" v-model="model[field]" :disabled="rules.readonly"/>
          <span class="text-sm">{{ rules.label || field }}</span>
        </label>

        <!-- ARRAY -->
        <div v-else-if="isArray(rules)">
          <div class="flex gap-2 mb-1" v-for="(v,i) in (model[field]||[])" :key="i">
            <input v-model="model[field][i]" class="input flex-1"/>
            <button type="button" class="btn-ghost-sm" @click="model[field].splice(i,1)"><X :size="14"/></button>
          </div>
          <button type="button" class="btn-ghost-sm" @click="addArrayItem(field)"><Plus :size="14"/> item</button>
        </div>

        <!-- OBJECT / JSON -->
        <textarea v-else-if="isObject(rules)" v-model="objectStrings[field]" @input="parseObject(field)"
          rows="4" class="input font-mono text-xs"></textarea>

        <!-- fallback -->
        <input v-else v-model="model[field]" class="input" :readonly="rules.readonly"/>

        <div class="text-[10px] text-slate-500 mt-1 flex justify-between">
          <span v-if="rules.regex">regex: <code>{{ rules.regex }}</code></span>
          <span v-if="rules.filterable" class="text-cyan-400">filterable</span>
          <span v-if="rules.sortable" class="text-violet-300">sortable</span>
          <span v-if="rules.index" class="text-emerald-300">indexed</span>
          <span v-if="rules.searchable" class="text-amber-300">searchable</span>
        </div>
        <div v-if="errors[field]" class="flex items-center gap-1 text-red-400 text-xs mt-1"><AlertTriangle :size="13"/> {{ errors[field] }}</div>
      </div>
    </div>

    <div class="flex gap-2 pt-2 items-center text-xs">
      <button type="button" class="btn-ghost-sm" @click="$emit('validate', getPayload())"><Check :size="14"/> Validate via API</button>
      <span class="text-slate-500">SSOT types: string • text • int • float • bool • array • object • enum • date • datetime • relation • tags</span>
    </div>
    <div v-if="apiError" class="text-red-400 text-sm bg-red-950/30 p-2 rounded-xl border border-red-900">{{ apiError }}</div>
  </div>
</template>

<script setup>
import { reactive, ref, watch, computed } from 'vue'
import axios from 'axios'
import { RefreshCw, X, Plus, AlertTriangle, Check, Link } from 'lucide-vue-next'

const props = defineProps({
  schema: { type: Object, default: ()=>({}) },
  modelValue: { type: Object, default: ()=>({}) },
  apiError: { type: String, default: '' }
})
const emit = defineEmits(['update:modelValue','validate'])
const model = reactive({ ...props.modelValue })
const errors = reactive({})
const objectStrings = reactive({})
const jsonFallback = ref(JSON.stringify(props.modelValue, null, 2))
const tagInputs = reactive({})
const relationOptions = reactive({})

const visibleFields = computed(()=>{
  const out = {}
  for(const [k,v] of Object.entries(props.schema||{})){
    if(v.hidden) continue
    out[k]=v
  }
  return out
})

watch(()=>props.modelValue, v=> Object.assign(model, v), {deep:true})
watch(model, ()=> emit('update:modelValue', getPayload()), {deep:true})

function getPayload(){
  if(!props.schema || !Object.keys(props.schema).length){
    try { return JSON.parse(jsonFallback.value) } catch { return model }
  }
  return {...model}
}

// type helpers – support SSOT enhanced types
const isRelation = r => r.type==='relation'
const isEnum = r => r.type==='enum' || r.enum || r.options
const isTextarea = r => r.type==='text' || (r.rows && r.type==='string')
const isDate = r => r.type==='date'
const isDateTime = r => r.type==='datetime'
const isTags = r => r.type==='tags'
const isString = r => ['string','email','password','url','slug'].includes(r.type) && !isEnum(r)
const isInt = r => r.type==='int' || r.type==='integer'
const isFloat = r => ['float','double','number'].includes(r.type)
const isBool = r => ['bool','boolean','checkbox'].includes(r.type)
const isArray = r => r.type==='array' && !isTags(r)
const isObject = r => ['object','json'].includes(r.type)

function enumOptions(rules){
  return rules.options || rules.enum || []
}
function guessInputType(field, rules){
  if(rules.type==='email' || field.includes('email')) return 'email'
  if(rules.type==='password') return 'password'
  if(rules.type==='url') return 'url'
  return 'text'
}
function fieldClass(rules){
  return (rules.type==='text' || rules.rows>2) ? 'md:col-span-2' : ''
}
function badgeClass(rules, value){
  const color = rules.ui?.color?.[value] || 'slate'
  const map = {
    gray:'bg-slate-700 text-slate-200',
    blue:'bg-blue-900 text-blue-200',
    green:'bg-emerald-900 text-emerald-200',
    amber:'bg-amber-900 text-amber-200',
    red:'bg-red-900 text-red-200',
    violet:'bg-violet-900 text-violet-200',
    slate:'bg-slate-800 text-slate-200'
  }
  return 'text-[10px] px-2 py-1 rounded-full ' + (map[color]||map.slate)
}

// tags
function addTag(field){
  const v = (tagInputs[field]||'').trim()
  if(!v) return
  if(!Array.isArray(model[field])) model[field]=[]
  model[field].push(v)
  tagInputs[field]=''
}
function removeTag(field,i){ model[field]?.splice(i,1) }

// array
function addArrayItem(field){
  if(!Array.isArray(model[field])) model[field]=[]
  model[field].push('')
}

// object
function parseObject(field){
  try { model[field] = JSON.parse(objectStrings[field]||'{}'); errors[field]='' }
  catch(e){ errors[field]='JSON tidak valid' }
}

// relation loader
async function loadRelation(field, rules){
  const rel = rules.relation
  if(!rel) return
  try{
    const res = await axios.get(`/api/${rel.db}/${rel.collection}/documents`, { params:{ limit: 100 }})
    relationOptions[field] = (res.data.data||[]).map(d=>({
      value: d[rel.field || '_id'],
      label: d[rel.display] || d.name || d.title || d[rel.field || '_id']
    }))
  }catch(e){
    relationOptions[field] = []
  }
}

// init defaults
watch(()=>props.schema, async (s)=>{
  if(!s) return
  for(const [f,rules] of Object.entries(s)){
    if(model[f]!==undefined) continue
    if(rules.default !== undefined) model[f]=rules.default
    else if(isBool(rules)) model[f]=false
    else if(isArray(rules) || isTags(rules)) model[f]=[]
    else if(isObject(rules)) { model[f]={}; objectStrings[f]='{}' }
    else if(isInt(rules)||isFloat(rules)) model[f]= rules.min ?? rules.default ?? 0
    else model[f]=''
    if(isObject(rules) && model[f]) objectStrings[f]=JSON.stringify(model[f],null,2)
    // auto load relation options
    if(isRelation(rules)) loadRelation(f, rules)
  }
}, {immediate:true})

</script>
