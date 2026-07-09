<template>
  <div class="space-y-5 p-6">
    <h2 class="text-xl font-bold">🔑 Permissions Registry</h2>
    <p class="text-sm text-slate-400">Kelola daftar aksi / permission secara dinamis – auth.permissions</p>
    <div class="grid lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-4">
        <div class="flex justify-between mb-3">
          <h3>Permissions ({{ list.length }})</h3>
          <button @click="load" class="text-xs px-2 py-1 border rounded">↻</button>
        </div>
        <div v-for="(items, grp) in grouped" :key="grp" class="mb-3">
          <div class="text-[11px] uppercase text-slate-500">{{ grp }}</div>
          <div class="grid sm:grid-cols-2 gap-2 mt-1">
            <div v-for="p in items" :key="p.name" class="bg-slate-950 border border-slate-800 rounded p-2 text-sm">
              <div class="font-mono text-indigo-300">{{ p.name }}</div>
              <div class="text-xs">{{ p.label }}</div>
              <div class="text-[11px] text-slate-500">{{ p.description || '—' }}</div>
              <button v-if="!p.is_system" @click="remove(p)" class="text-red-400 text-[11px] mt-1">delete</button>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-3">
        <h3 class="font-semibold">New Permission</h3>
        <input v-model="form.name" placeholder="export_invoice" class="w-full bg-slate-950 border border-slate-700 rounded px-3 py-2 text-sm font-mono"/>
        <input v-model="form.label" placeholder="Label" class="w-full bg-slate-950 border border-slate-700 rounded px-3 py-2 text-sm"/>
        <select v-model="form.group" class="w-full bg-slate-950 border border-slate-700 rounded px-3 py-2 text-sm">
          <option>crud</option><option>admin</option><option>data</option><option>workflow</option><option>custom</option>
        </select>
        <textarea v-model="form.description" rows="2" placeholder="Description" class="w-full bg-slate-950 border border-slate-700 rounded px-3 py-2 text-sm"></textarea>
        <button @click="save" class="w-full bg-indigo-600 text-white py-2 rounded">Create</button>
        <pre v-if="msg" class="text-[11px] bg-black p-2 rounded">{{ msg }}</pre>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
const list=ref([]), grouped=ref({}), msg=ref('')
const form=reactive({name:'',label:'',group:'custom',description:''})
async function load(){
  const r=await axios.get('/admin/permissions').catch(()=>({data:{data:[],grouped:{}}}))
  list.value=r.data.data||[]; grouped.value=r.data.grouped||{}
}
async function save(){
  try{
    const r=await axios.post('/admin/permissions', form)
    msg.value='Created ✔'; form.name='';form.label='';form.description=''; load()
  }catch(e){ msg.value=e.response?.data?.message||e.message }
}
async function remove(p){
  if(!confirm('Delete '+p.name+'?')) return
  try{ await axios.delete('/admin/permissions/'+encodeURIComponent(p.name)); load() }catch(e){ alert(e.response?.data?.message||e.message) }
}
onMounted(load)
</script>
