<template>
  <div class="space-y-5">
    <UiPageHeader
      title="Roles"
      description="Kelola peran di auth.roles – permissions = relation array → auth.permissions"
      :icon="ShieldIcon"
    >
      <template #badge>
        <UiBadge color="indigo">{{ roles.length }} roles</UiBadge>
      </template>
      <template #actions>
        <UiButton size="sm" variant="ghost" @click="load" :loading="loading">Reload</UiButton>
        <a href="/permissions"><UiButton size="sm" variant="secondary">🔑 Permissions</UiButton></a>
      </template>
      <template #meta>
        RBAC: <code>user.role → roles.permissions[] → resource → action</code> • permissions dinamis dari <code>auth.permissions</code>
      </template>
    </UiPageHeader>

    <div class="grid lg:grid-cols-3 gap-4">
      <!-- table -->
      <UiCard class="lg:col-span-2" :padding="false">
        <div class="p-4 border-b border-white/[0.06] text-[12px] font-[600] text-slate-300">
          Roles registry – auth.roles
        </div>
        <UiTable
          :columns="[
            {key:'name', label:'Role', mono:true},
            {key:'label', label:'Label'},
            {key:'permissions', label:'Permissions'},
          ]"
          :items="roles"
          :loading="loading"
        >
          <template #cell-name="{row}">
            <div class="flex items-center gap-2">
              <span class="font-mono text-indigo-300">{{ row.name }}</span>
              <UiBadge v-if="row.is_system" color="amber" size="sm">system</UiBadge>
            </div>
            <div class="text-[10px] text-slate-500">{{ row._id || '' }}</div>
          </template>
          <template #cell-permissions="{row}">
            <div class="flex flex-wrap gap-1 max-w-[420px]">
              <UiBadge v-for="p in (row.permissions||[])" :key="p" size="sm"
                :color="p==='*' ? 'red' : p.startsWith('manage') ? 'violet' : p==='read' ? 'emerald' : 'slate'">
                {{ p }}
              </UiBadge>
              <span v-if="!row.permissions?.length" class="text-slate-600 text-[11px]">—</span>
            </div>
          </template>
          <template #actions="{row}">
            <div class="flex gap-1">
              <UiButton size="sm" variant="subtle" @click="edit(row)"><Pencil :size="13"/></UiButton>
              <UiButton v-if="!row.is_system" size="sm" variant="ghost" @click="remove(row)"><Trash2 :size="13" class="text-red-400"/></UiButton>
            </div>
          </template>
          <template #empty>
            <UiEmptyState :icon="ShieldIcon" title="Belum ada role"
              description="Seed default: superadmin, admin, editor, user, guest" />
          </template>
        </UiTable>
      </UiCard>

      <!-- form -->
      <UiCard>
        <h3 class="font-[600] text-slate-100 mb-3 flex items-center gap-2">
          <component :is="form._id ? Save : Plus" :size="15" class="text-indigo-400"/>
          {{ form._id ? 'Edit Role' : 'New Role' }}
        </h3>
        <div class="space-y-3">
          <UiInput v-model="form.name" :disabled="!!form._id" label="Role key *" placeholder="moderator"
            hint="a-z0-9_ – akan jadi _id di auth.roles" :required="!form._id" />
          <UiInput v-model="form.label" label="Label" placeholder="Moderator" />
          <UiTextarea v-model="form.description" label="Description" :rows="2" placeholder="Apa yang boleh role ini lakukan?" />

          <div>
            <div class="flex items-center justify-between text-[11px] uppercase tracking-wider text-slate-400 mb-2">
              <span>Permissions</span>
              <a href="/permissions" class="text-indigo-400 normal-case tracking-normal hover:underline">manage →</a>
            </div>
            <div class="max-h-[360px] overflow-auto bg-[#0f131c] border border-white/[0.08] rounded-xl p-3 space-y-3">
              <div v-if="Object.keys(permsGrouped).length">
                <div v-for="(items, grp) in permsGrouped" :key="grp" class="mb-3">
                  <div class="text-[10px] uppercase text-slate-500 mb-1.5">{{ grp }}</div>
                  <div class="space-y-1">
                    <label v-for="p in items" :key="p.name"
                      class="flex items-start gap-2 px-2 py-1.5 rounded-lg hover:bg-white/[0.03] cursor-pointer">
                      <input type="checkbox" :value="p.name" v-model="form.permissions"
                        class="mt-0.5 rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500/30">
                      <div class="min-w-0 flex-1">
                        <div class="text-[12px] font-mono text-slate-200">{{ p.name }}
                          <UiBadge v-if="p.is_system" color="amber" size="sm" class="ml-1">sys</UiBadge>
                        </div>
                        <div class="text-[11px] text-slate-400">{{ p.label }}</div>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
              <div v-else class="text-slate-500 text-xs">Loading permissions…</div>
            </div>
            <div class="text-[10px] text-slate-500 mt-1.5">
              {{ form.permissions.length }} selected • 
              source: <code>GET /admin/permissions</code>
            </div>
          </div>

          <div class="flex gap-2">
            <UiButton :block="true" @click="save" :loading="saving">
              {{ form._id ? 'Update role' : 'Create role' }}
            </UiButton>
            <UiButton v-if="form._id" variant="ghost" @click="reset">Cancel</UiButton>
          </div>

          <div v-if="msg" class="text-[11px] bg-slate-950 border border-slate-800 rounded-xl p-2 text-emerald-300">
            {{ msg }}
          </div>
        </div>
      </UiCard>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from 'axios'
import { Shield as ShieldIcon, Pencil, Trash2, Plus, Save } from 'lucide-vue-next'

const roles = ref([])
const permsList = ref([])
const permsGrouped = ref({})
const loading = ref(false)
const saving = ref(false)
const msg = ref('')

const form = reactive({ 
  _id:'', name:'', label:'', description:'', 
  permissions:['read'], is_system:false 
})

async function load(){
  loading.value = true
  try{
    const [r, pr] = await Promise.all([
      axios.get('/admin/roles', {withCredentials:true}),
      axios.get('/admin/permissions', {withCredentials:true}).catch(()=>({data:{data:[],grouped:{}}}))
    ])
    roles.value = r.data.data || []
    permsList.value = pr.data.data || []
    permsGrouped.value = pr.data.grouped || {}
  } finally { loading.value=false }
}

function edit(r){
  Object.assign(form, {
    _id: r._id || r.name,
    name: r.name,
    label: r.label || '',
    description: r.description || '',
    permissions: Array.isArray(r.permissions) ? [...r.permissions] : ['read'],
    is_system: !!r.is_system
  })
  window.scrollTo({top:0, behavior:'smooth'})
}
function reset(){
  Object.assign(form,{_id:'',name:'',label:'',description:'',permissions:['read'],is_system:false})
  msg.value=''
}
async function save(){
  saving.value=true; msg.value=''
  try{
    const payload = {
      label: form.label,
      description: form.description,
      permissions: form.permissions
    }
    if(form._id){
      await axios.put(`/admin/roles/${encodeURIComponent(form.name)}`, payload, {withCredentials:true})
      msg.value='Role updated ✔'
    }else{
      if(!form.name) throw new Error('Role name wajib')
      await axios.post('/admin/roles', { name: form.name, ...payload }, {withCredentials:true})
      msg.value='Role created ✔'
    }
    reset()
    await load()
  }catch(e){
    msg.value='Error: '+(e.response?.data?.message||e.message)
  }finally{ saving.value=false }
}
async function remove(r){
  if(!confirm(`Hapus role "${r.name}" ?`)) return
  try{
    await axios.delete(`/admin/roles/${encodeURIComponent(r.name)}`, {withCredentials:true})
    load()
  }catch(e){ alert(e.response?.data?.message || e.message) }
}

onMounted(load)
</script>
