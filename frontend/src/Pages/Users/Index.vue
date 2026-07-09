<template>
  <div class="space-y-5">
    <UiPageHeader
      title="Users"
      description="Kelola akun di auth.users – role = SINGLE relation → auth.roles._id"
      :icon="UsersIcon"
    >
      <template #badge>
        <UiBadge color="indigo">{{ users.length }} users</UiBadge>
      </template>
      <template #actions>
        <UiButton size="sm" @click="load" :loading="loading" variant="ghost">
          <RefreshCw :size="14"/> Reload
        </UiButton>
        <UiButton size="sm" @click="resetForm" variant="secondary" v-if="form._id">
          Cancel
        </UiButton>
      </template>
      <template #meta>
        Model: <code class="text-[11px] bg-slate-900 px-1.5 py-0.5 rounded text-indigo-300">user.role → roles.permissions[] → resource → action</code>
        • JWT: access 15m / refresh 30d • Session: PHPSESSID HttpOnly
      </template>
    </UiPageHeader>

    <div class="grid lg:grid-cols-3 gap-4">
      <!-- Users table -->
      <UiCard class="lg:col-span-2" :padding="false">
        <div class="p-4 border-b border-white/[0.06] flex items-center justify-between">
          <div class="text-[12px] font-[600] text-slate-300">Auth Users – auth.users</div>
          <div class="text-[10px] text-slate-500">{{ users.length }} records</div>
        </div>
<<<<<<< HEAD
        
        <UiTable
          :columns="[
            {key:'username', label:'User', mono:false},
            {key:'email', label:'Email'},
            {key:'role', label:'Role'},
            {key:'active', label:'Status'},
          ]"
          :items="users"
          :loading="loading"
        >
          <template #cell-username="{row}">
            <div class="font-[550] text-slate-100">{{ row.username }}</div>
            <div class="text-[10px] text-slate-500 font-mono">{{ row._id }}</div>
            <div v-if="row.name" class="text-[11px] text-slate-400">{{ row.name }}</div>
          </template>
          <template #cell-role="{row}">
            <UiBadge :color="roleColor(row.role || row.roles?.[0])">
              {{ row.role || row.roles?.[0] || 'user' }}
            </UiBadge>
          </template>
          <template #cell-active="{row}">
            <span :class="row.active===false ? 'text-red-400' : 'text-emerald-400'" class="text-[11px] flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full"
                :class="row.active===false ? 'bg-red-400' : 'bg-emerald-400'"></span>
              {{ row.active===false ? 'Disabled' : 'Active' }}
            </span>
          </template>
          <template #actions="{row}">
            <div class="flex gap-1 justify-end">
              <UiButton size="sm" variant="subtle" @click="editUser(row)" title="Edit">
                <Pencil :size="13"/>
              </UiButton>
              <UiButton size="sm" variant="subtle" @click="resetPass(row)" title="Reset password">
                <Key :size="13"/>
              </UiButton>
              <UiButton size="sm" variant="ghost" @click="toggleActive(row)" :title="row.active!==false ? 'Disable':'Enable'">
                <Ban v-if="row.active!==false" :size="13"/>
                <CheckCircle v-else :size="13"/>
              </UiButton>
              <UiButton size="sm" variant="ghost" @click="revokeTokens(row)" title="Revoke tokens">
                <AlertTriangle :size="13" class="text-amber-400"/>
              </UiButton>
            </div>
          </template>
          <template #empty>
            <UiEmptyState
              :icon="UsersIcon"
              title="Belum ada user"
              description="Buat user pertama via form di kanan, atau jalankan Setup Wizard"
            >
              <template #action>
                <UiButton size="sm" @click="$el.querySelector('input')?.focus()">Create user</UiButton>
              </template>
            </UiEmptyState>
          </template>
        </UiTable>
      </UiCard>
=======
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="u in users" :key="u._id">
                <td class="py-2 font-medium">{{ u.username }}<div class="text-[11px] text-slate-500">{{ u._id }}</div></td>
                <td>{{ u.email }}</td>
                <td><span class="badge mr-1">{{ u.role || (u.roles && u.roles[0]) || 'user' }}</span></td>
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
>>>>>>> 2649ce77a485fe976186d38e231134378ddc6160

      <!-- Form -->
      <UiCard class="h-fit">
        <h3 class="font-[600] text-slate-100 mb-3 flex items-center gap-2">
          <component :is="form._id ? Pencil : UserPlus" :size="15" :class="form._id ? 'text-indigo-400' : 'text-emerald-400'"/>
          {{ form._id ? 'Edit User' : 'New User' }}
        </h3>
<<<<<<< HEAD

        <div class="space-y-3">
          <UiInput v-model="form.username" label="Username *" placeholder="johndoe" :required="true" />
          <UiInput v-model="form.email" label="Email" type="email" placeholder="john@local" />
          <UiInput v-model="form.name" label="Full name" placeholder="John Doe" />
          <UiInput v-model="form.password" label="Password" type="password"
            placeholder="Kosongkan = auto-generate"
            hint="Min 8 karakter – Argon2id" />

          <UiSelect
            v-model="form.role"
            label="Role *"
            :options="allRoles"
            option-value="name"
            option-label="label"
            :required="true"
            hint="Single relation → auth.roles._id"
          >
            <option value="" disabled>Pilih role…</option>
            <option v-for="r in allRoles" :key="r.name||r._id" :value="r.name">
              {{ r.label || r.name }} {{ r.is_system ? '• system' : '' }}
            </option>
          </UiSelect>

          <label class="flex items-center gap-2 text-[13px] text-slate-300">
            <input type="checkbox" v-model="form.active" class="rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500/30">
            Active
          </label>

          <div class="flex gap-2 pt-1">
            <UiButton :block="true" @click="save" :loading="saving">
              {{ form._id ? 'Update' : 'Create user' }}
            </UiButton>
            <UiButton v-if="form._id" variant="ghost" @click="resetForm">Cancel</UiButton>
          </div>

          <div v-if="result" class="text-[11px] bg-slate-950 border border-slate-800 rounded-xl p-2 overflow-auto max-h-40">
            <pre class="text-indigo-200 whitespace-pre-wrap">{{ result }}</pre>
          </div>

          <div class="text-[10px] text-slate-500 bg-slate-950/70 border border-slate-800 rounded-xl p-2 leading-relaxed">
            • <b>role</b> disimpan di <code>auth.users.role</code> (relation ONE)<br>
            • <code>roles[]</code> tetap disimpan otomatis untuk BC JWT<br>
            • Password → Argon2id<br>
            • Reset password → revoke semua refresh tokens
          </div>
=======
        <input v-model="form.username" placeholder="Username" class="input"/>
        <input v-model="form.email" placeholder="Email" class="input"/>
        <input v-model="form.name" placeholder="Full Name" class="input"/>
        <input v-model="form.password" type="password" placeholder="Leave empty to auto-generate" class="input"/>
        <div>
          <div class="text-xs text-slate-400 mb-1">Role <span class="text-[10px] text-amber-400">(single relation)</span></div>
          <select v-model="form.role" class="input">
            <option v-for="r in allRoles" :key="r.name" :value="r.name">{{ r.label || r.name }}</option>
          </select>
          <div class="text-[10px] text-slate-500 mt-1">auth.users.role → auth.roles._id</div>
>>>>>>> 2649ce77a485fe976186d38e231134378ddc6160
        </div>
      </UiCard>
    </div>

    <!-- Tokens / Blacklist – 2 cards -->
    <div class="grid md:grid-cols-2 gap-4">
      <UiCard>
        <template #default>
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-[600] text-[13px] flex items-center gap-2">
              <KeyRound :size="15" class="text-emerald-400"/> Active Refresh Tokens
            </h3>
            <UiButton size="sm" variant="ghost" @click="loadTokens">Reload</UiButton>
          </div>
          <div class="space-y-1.5 max-h-72 overflow-auto text-[11px] pr-1">
            <div v-for="t in tokens" :key="t.jti"
              class="flex items-center justify-between bg-slate-950 border border-slate-800 rounded-xl px-3 py-2">
              <div class="min-w-0">
                <div class="font-mono text-indigo-300 truncate">{{ (t.jti||'').slice(0,20) }}…</div>
                <div class="text-slate-400 truncate">{{ t.username || t.user_id }} • {{ t.ip || '-' }}</div>
              </div>
              <UiButton size="sm" variant="ghost" @click="revokeJti(t.jti)">Revoke</UiButton>
            </div>
            <UiEmptyState v-if="tokens.length===0"
              title="No active tokens"
              description="Refresh tokens muncul setelah user login"
            />
          </div>
        </template>
      </UiCard>

      <UiCard>
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-[600] text-[13px] flex items-center gap-2">
            <Ban :size="15" class="text-red-400"/> Token Blacklist
          </h3>
          <UiButton size="sm" variant="ghost" @click="loadBlacklist">Reload</UiButton>
        </div>
        <div class="space-y-1 text-[11px] max-h-72 overflow-auto">
          <div v-for="b in blacklist" :key="b.jti"
            class="bg-slate-950 border border-slate-800 rounded-lg px-3 py-1.5 flex justify-between">
            <span class="font-mono text-slate-300">{{ (b.jti||'').slice(0,16) }}…</span>
            <span class="text-slate-500">{{ b.reason || 'revoked' }}</span>
          </div>
          <div v-if="blacklist.length===0" class="text-slate-600 text-center py-6 text-xs">
            Blacklist kosong – good!
          </div>
        </div>
      </UiCard>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import {
  Users as UsersIcon, RefreshCw, Pencil, Trash2, Key, KeyRound,
  Ban, CheckCircle, AlertTriangle, UserPlus
} from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const toast = useToast()

// UI Kit – auto registered globally via main.js, import optional for IDE
// import UiCard from '@/Components/UI/UiCard.vue'
// import UiButton from '@/Components/UI/UiButton.vue'
// ...

const users = ref([])
const allRoles = ref([])
const tokens = ref([])
const blacklist = ref([])
const result = ref('')
const loading = ref(false)
const saving = ref(false)

<<<<<<< HEAD
const form = reactive({ 
  _id:'', username:'', email:'', name:'', password:'', 
  role:'user', roles:['user'], active:true 
})
=======
const form = reactive({ _id:'', username:'', email:'', name:'', password:'', role:'user', roles:['user'], active:true })
>>>>>>> 2649ce77a485fe976186d38e231134378ddc6160

async function load(){
  loading.value = true
  try {
    const r = await axios.get('/admin/users', {withCredentials:true})
    users.value = r.data.data || []
    const rr = await axios.get('/admin/roles', {withCredentials:true})
    allRoles.value = rr.data.data || []
  } catch(e){} 
  finally { loading.value=false }
}

function resetForm(){ 
  Object.assign(form,{_id:'',username:'',email:'',name:'',password:'',role:'user',roles:['user'],active:true})
  result.value=''
}
<<<<<<< HEAD
function editUser(u){
  const r = u.role || u.roles?.[0] || 'user'
  Object.assign(form, {
    _id:u._id, username:u.username, email:u.email||'', name:u.name||'',
    password:'', role:r, roles:[r], active:u.active!==false
  })
  window.scrollTo({top:0, behavior:'smooth'})
=======
function resetForm(){ Object.assign(form,{_id:'',username:'',email:'',name:'',password:'',role:'user',roles:['user'],active:true}); result.value='' }
function editUser(u){
  const r = u.role || (u.roles && u.roles[0]) || 'user'
  Object.assign(form, {_id:u._id, username:u.username, email:u.email||'', name:u.name||'', password:'', role:r, roles:[r], active:u.active!==false})
>>>>>>> 2649ce77a485fe976186d38e231134378ddc6160
}
async function save(){
  saving.value = true
  result.value = ''
  try{
<<<<<<< HEAD
    const payload = { ...form, roles: [form.role] }
    let res
    if(form._id){
      res = await axios.put(`/admin/users/${form._id}`, payload, {withCredentials:true})
      result.value = 'Updated ✔'
    }else{
      res = await axios.post('/admin/users', payload, {withCredentials:true})
      result.value = 'Created ✔\n' + JSON.stringify(res.data, null, 2)
=======
    const payload = {...form, roles:[form.role]}
    if(form._id){
      await axios.put(`/admin/users/${form._id}`, payload)
      result.value = 'updated'
    }else{
      const r = await axios.post('/admin/users', payload)
      result.value = JSON.stringify(r.data, null, 2)
>>>>>>> 2649ce77a485fe976186d38e231134378ddc6160
    }
    resetForm(); await load()
  }catch(e){
    result.value = 'Error: ' + (e.response?.data?.message || e.message)
  }finally{ saving.value=false }
}
async function resetPass(u){
<<<<<<< HEAD
  try{
    const r = await axios.post(`/admin/users/${u._id}/reset-password`, {}, {withCredentials:true})
    alert('Password baru untuk '+u.username+':\n\n' + r.data.new_password + '\n\nUser wajib ganti saat login berikutnya.')
    load()
  }catch(e){ alert(e.response?.data?.message || e.message) }
=======
  const r = await axios.post(`/admin/users/${u._id}/reset-password`, {})
  toast.info('Password baru: ' + r.data.new_password)
  load()
>>>>>>> 2649ce77a485fe976186d38e231134378ddc6160
}
async function toggleActive(u){
  await axios.post(`/admin/users/${u._id}/toggle-active`, {}, {withCredentials:true}); load()
}
async function revokeTokens(u){
<<<<<<< HEAD
  const r = await axios.post(`/admin/users/${u._id}/revoke-tokens`, {}, {withCredentials:true})
  alert(`Refresh tokens ${u.username} dicabut: ${r.data.revoked_refresh}`)
=======
  await axios.post(`/admin/users/${u._id}/revoke-tokens`, {})
  toast.info('Token untuk '+u.username+' telah dicabut')
>>>>>>> 2649ce77a485fe976186d38e231134378ddc6160
  loadTokens()
}
async function loadTokens(){
  try{
    const r = await axios.get('/auth/tokens', {withCredentials:true})
    tokens.value = r.data.data || []
  }catch{ tokens.value=[] }
}
async function loadBlacklist(){
  try{
    const r = await axios.get('/auth/blacklist', {withCredentials:true})
    blacklist.value = r.data.data || []
  }catch{ blacklist.value=[] }
}
async function revokeJti(jti){
  if(!confirm('Revoke token '+jti.slice(0,12)+'… ?')) return
  await axios.post('/auth/revoke', {jti, reason:'admin_ui'}, {withCredentials:true})
  loadTokens(); loadBlacklist()
}
function roleColor(r){
  return { superadmin:'red', admin:'amber', editor:'indigo', user:'slate', viewer:'slate', guest:'slate'}[r] || 'slate'
}

onMounted(()=>{ load(); loadTokens(); loadBlacklist() })
</script>
