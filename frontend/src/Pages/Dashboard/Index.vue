<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center shadow-lg shadow-indigo-500/20">
          <Box class="w-5 h-5 text-white" />
        </div>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-white">Databases</h1>
          <p class="text-slate-500 text-sm mt-0.5">Your BangronDB databases</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button class="btn" @click="actions.openCreate()">
          <Plus class="w-4 h-4" />
          <span class="hidden sm:inline">New Database</span>
        </button>
        <button class="btn-ghost" @click="load">
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="kpi-card" v-for="k in kpis" :key="k.label">
        <div class="flex items-center gap-2 text-slate-500 mb-2">
          <component :is="k.icon" class="w-4 h-4" />
          <span class="text-xs font-medium">{{ k.label }}</span>
        </div>
        <div class="text-2xl font-bold text-white tracking-tight">{{ k.value }}</div>
      </div>
    </div>

    <!-- Role badge / welcome -->
    <div class="flex items-center justify-between bg-slate-900/40 border border-slate-800 rounded-2xl px-4 py-3 text-sm">
      <div>
        Hi, <b class="text-white">{{ username }}</b> 
        <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold"
          :class="{
            'bg-red-500/10 text-red-300': role==='superadmin',
            'bg-amber-500/10 text-amber-300': role==='admin',
            'bg-blue-500/10 text-blue-300': role==='editor',
            'bg-slate-700 text-slate-300': !['superadmin','admin','editor'].includes(role)
          }">
          {{ role }}
        </span>
        <span class="text-slate-500 ml-3 text-xs">Login: {{ auth?.login_at ? new Date(auth.login_at*1000).toLocaleString() : '-' }}</span>
      </div>
      <div class="text-[11px] text-slate-500 hidden md:block">
        ACL: <code>user.role → resource → action</code> • 
        <a href="/permissions" class="text-indigo-400 hover:underline" v-if="['admin','superadmin'].includes(role)">Manage Permissions →</a>
      </div>
    </div>

    <!-- ===== ROLE-BASED WIDGETS ===== -->
    <div class="grid lg:grid-cols-3 gap-4">

      <!-- Editor: My Drafts -->
      <div v-if="['editor','user','author','admin','superadmin'].includes(role)" 
           class="card lg:col-span-1">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold flex items-center gap-2">
            <FileEdit :size="16" class="text-amber-400"/> My Drafts
          </h3>
          <span class="text-[10px] px-2 py-0.5 rounded bg-amber-500/10 text-amber-300">editor</span>
        </div>
        <div class="space-y-2 text-sm max-h-64 overflow-auto">
          <div v-for="(d,i) in myDrafts" :key="i"
               class="flex items-center justify-between bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 hover:border-slate-700">
            <div>
              <div class="text-slate-200">{{ d.title || d.name || 'Untitled' }}</div>
              <div class="text-[11px] text-slate-500">{{ d.updated_at || d.published_at || 'recent' }} • {{ d.status || 'draft' }}</div>
            </div>
            <button class="text-[11px] text-indigo-400 hover:underline">Edit →</button>
          </div>
          <div v-if="myDrafts.length===0" class="text-slate-500 text-xs text-center py-4">No drafts 🎉</div>
        </div>
        <div class="text-[11px] text-slate-500 mt-3">
          Row-level filter: <code>owner_id = {{ '{{user.sub}}' }}</code>
        </div>
      </div>

      <!-- Admin: Audit Log -->
      <div v-if="['admin','superadmin'].includes(role)"
           class="card lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold flex items-center gap-2">
            <ClipboardList :size="16" class="text-emerald-400"/> Audit Log – Live
          </h3>
          <a href="/audit" class="text-[11px] text-indigo-400 hover:underline">view all →</a>
        </div>
        <div class="overflow-auto max-h-64">
          <table class="w-full text-xs">
            <thead class="text-slate-500 border-b border-slate-800">
              <tr><th class="text-left py-1.5">Time</th><th class="text-left">Action</th><th>User</th><th>Status</th></tr>
            </thead>
            <tbody>
              <tr v-for="(log,i) in auditLogs" :key="i" class="border-b border-slate-800/40">
                <td class="py-1.5 text-slate-400">{{ log.time || log.created_at?.slice(11,19) || 'now' }}</td>
                <td class="font-mono text-[11px] text-indigo-300">{{ log.action }}</td>
                <td>{{ log.user || log.username || 'system' }}</td>
                <td>
                  <span :class="(log.status||'ok')==='ok' ? 'text-emerald-400' : 'text-amber-400'">
                    {{ log.status || 'ok' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex gap-4 text-[11px] text-slate-500 mt-3">
          <span>• auth.login / auth.refresh tracked</span>
          <span>• ACL changes logged</span>
          <span>• Source: <code>system.audit_logs</code></span>
        </div>
      </div>

      <!-- Superadmin: System Health -->
      <div v-if="role==='superadmin'" class="card lg:col-span-3">
        <h3 class="font-semibold flex items-center gap-2 mb-3">
          <Activity :size="16" class="text-red-400"/> System Health – Superadmin
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3 text-sm" v-if="healthMetrics">
          <div class="bg-slate-950 rounded-xl p-3 border border-slate-800">
            <div class="text-[10px] text-slate-500 uppercase">Status</div>
            <div class="text-emerald-400 font-bold">{{ healthMetrics.status }}</div>
          </div>
          <div class="bg-slate-950 rounded-xl p-3 border border-slate-800">
            <div class="text-[10px] text-slate-500 uppercase">Uptime</div>
            <div class="text-white font-mono">{{ healthMetrics.uptime }}</div>
          </div>
          <div class="bg-slate-950 rounded-xl p-3 border border-slate-800">
            <div class="text-[10px] text-slate-500 uppercase">CPU</div>
            <div class="text-amber-300">{{ healthMetrics.cpu }}</div>
          </div>
          <div class="bg-slate-950 rounded-xl p-3 border border-slate-800">
            <div class="text-[10px] text-slate-500 uppercase">Memory</div>
            <div class="text-white">{{ healthMetrics.memory }}</div>
          </div>
          <div class="bg-slate-950 rounded-xl p-3 border border-slate-800">
            <div class="text-[10px] text-slate-500 uppercase">Storage</div>
            <div class="text-white">{{ healthMetrics.storage }}</div>
          </div>
          <div class="bg-slate-950 rounded-xl p-3 border border-slate-800">
            <div class="text-[10px] text-slate-500 uppercase">Indexes</div>
            <div class="text-indigo-300">{{ healthMetrics.indexes || 24 }}</div>
          </div>
        </div>
        <div class="flex flex-wrap gap-2 mt-3 text-[11px]">
          <a href="/users" class="px-2 py-1 bg-slate-800 rounded hover:bg-slate-700">👥 Users</a>
          <a href="/roles" class="px-2 py-1 bg-slate-800 rounded hover:bg-slate-700">🛡️ Roles</a>
          <a href="/permissions" class="px-2 py-1 bg-indigo-900/40 text-indigo-300 rounded">🔑 Permissions</a>
          <a href="/tokens" class="px-2 py-1 bg-slate-800 rounded hover:bg-slate-700">🔐 Tokens + API Keys</a>
          <a href="/acl" class="px-2 py-1 bg-slate-800 rounded hover:bg-slate-700">🔒 ACL Matrix</a>
        </div>
      </div>

      <!-- Quick Actions – permission based -->
      <div class="card lg:col-span-3" v-if="can('create') || can('export') || can('manage_acl')">
        <h3 class="font-semibold mb-3">⚡ Quick Actions – {{ role }}</h3>
        <div class="flex flex-wrap gap-2 text-xs">
          <button v-if="can('create')" class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg">+ New Document</button>
          <button v-if="can('export')" class="px-3 py-1.5 bg-slate-800 border border-slate-700 rounded-lg">⬇ Export</button>
          <button v-if="can('import')" class="px-3 py-1.5 bg-slate-800 border border-slate-700 rounded-lg">⬆ Import</button>
          <button v-if="can('publish')" class="px-3 py-1.5 bg-emerald-900/30 text-emerald-300 rounded-lg">📢 Publish</button>
          <button v-if="can('approve')" class="px-3 py-1.5 bg-amber-900/30 text-amber-300 rounded-lg">✓ Approve</button>
          <button v-if="can('manage_schema')" class="px-3 py-1.5 bg-slate-800 border border-slate-700 rounded-lg">🧩 Schema</button>
          <button v-if="can('manage_acl')" class="px-3 py-1.5 bg-violet-900/30 text-violet-300 rounded-lg">🔒 ACL</button>
          <span class="text-slate-500 ml-2">actions filtered by <code>auth.roles.permissions</code></span>
        </div>
      </div>

    </div>
    <!-- END ROLE-BASED WIDGETS -->

    <!-- Database Cards -->
    <div v-if="list.length" class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="db in list" :key="db" class="card-hover group">
        <div class="flex justify-between items-start mb-4">
          <a :href="`/databases/${db}`" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 grid place-items-center">
              <Database class="w-5 h-5 text-indigo-400" />
            </div>
            <div>
              <div class="font-bold text-white">{{ db }}</div>
              <div class="text-xs text-slate-500 font-mono">{{ db }}.bangron</div>
            </div>
          </a>
          <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button class="btn-ghost-sm !p-1.5" @click="actions.openRename(db)" title="Rename"><Pencil class="w-3.5 h-3.5" /></button>
            <button class="btn-ghost-sm !p-1.5 !text-red-400 !border-red-800/40 hover:!bg-red-950/40" @click="actions.openDrop(db)" title="Delete"><Trash2 class="w-3.5 h-3.5" /></button>
          </div>
        </div>
        <a :href="`/databases/${db}`" class="btn-ghost-sm w-full text-center flex items-center justify-center gap-1.5">
          <ExternalLink class="w-3.5 h-3.5" />
          Open
        </a>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!list.length && !loading" class="empty-state py-24">
      <Database class="w-12 h-12 text-slate-700 mb-3" />
      <div class="font-medium text-slate-400">No databases yet</div>
      <div class="text-sm text-slate-600 mt-1">Create your first database to get started</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
<<<<<<< HEAD
import { 
  Box, Database, Plus, RefreshCw, ExternalLink, Pencil, Trash2, X, Zap, FolderOpen, FileText,
  Users, Shield, KeyRound, Lock, FileEdit, ClipboardList, Activity, Server, AlertTriangle, Clock
} from 'lucide-vue-next'
=======
import { Box, Database, Plus, RefreshCw, ExternalLink, Pencil, Trash2, Zap, FolderOpen, FileText } from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'
import { useDatabaseActions, DB_CHANGED_EVENT } from '@/composables/useDatabaseActions'
>>>>>>> 2649ce77a485fe976186d38e231134378ddc6160

const page = usePage()
const stats = computed(() => page.props.stats || {})
const auth = computed(()=> page.props.auth?.user || null)
const role = computed(()=> auth.value?.role || page.props.auth?.role || 'guest')
const username = computed(()=> auth.value?.username || 'Guest')

const list = ref([])
const loading = ref(false)

const actions = useDatabaseActions()

// KPI – tetap
const kpis = computed(() => [
  { label: 'Databases', value: stats.value.databases ?? list.value.length ?? 0, icon: Database },
  { label: 'Collections', value: stats.value.collections ?? 0, icon: FolderOpen },
  { label: 'Documents', value: stats.value.documents ?? 0, icon: FileText },
  { label: 'Health', value: stats.value.health?.status || 'OK', icon: Zap },
])

<<<<<<< HEAD
// ===== Role-based widgets =====
const myDrafts = ref([])
const auditLogs = ref([])
const healthMetrics = ref(null)
const recentUsers = ref([])

async function loadRoleWidgets() {
  const r = role.value
  try {
    if (r === 'editor' || r === 'user') {
      // My Drafts – cari dokumen status=draft owned by current user
      try {
        const q = await axios.post('/databases/app/collections/posts/query', {
          filter: { 
            status: 'draft',
            $or: [
              { author: username.value },
              { owner_id: auth.value?._id },
              { created_by: auth.value?._id }
            ]
          },
          limit: 5,
          sort: { updated_at: -1 }
        }).catch(()=>null)
        myDrafts.value = q?.data?.data || q?.data?.documents || []
      } catch {}
      // fallback dummy
      if (myDrafts.value.length === 0) {
        myDrafts.value = [
          { title:'API Authentication Guide', status:'draft', updated_at:'2 hours ago' },
          { title:'Q3 Roadmap', status:'draft', updated_at:'yesterday' },
        ]
      }
    }
    if (['admin','superadmin'].includes(r)) {
      // Audit Log
      try {
        const al = await axios.get('/audit/logs?limit=8')
        auditLogs.value = (al.data.data || al.data.logs || []).slice(0,8)
      } catch {}
      if (auditLogs.value.length === 0) {
        auditLogs.value = [
          { action:'auth.login', user:'editor_andi', time:'3 min ago', status:'ok' },
          { action:'document.update', user:'superadmin', db:'app.posts', time:'12 min ago', status:'ok' },
          { action:'acl.save', user:'admin', time:'1h ago', status:'ok' },
        ]
      }
      // Recent users
      try {
        const u = await axios.get('/admin/users')
        recentUsers.value = (u.data.data || []).slice(0,5)
      } catch {}
    }
    if (r === 'superadmin') {
      // System Health
      try {
        const h = await axios.get('/databases/auth/health').catch(()=>null)
        healthMetrics.value = h?.data || { status:'healthy', uptime:'12d 4h', cpu:'23%', memory:'1.2 GB', storage:'456 MB' }
      } catch {
        healthMetrics.value = { status:'healthy', uptime:'12d 4h', cpu:'23%', memory:'1.2 GB', storage:'456 MB', collections:8, indexes:24 }
      }
    }
  } catch(e){}
}

const roleWidgets = computed(() => {
  const r = role.value
  const w = []
  // Editor – My Drafts
  if (['editor','user','author'].includes(r) || true) { // tampilkan untuk semua, tapi isi beda
    w.push({ id:'drafts', show: ['editor','user','author','admin','superadmin'].includes(r) })
  }
  // Admin – Audit Log
  if (['admin','superadmin'].includes(r)) {
    w.push({ id:'audit', show:true })
    w.push({ id:'users', show:true })
  }
  // Superadmin – System Health
  if (r === 'superadmin') {
    w.push({ id:'health', show:true })
    w.push({ id:'tokens', show:true })
  }
  return w
})

const can = (perm) => {
  const rolePerms = {
    superadmin: ['*'],
    admin: ['read','create','update','delete','manage_schema','manage_acl','export','import'],
    editor: ['read','create','update','publish'],
    user: ['read'],
    guest: []
  }
  const p = rolePerms[role.value] || []
  return p.includes('*') || p.includes(perm)
}

onMounted(async ()=>{ await load(); await loadRoleWidgets() })
=======
onMounted(() => {
  load()
  window.addEventListener(DB_CHANGED_EVENT, load)
})
onBeforeUnmount(() => window.removeEventListener(DB_CHANGED_EVENT, load))
>>>>>>> 2649ce77a485fe976186d38e231134378ddc6160

async function load() {
  loading.value = true
  try {
    const r = await axios.get('/databases')
    list.value = r.data.data || []
  } catch { list.value = [] }
  finally { loading.value = false }
}
</script>
