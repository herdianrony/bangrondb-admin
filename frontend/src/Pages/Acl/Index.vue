<template>
  <div class="space-y-5">
    <!-- Auth / JWT login bar -->
    <div class="card">
      <div class="flex flex-wrap items-center gap-3 justify-between">
        <div>
          <div class="flex items-center gap-2">
            <Shield :size="20" class="text-indigo-400" />
            <h2 class="font-bold text-lg">Access Control</h2>
          </div>
          <p class="text-xs text-slate-400">RBAC with field-level rules, row-level filters, JWT, and audit logging</p>
        </div>
        <div class="flex items-center gap-2 text-xs">
          <span v-if="authUser" class="badge badge-info">
            <User :size="12" class="inline mr-1" />
            {{ authUser.username || authUser.sub }} · {{ (authUser.roles||[]).join(',') }}
          </span>
          <span v-else class="badge">guest</span>
          <button v-if="authToken" class="btn-ghost-sm" @click="logout">
            <LogOut :size="14" class="inline mr-1" /> Logout
          </button>
        </div>
      </div>

      <!-- JWT login mini -->
      <div class="grid md:grid-cols-4 gap-2 mt-3 items-end" v-if="!authToken">
        <input v-model="loginForm.username" placeholder="username / email" class="input input-sm"/>
        <input v-model="loginForm.password" type="password" placeholder="password" class="input input-sm"/>
        <button class="btn btn-sm" @click="doLogin">
          <LogIn :size="14" class="inline mr-1" /> Sign In
        </button>
        <button class="btn-ghost btn-sm" @click="doRegister">Register</button>
      </div>
      <div v-else class="text-xs text-slate-400 mt-2 break-all flex items-center gap-2">
        <span>Token: {{ authToken.slice(0,60) }}…</span>
        <button class="btn-ghost-sm" @click="copyToken" title="Copy token"><Copy :size="14" /></button>
        <button class="btn-ghost-sm" @click="testMe" title="Test /auth/me">
          <Play :size="14" class="inline mr-1" /> /auth/me
        </button>
      </div>
    </div>

    <!-- collection selector + ACL toggle -->
    <div class="card">
      <div class="flex flex-wrap gap-3 items-end">
        <div><label class="section-label">DB</label>
          <input v-model="db" class="input input-sm w-36" placeholder="app"/></div>
        <div><label class="section-label">Collection</label>
          <input v-model="col" class="input input-sm w-44" placeholder="users"/></div>
        <button class="btn-ghost btn-sm" @click="load">
          <RefreshCw :size="14" class="inline mr-1" /> Load ACL
        </button>
        <button class="btn btn-sm" @click="save">
          <Save :size="14" class="inline mr-1" /> Save ACL
        </button>
        <label class="flex items-center gap-2 text-sm ml-auto">
          <input type="checkbox" v-model="acl.enabled"/>
          <span :class="acl.enabled ? 'text-emerald-300' : 'text-slate-400'">
            <Lock v-if="acl.enabled" :size="14" class="inline mr-1" />
            <Unlock v-else :size="14" class="inline mr-1" />
            {{ acl.enabled ? 'ACL ON' : 'ACL OFF' }}
          </span>
        </label>
        <select v-model="acl.default_role" class="bg-slate-950 border border-slate-800 rounded-lg px-2 py-1.5 text-xs">
          <option v-for="r in Object.keys(acl.roles)" :key="r" :value="r">default: {{ r }}</option>
        </select>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
      <!-- roles matrix -->
      <div class="card lg:col-span-2">
        <h3 class="font-semibold mb-3">Roles & Permissions</h3>
        <div class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th class="text-left">Role</th>
              <th v-for="p in allPerms" :key="p" class="text-center text-[10px] px-1">{{ p }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(perms, role) in acl.roles" :key="role">
              <td class="pr-2">
                <input :value="role" @change="renameRole(role, $event.target.value)"
                       class="bg-transparent outline-none w-28 font-mono text-indigo-300"/>
              </td>
              <td v-for="p in allPerms" :key="p" class="text-center">
                <input type="checkbox"
                  :checked="perms.includes('*') || perms.includes(p)"
                  :disabled="perms.includes('*') && p!=='*'"
                  @change="togglePerm(role,p,$event.target.checked)"/>
              </td>
              <td class="text-right">
                <button class="btn-ghost-sm text-red-400" @click="removeRole(role)" title="Remove role"><X :size="14" /></button>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
        <div class="flex gap-2 mt-3 items-center">
          <input v-model="newRole" placeholder="new_role" class="input input-sm w-40"/>
          <button class="btn-ghost btn-sm" @click="addRole">
            <Plus :size="14" class="inline mr-1" /> Role
          </button>
          <span class="text-[11px] text-slate-400 ml-auto">* = all · read=find · create=insert · update · delete</span>
        </div>
      </div>

      <!-- test + auth -->
      <div class="card space-y-3">
        <h3 class="font-semibold">Test Access</h3>
        <input v-model="testRole" placeholder="editor,user" class="input input-sm"/>
        <select v-model="testAction" class="input input-sm">
          <option v-for="p in allPerms.filter(x=>x!=='*')" :key="p" :value="p">{{ p }}</option>
        </select>
        <div class="grid grid-cols-2 gap-2">
          <button class="btn btn-sm" @click="testAccess">
            <Play :size="14" class="inline mr-1" /> Test API
          </button>
          <button class="btn-ghost btn-sm" @click="testWithToken">
            <Key :size="14" class="inline mr-1" /> Test + JWT
          </button>
        </div>
        <pre class="code-block">{{ testResult }}</pre>
        <div class="text-[10px] text-slate-400">
          Headers used by server:<br/>
          <code>X-Role: admin,editor</code><br/>
          <code>X-API-Key: sk_...</code><br/>
          <code>Authorization: Bearer &lt;jwt&gt;</code>
        </div>
      </div>
    </div>

    <!-- field-level allow/deny + row filter -->
    <div class="grid lg:grid-cols-2 gap-4">
      <div class="card">
        <div class="flex justify-between items-center mb-2">
          <h3 class="font-semibold">Field-level ACL</h3>
          <span class="badge badge-warning">allowlist & denylist</span>
        </div>
        <div v-for="role in Object.keys(acl.roles)" :key="role" class="mb-4 border border-slate-800 rounded-xl p-3">
          <div class="flex justify-between items-center mb-2">
            <b class="text-indigo-300">{{ role }}</b>
            <select :value="getFieldMode(role)" @change="setFieldMode(role, $event.target.value)"
              class="bg-slate-950 border border-slate-800 rounded px-2 py-1 text-[11px]">
              <option value="deny">Deny list (default allow)</option>
              <option value="allow">Allow list (default deny)</option>
            </select>
          </div>
          <div v-for="(mode, field) in getFieldRules(role)" :key="field" class="flex gap-2 mb-1 items-center">
            <input :value="field" disabled class="input input-sm flex-1 opacity-80"/>
            <select :value="mode" @change="setFieldRule(role, field, $event.target.value)"
              class="bg-slate-950 border border-slate-800 rounded px-2 py-1 text-[11px] w-24">
              <option value="allow">allow</option>
              <option value="deny">deny</option>
            </select>
            <button class="btn-ghost-sm text-red-400" @click="removeFieldRule(role, field)" title="Remove"><X :size="14" /></button>
          </div>
          <div class="flex gap-2 mt-2">
            <input v-model="fieldRuleInput[role]" @keyup.enter="addFieldRule(role,'deny')"
              placeholder="field.path" class="input input-sm flex-1"/>
            <button class="btn-ghost btn-sm text-[11px]" @click="addFieldRule(role,'deny')">deny</button>
            <button class="btn-ghost btn-sm text-[11px]" @click="addFieldRule(role,'allow')">allow</button>
          </div>
        </div>
        <p class="text-[10px] text-slate-500">Allowlist mode: only explicitly allowed fields are returned. Deny always overrides allow.</p>
      </div>

      <div class="card">
        <h3 class="font-semibold mb-2">Row-level filter</h3>
        <div v-for="role in Object.keys(acl.roles)" :key="role" class="mb-3">
          <label class="section-label">{{ role }}</label>
          <textarea :value="JSON.stringify(acl.row_filters?.[role] || {}, null, 2)"
            @change="setRowFilter(role, $event.target.value)"
            rows="2" class="input font-mono text-[11px]"></textarea>
        </div>
        <div class="text-[10px] text-slate-400">
          Example:<br/>
          editor → <code>{"status":{"$ne":"draft"}}</code><br/>
          user → <code>{"owner_id":"$user.sub"}</code> (supports variables)
        </div>
      </div>
    </div>

    <!-- API keys + Audit -->
    <div class="grid lg:grid-cols-2 gap-4">
      <div class="card">
        <div class="flex items-center gap-2 mb-2">
          <Key :size="16" class="text-indigo-400" />
          <h3 class="font-semibold">API Keys</h3>
        </div>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr><th class="text-left">Key</th><th>Roles</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="(k,i) in acl.api_keys" :key="i">
                <td class="font-mono">{{ k.key }}</td>
                <td>{{ (k.roles||[]).join(', ') }}</td>
                <td class="text-right">
                  <button @click="acl.api_keys.splice(i,1)" class="btn-ghost-sm text-red-400" title="Delete">
                    <X :size="14" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex gap-2 mt-2">
          <input v-model="newApiKey" placeholder="sk_..." class="input input-sm flex-1"/>
          <input v-model="newApiRoles" placeholder="roles" class="input input-sm w-32"/>
          <button class="btn-ghost btn-sm" @click="addApiKey">
            <Plus :size="14" class="inline mr-1" /> Add
          </button>
          <button class="btn-ghost btn-sm" @click="genApiKey">
            <RefreshCw :size="14" class="inline mr-1" /> Generate
          </button>
        </div>
      </div>

      <div class="card">
        <div class="flex justify-between items-center mb-2">
          <div class="flex items-center gap-2">
            <FileText :size="16" class="text-indigo-400" />
            <h3 class="font-semibold">Audit Log</h3>
          </div>
          <button class="btn-ghost-sm" @click="loadAudit" title="Refresh">
            <RefreshCw :size="14" />
          </button>
        </div>
        <div class="table-container max-h-64 overflow-auto text-[11px]">
          <table class="data-table w-full">
            <thead>
              <tr>
                <th class="text-left">time</th><th>user</th><th>action</th><th>target</th><th>status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in audit" :key="a._id">
                <td>{{ new Date(a.created_at).toLocaleTimeString() }}</td>
                <td>{{ a.username || a.user_id || 'anon' }}<br><span class="text-slate-500">{{ (a.roles||[]).join(',') }}</span></td>
                <td class="font-mono">{{ a.action }}</td>
                <td>{{ a.db }}<span v-if="a.collection">.{{a.collection}}</span></td>
                <td>
                  <span :class="a.status==='ok' || a.status==='allowed' ? 'badge badge-success' : a.status==='forbidden' ? 'badge badge-danger' : 'badge badge-warning'">
                    {{ a.status }}
                  </span>
                </td>
              </tr>
              <tr v-if="!audit.length"><td colspan="5" class="empty-state">No audit logs yet — make a request with X-Role / JWT</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <details class="card">
      <summary class="cursor-pointer text-sm text-slate-300">View Full ACL JSON</summary>
      <pre class="code-block mt-2">{{ JSON.stringify(acl, null, 2) }}</pre>
    </details>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { Shield, User, Lock, Unlock, Plus, X, RefreshCw, Play, Key, FileText, LogIn, LogOut, Copy } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const db = ref('app')
const col = ref('users')

const acl = reactive({
  enabled: false,
  default_role: 'guest',
  roles: {
    admin: ['*'],
    editor: ['read','find','count','create','update'],
    user: ['read','find'],
    guest: []
  },
  field_rules: {},
  row_filters: {},
  api_keys: []
})

const allPerms = ['*','read','find','count','create','update','delete','manage_schema']
const newRole = ref('')
const testRole = ref('editor')
const testAction = ref('read')
const testResult = ref('{}')
const fieldRuleInput = reactive({})
const newApiKey = ref('')
const newApiRoles = ref('editor')

// auth JWT
const authToken = ref(localStorage.getItem('bangrondb_jwt') || '')
const authUser = ref(JSON.parse(localStorage.getItem('bangrondb_user')||'null'))
const loginForm = reactive({username:'admin', password:'admin123'})

if(authToken.value){
  axios.defaults.headers.common['Authorization'] = 'Bearer ' + authToken.value
}

async function doRegister(){
  try{
    await axios.post('/auth/register', {
      username: loginForm.username,
      email: loginForm.username + '@local',
      password: loginForm.password,
      role: 'admin'
    })
    await doLogin()
  }catch(e){ toast.error(e.response?.data?.message || e.message) }
}
async function doLogin(){
  try{
    const r = await axios.post('/auth/login', {
      username: loginForm.username,
      password: loginForm.password
    })
    authToken.value = r.data.token
    authUser.value = r.data.user
    localStorage.setItem('bangrondb_jwt', authToken.value)
    localStorage.setItem('bangrondb_user', JSON.stringify(authUser.value))
    axios.defaults.headers.common['Authorization'] = 'Bearer ' + authToken.value
    testResult.value = 'Logged in as ' + (authUser.value.username||'?') + ' roles: ' + (r.data.roles||[]).join(',')
  }catch(e){ toast.error(e.response?.data?.message || e.message) }
}
function logout(){
  authToken.value=''; authUser.value=null
  localStorage.removeItem('bangrondb_jwt'); localStorage.removeItem('bangrondb_user')
  delete axios.defaults.headers.common['Authorization']
}
function copyToken(){ if(authToken.value) navigator.clipboard.writeText(authToken.value) }
async function testMe(){
  const r = await axios.get('/auth/me').catch(e=>e.response)
  testResult.value = JSON.stringify(r.data, null, 2)
}

// ACL CRUD
async function load(){
  const r = await axios.get(`/databases/${db.value}/collections/${col.value}/acl`).catch(()=>({data:{}}))
  if(r.data?.acl){ Object.keys(acl).forEach(k=>delete acl[k]); Object.assign(acl, r.data.acl) }
  testResult.value = JSON.stringify(r.data, null, 2)
  loadAudit()
}
async function save(){
  await axios.put(`/databases/${db.value}/collections/${col.value}/acl`, {acl})
  testResult.value = 'ACL saved to '+db.value+'.'+col.value
  loadAudit()
}

// roles
function addRole(){ const n=newRole.value.trim(); if(n && !acl.roles[n]) { acl.roles[n]=[]; newRole.value='' } }
function removeRole(r){ delete acl.roles[r] }
function renameRole(oldName, newName){
  newName=newName.trim(); if(!newName || newName===oldName || acl.roles[newName]) return
  acl.roles[newName]=acl.roles[oldName]; delete acl.roles[oldName]
}
function togglePerm(role, perm, on){
  const list = acl.roles[role] || []
  if(on){ if(!list.includes(perm)) list.push(perm) }
  else { const i=list.indexOf(perm); if(i>=0) list.splice(i,1) }
  acl.roles[role] = [...new Set(list)]
}

// test
async function testAccess(){
  const r = await axios.post(`/databases/${db.value}/collections/${col.value}/acl/test`, {
    roles: testRole.value.split(',').map(s=>s.trim()),
    action: testAction.value
  }).catch(e=>e.response)
  testResult.value = JSON.stringify(r.data, null, 2)
}
async function testWithToken(){
  // actually hit documents endpoint with current JWT
  const r = await axios.get(`/databases/${db.value}/collections/${col.value}/documents?limit=1`).catch(e=>e.response)
  testResult.value = JSON.stringify({status:r.status, data:r.data}, null, 2)
  loadAudit()
}

// field rules allow/deny
function getFieldMode(role){
  const rules = acl.field_rules?.[role] || {}
  return rules.__mode || 'deny'
}
function setFieldMode(role, mode){
  if(!acl.field_rules[role]) acl.field_rules[role] = {}
  acl.field_rules[role].__mode = mode
}
function addFieldRule(role, mode='deny'){
  const f = (fieldRuleInput[role]||'').trim()
  if(!f) return
  if(!acl.field_rules[role]) acl.field_rules[role] = {}
  acl.field_rules[role][f] = mode
  fieldRuleInput[role]=''
}
function removeFieldRule(role, field){ if(acl.field_rules[role]) delete acl.field_rules[role][field] }
function setFieldRule(role, field, mode){ if(acl.field_rules[role]) acl.field_rules[role][field]=mode }

// row filter
function setRowFilter(role, txt){
  try{
    const o = txt.trim() ? JSON.parse(txt) : {}
    if(!acl.row_filters) acl.row_filters = {}
    acl.row_filters[role] = o
  }catch(e){ toast.error('JSON invalid '+e.message) }
}

// api keys
function addApiKey(){
  if(!newApiKey.value) return
  acl.api_keys.push({key:newApiKey.value, roles: newApiRoles.value.split(',').map(s=>s.trim())})
  newApiKey.value=''; newApiRoles.value='editor'
}
function genApiKey(){
  const r = Array.from(crypto.getRandomValues(new Uint8Array(24))).map(b=>b.toString(16).padStart(2,'0')).join('')
  newApiKey.value = 'sk_'+r.slice(0,32)
}

// audit
const audit = ref([])
async function loadAudit(){
  const r = await axios.get('/audit/logs?limit=50').catch(()=>({data:{data:[]}}))
  audit.value = r.data.data || []
}

onMounted(()=>{ load(); loadAudit(); setInterval(loadAudit, 8000) })
</script>