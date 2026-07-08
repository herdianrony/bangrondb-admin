<template>
  <div class="space-y-5">
    <div class="card card-hover">
      <h2 class="text-xl font-bold mb-1 flex items-center gap-2">
        <Users :size="22" class="text-indigo-400" />
        Users
      </h2>
      <p class="text-sm text-slate-400">Manage user accounts, assign roles, and reset passwords</p>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 card card-hover">
        <div class="flex items-center justify-between mb-3">
          <h3 class="section-label font-semibold">Users ({{ users.length }})</h3>
          <button class="btn-sm" @click="load">
            <RefreshCw :size="14" />
            Reload
          </button>
        </div>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Username</th><th>Email</th><th>Roles</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="u in users" :key="u._id">
                <td class="py-2 font-medium">{{ u.username }}<div class="text-[11px] text-slate-500">{{ u._id }}</div></td>
                <td>{{ u.email }}</td>
                <td><span v-for="r in (u.roles||[])" :key="r" class="badge mr-1">{{ r }}</span></td>
                <td>
                  <span v-if="u.active!==false" class="text-emerald-400 flex items-center gap-1">
                    <CheckCircle :size="14" />
                    Active
                  </span>
                  <span v-else class="text-red-400 flex items-center gap-1">
                    <XCircle :size="14" />
                    Disabled
                  </span>
                </td>
                <td class="text-right space-x-1">
                  <button class="btn-ghost-sm" @click="editUser(u)">
                    <Pencil :size="13" />
                  </button>
                  <button class="btn-ghost-sm" @click="resetPass(u)">
                    <Key :size="13" />
                  </button>
                  <button class="btn-ghost-sm" @click="toggleActive(u)">
                    <Ban v-if="u.active!==false" :size="13" />
                    <CheckCircle v-else :size="13" />
                  </button>
                  <button class="btn-ghost-sm text-amber-300" @click="revokeTokens(u)">
                    <AlertTriangle :size="13" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card card-hover space-y-3">
        <h3 class="section-label font-semibold">
          <template v-if="form._id">
            <Pencil :size="16" class="text-indigo-400" />
            Edit User
          </template>
          <template v-else>
            <UserPlus :size="16" class="text-emerald-400" />
            New User
          </template>
        </h3>
        <input v-model="form.username" placeholder="Username" class="input"/>
        <input v-model="form.email" placeholder="Email" class="input"/>
        <input v-model="form.name" placeholder="Full Name" class="input"/>
        <input v-model="form.password" type="password" placeholder="Leave empty to auto-generate" class="input"/>
        <div>
          <div class="text-xs text-slate-400 mb-1">Roles</div>
          <div class="flex flex-wrap gap-2">
            <label v-for="r in allRoles" :key="r.name" class="text-xs flex items-center gap-1">
              <input type="checkbox" :value="r.name" v-model="form.roles"> {{ r.name }}
            </label>
          </div>
        </div>
        <label class="text-sm flex items-center gap-2"><input type="checkbox" v-model="form.active"> Active</label>
        <div class="flex gap-2">
          <button class="btn-sm flex-1" @click="save">{{ form._id ? 'Update' : 'Create' }}</button>
          <button v-if="form._id" class="btn-ghost-sm" @click="resetForm">Cancel</button>
        </div>
        <pre v-if="result" class="code-block">{{ result }}</pre>
      </div>
    </div>

    <!-- Token / Blacklist panel -->
    <div class="grid md:grid-cols-2 gap-4">
      <div class="card card-hover">
        <h3 class="section-label font-semibold mb-2 flex items-center gap-2">
          <KeyRound :size="16" class="text-emerald-400" />
          Active Refresh Tokens
        </h3>
        <button class="btn-ghost-sm mb-2" @click="loadTokens">
          <RefreshCw :size="13" />
          Reload
        </button>
        <div class="text-xs max-h-60 overflow-auto space-y-1">
          <div v-for="t in tokens" :key="t.jti" class="bg-slate-950 p-2 rounded flex justify-between">
            <div>
              <div class="font-mono text-[11px]">{{ t.jti?.slice(0,16) }}&hellip;</div>
              <div class="text-slate-400">{{ t.username }} &bull; {{ t.user_id }}</div>
            </div>
            <button class="text-amber-300 text-[11px]" @click="revokeJti(t.jti)">
              <Ban :size="13" />
            </button>
          </div>
        </div>
      </div>
      <div class="card card-hover">
        <h3 class="section-label font-semibold mb-2 flex items-center gap-2">
          <Ban :size="16" class="text-red-400" />
          Blacklist
        </h3>
        <button class="btn-ghost-sm mb-2" @click="loadBlacklist">
          <RefreshCw :size="13" />
          Reload
        </button>
        <div class="text-xs max-h-60 overflow-auto space-y-1">
          <div v-for="b in blacklist" :key="b.jti" class="bg-slate-950 p-2 rounded">
            <span class="font-mono">{{ b.jti?.slice(0,16) }}&hellip;</span>
            <span class="text-slate-500 ml-2">{{ b.reason }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import axios from 'axios'
import {
  Users,
  Shield,
  Plus,
  RefreshCw,
  Pencil,
  Trash2,
  Key,
  KeyRound,
  Ban,
  CheckCircle,
  XCircle,
  AlertTriangle,
  Copy,
  UserPlus,
  UserCog
} from 'lucide-vue-next'

const users = ref([])
const allRoles = ref([])
const tokens = ref([])
const blacklist = ref([])
const result = ref('')

const form = reactive({ _id:'', username:'', email:'', name:'', password:'', roles:['user'], active:true })

async function load(){
  const r = await axios.get('/api/admin/users').catch(()=>({data:{data:[]}}))
  users.value = r.data.data || []
  const rr = await axios.get('/api/admin/roles').catch(()=>({data:{data:[]}}))
  allRoles.value = rr.data.data || []
}
function resetForm(){ Object.assign(form,{_id:'',username:'',email:'',name:'',password:'',roles:['user'],active:true}); result.value='' }
function editUser(u){
  Object.assign(form, {_id:u._id, username:u.username, email:u.email||'', name:u.name||'', password:'', roles:u.roles||['user'], active:u.active!==false})
}
async function save(){
  try{
    if(form._id){
      await axios.put(`/api/admin/users/${form._id}`, {...form})
      result.value = 'updated'
    }else{
      const r = await axios.post('/api/admin/users', {...form})
      result.value = JSON.stringify(r.data, null, 2)
    }
    resetForm(); load()
  }catch(e){ result.value = JSON.stringify(e.response?.data||e.message, null, 2) }
}
async function resetPass(u){
  const r = await axios.post(`/api/admin/users/${u._id}/reset-password`, {})
  alert('Password baru: ' + r.data.new_password)
  load()
}
async function toggleActive(u){
  await axios.post(`/api/admin/users/${u._id}/toggle-active`, {})
  load()
}
async function revokeTokens(u){
  await axios.post(`/api/admin/users/${u._id}/revoke-tokens`, {})
  alert('Token untuk '+u.username+' telah dicabut')
  loadTokens()
}
async function loadTokens(){
  const r = await axios.get('/api/auth/tokens').catch(()=>({data:{data:[]}}))
  tokens.value = r.data.data || []
}
async function loadBlacklist(){
  const r = await axios.get('/api/auth/blacklist').catch(()=>({data:{data:[]}}))
  blacklist.value = r.data.data || []
}
async function revokeJti(jti){
  await axios.post('/api/auth/revoke', {jti, reason:'admin_manual'})
  loadTokens(); loadBlacklist()
}

onMounted(()=>{ load(); loadTokens(); loadBlacklist() })
</script>