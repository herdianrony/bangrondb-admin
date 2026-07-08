<template>
  <div class="space-y-4">
    <div class="card">
      <div class="flex items-center gap-2">
        <Shield :size="20" class="text-indigo-400" />
        <h2 class="text-xl font-bold">Roles</h2>
      </div>
      <p class="text-sm text-slate-400">Define roles and their permissions</p>
    </div>
    <div class="grid lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 card">
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr><th class="text-left">Role</th><th>Label</th><th>Permissions</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="r in roles" :key="r.name">
                <td class="font-mono">
                  {{ r.name }}
                  <span v-if="r.is_system" class="badge badge-warning ml-1">system</span>
                </td>
                <td>{{ r.label }}</td>
                <td class="text-xs">{{ (r.permissions||[]).join(', ') }}</td>
                <td class="text-right">
                  <button class="btn-ghost-sm" @click="edit(r)" title="Edit"><Pencil :size="14" /></button>
                  <button v-if="!r.is_system" class="btn-ghost-sm text-red-400" @click="remove(r)" title="Delete"><Trash2 :size="14" /></button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card space-y-3">
        <div class="flex items-center gap-2">
          <Plus v-if="!form._id" :size="16" class="text-indigo-400" />
          <Save v-else :size="16" class="text-indigo-400" />
          <h3 class="font-semibold">{{ form._id ? 'Edit' : 'New' }} Role</h3>
        </div>
        <input v-model="form.name" :disabled="!!form._id" placeholder="Role Name" class="input"/>
        <input v-model="form.label" placeholder="Label" class="input"/>
        <input v-model="form.description" placeholder="Description" class="input"/>
        <div class="section-label">Permissions</div>
        <div class="grid grid-cols-2 gap-1 text-sm">
          <label v-for="p in perms" :key="p"><input type="checkbox" :value="p" v-model="form.permissions"> {{ p }}</label>
        </div>
        <button class="btn w-full" @click="save">{{ form._id ? 'Update' : 'Create' }}</button>
        <button v-if="form._id" class="btn-ghost w-full" @click="reset">
          <X :size="14" class="inline mr-1" /> Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { Shield, Pencil, Trash2, Plus, Save, X } from 'lucide-vue-next'
const roles = ref([])
const perms = ['*','read','create','update','delete','manage_schema','manage_acl','find','count']
const form = reactive({ _id:'', name:'', label:'', description:'', permissions:['read'] })
async function load(){ const r = await axios.get('/admin/roles'); roles.value = r.data.data||[] }
function edit(r){ Object.assign(form, {_id:r.name, name:r.name, label:r.label||'', description:r.description||'', permissions:r.permissions||[] }) }
function reset(){ Object.assign(form, {_id:'', name:'', label:'', description:'', permissions:['read'] }) }
async function save(){
  if(form._id){
    await axios.put(`/admin/roles/${form.name}`, form); 
  }else{
    await axios.post('/admin/roles', form)
  }
  reset(); load()
}
async function remove(r){ if(confirm('Hapus role '+r.name+'?')){ await axios.delete(`/admin/roles/${r.name}`); load() } }
onMounted(load)
</script>