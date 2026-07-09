<template>
<<<<<<< HEAD
  <div class="min-h-screen bg-[#0e1116] text-slate-200 antialiased">
    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 h-screen w-[260px] bg-[#141824]/95 backdrop-blur-xl border-r border-white/[0.06] z-40 hidden lg:flex flex-col">
      <!-- Brand -->
      <div class="h-[56px] px-4 flex items-center gap-3 border-b border-white/[0.06] flex-shrink-0">
        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center shadow-lg shadow-indigo-900/30">
          <Box :size="16" class="text-white"/>
        </div>
        <div class="min-w-0">
          <div class="font-[650] text-[14px] text-white tracking-tight">Bangron Studio</div>
          <div class="text-[10px] text-slate-500 -mt-0.5">v2.3 • RBAC</div>
        </div>
      </div>

      <div class="flex-1 overflow-y-auto py-4 px-3 space-y-5">
        <!-- General -->
        <div>
          <div class="nav-section">General</div>
          <nav class="space-y-1">
            <a href="/" :class="navCls('/')"> 
              <LayoutDashboard :size="16"/> <span>Dashboard</span>
            </a>
          </nav>
        </div>

        <!-- Content -->
        <div>
          <div class="flex items-center justify-between pr-2">
            <div class="nav-section">Content</div>
            <button v-if="can('create')" @click="openCreateDb" class="text-slate-500 hover:text-indigo-300"><Plus :size="13"/></button>
          </div>
          <div class="space-y-1">
            <div v-if="databases.length===0" class="px-3 py-2 text-[11px] text-slate-600">no databases</div>
            <div v-for="db in databases" :key="db.name">
              <a :href="`/databases/${db.name}`"
                 :class="[navCls(`/databases/${db.name}`),'justify-between']"
                 @click.prevent="toggleDb(db.name)">
                <span class="flex items-center gap-2 truncate"><Database :size="14"/> {{ db.name }}</span>
                <ChevronRight :size="12" :class="['text-slate-600 transition', expandedDbs.has(db.name)?'rotate-90':'']"/>
              </a>
              <div v-if="expandedDbs.has(db.name)" class="ml-4 pl-3 border-l border-white/[0.05] space-y-0.5 my-1">
                <a v-for="c in db.collections" :key="c"
                   :href="`/databases/${db.name}/collections/${c}`"
                   :class="['nav-item !text-[12px] !py-1.5', isColActive(db.name,c)?'!bg-indigo-500/10 !text-indigo-300':'']">
                  <FileText :size="13"/> {{ c }}
                </a>
                <div v-if="db.collections?.length===0" class="text-[10px] text-slate-600 px-2 py-1">empty</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Access Control – RBAC filtered -->
        <div v-if="showAccess">
          <div class="nav-section">Access Control
            <span class="ml-1 text-[9px] px-1.5 py-0.5 rounded bg-indigo-500/10 text-indigo-300">{{ userRole }}</span>
          </div>
          <nav class="space-y-1">
            <a v-for="n in accessNav" :key="n.href" :href="n.href" :class="navCls(n.href)">
              <component :is="n.icon" :size="15"/> 
              <span>{{ n.label }}</span>
              <span v-if="n.badge" class="ml-auto text-[9px] px-1.5 py-0.5 rounded-full bg-amber-500/10 text-amber-300">{{ n.badge }}</span>
            </a>
          </nav>
        </div>

        <!-- Workflow – editor+ -->
        <div v-if="editorNav.length">
          <div class="nav-group-label">Workflow</div>
          <nav class="space-y-1">
            <a v-for="n in editorNav" :key="n.href" :href="n.href" :class="navCls(n.href)">
              <component :is="n.icon" :size="15"/> <span>{{ n.label }}</span>
            </a>
          </nav>
        </div>

        <!-- System – superadmin -->
        <div v-if="adminNav.length">
          <div class="nav-group-label">System</div>
          <nav class="space-y-1">
            <a v-for="n in adminNav" :key="n.href" :href="n.href" :class="navCls(n.href)">
              <component :is="n.icon" :size="15"/> <span>{{ n.label }}</span>
              <span v-if="n.ping" class="ml-auto w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
            </a>
          </nav>
        </div>
      </div>

      <!-- User pill footer -->
      <div class="p-3 border-t border-white/[0.06] bg-black/10">
        <div v-if="authUser" class="flex items-center gap-2 text-xs">
          <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center text-[11px] font-bold text-white">
            {{ (authUser.username||'U').slice(0,2).toUpperCase() }}
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-white truncate text-[12px]">{{ authUser.username }}</div>
            <div class="text-[10px] text-slate-400">{{ authUser.role || userRole }} • session</div>
          </div>
          <button @click="doLogout" class="text-slate-500 hover:text-red-300" title="Logout">
            <LogOut :size="14"/>
          </button>
        </div>
        <a v-else href="/auth/login" class="btn w-full text-xs !py-2">Sign in</a>
      </div>
    </aside>

    <!-- Main -->
    <div class="lg:pl-[260px] min-h-screen flex flex-col">
      <!-- Topbar -->
      <header class="h-[56px] sticky top-0 z-30 bg-[#0f1117]/85 backdrop-blur-xl border-b border-white/[0.06] flex items-center px-4 lg:px-6 gap-4">
        <button class="lg:hidden p-2 -ml-2 text-slate-400" @click="mobileOpen=true"><Menu :size="18"/></button>
        
        <!-- breadcrumbs -->
        <nav class="text-[12px] text-slate-400 hidden sm:flex items-center gap-1.5">
          <a href="/" class="hover:text-slate-200">Home</a>
          <span class="text-slate-600">/</span>
          <span class="text-slate-200">{{ breadcrumb }}</span>
        </nav>

        <div class="flex-1"></div>

        <!-- search -->
        <button class="hidden md:flex items-center gap-2 bg-[#161a22] border border-white/[0.07] rounded-xl px-3 py-1.5 text-[12px] text-slate-400 hover:border-slate-600 w-72"
          @click="openCommand=true">
          <Search :size="14"/> 
          <span class="flex-1 text-left">Search collections, docs…</span>
          <kbd class="text-[10px] bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700">⌘K</kbd>
        </button>

        <!-- API key indicator -->
        <div class="hidden lg:flex items-center gap-1.5 text-[10px] px-2 py-1 rounded-full bg-emerald-500/8 border border-emerald-500/15 text-emerald-300"
             v-if="authUser" title="Session authenticated">
          <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full"></span>
          {{ userRole }}
        </div>

        <!-- user menu -->
        <div class="relative" v-if="authUser">
          <button @click="userMenu=!userMenu" class="flex items-center gap-2 hover:opacity-90">
            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center text-[11px] font-bold text-white">
              {{ (authUser.username||'U').slice(0,1).toUpperCase() }}
            </div>
            <ChevronDown :size="12" class="text-slate-500 hidden sm:block"/>
          </button>
          <div v-if="userMenu" @click.outside="userMenu=false"
            class="absolute right-0 mt-2 w-56 bg-[#181c25] border border-white/[0.08] rounded-2xl shadow-2xl overflow-hidden text-sm z-50">
            <div class="px-3 py-2.5 border-b border-white/[0.06]">
              <div class="text-white font-medium">{{ authUser.name || authUser.username }}</div>
              <div class="text-[11px] text-slate-400">{{ authUser.email || '' }}</div>
              <div class="text-[10px] text-indigo-300 mt-1">role: {{ userRole }} • single relation</div>
            </div>
            <a href="/tokens" class="block px-3 py-2 hover:bg-white/[0.04]">🔐 API Keys & Tokens</a>
            <a href="/users" v-if="['admin','superadmin'].includes(userRole)" class="block px-3 py-2 hover:bg-white/[0.04]">👥 User Management</a>
            <div class="border-t border-white/[0.06]"></div>
            <button @click="doLogout" class="w-full text-left px-3 py-2 hover:bg-red-950/30 text-red-300">Sign out</button>
          </div>
        </div>
        <a v-else href="/auth/login" class="btn-sm">Sign in</a>
      </header>
=======
  <div class="min-h-screen bg-[#0f1117]">
    <!-- Mobile Sidebar Overlay -->
    <div v-if="mobileOpen" class="sidebar-overlay" @click="mobileOpen = false"></div>

    <!-- ═══ Sidebar ═══ -->
    <Sidebar :mobile-open="mobileOpen" @close="mobileOpen = false" />

    <!-- ═══ Main Area ═══ -->
    <div class="lg:ml-[250px] min-h-screen flex flex-col">
      <!-- Mobile Top Bar -->
      <MobileHeader @open="mobileOpen = true" />
>>>>>>> 2649ce77a485fe976186d38e231134378ddc6160

      <!-- Page -->
      <main class="flex-1 px-4 lg:px-8 py-6 max-w-7xl w-full mx-auto">
        <slot />
      </main>

      <!-- Footer -->
      <footer class="border-t border-white/[0.05] px-6 py-3 text-[11px] text-slate-600 flex justify-between">
        <span>Bangron Studio v2.3 Clarity • RBAC user→role→resource→action</span>
        <span>Auth: <b class="text-emerald-400" v-if="authUser">session</b><b v-else class="text-slate-500">guest</b> • {{ userRole }}</span>
      </footer>
    </div>

<<<<<<< HEAD
    <!-- Mobile drawer -->
    <div v-if="mobileOpen" class="fixed inset-0 z-50 lg:hidden">
      <div class="absolute inset-0 bg-black/60" @click="mobileOpen=false"></div>
      <div class="absolute left-0 top-0 h-full w-[300px] bg-[#141824] border-r border-white/[0.08] overflow-y-auto">
        <!-- duplicate nav simplified -->
        <div class="p-4">
          <div class="flex items-center justify-between mb-4">
            <b class="text-white">Bangron Studio</b>
            <button @click="mobileOpen=false"><X :size="18"/></button>
          </div>
          <nav class="space-y-1 text-sm">
            <a v-for="n in [...accessNav, ...editorNav, ...adminNav]" :key="n.href" :href="n.href"
               class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-white/[0.05]">
              <component :is="n.icon" :size="15"/> {{ n.label }}
            </a>
          </nav>
        </div>
      </div>
    </div>
=======
    <!-- Mobile Bottom Nav -->
    <BottomNav />

    <!-- Database action modals (create / rename / drop) -->
    <DatabaseModals />

    <!-- Global confirm / prompt dialogs -->
    <GlobalDialogs />
>>>>>>> 2649ce77a485fe976186d38e231134378ddc6160

    <!-- Command palette ⌘K -->
    <div v-if="openCommand" class="fixed inset-0 z-[60] bg-black/50 backdrop-blur-sm flex items-start justify-center pt-[20vh] p-4"
         @click.self="openCommand=false">
      <div class="w-full max-w-lg bg-[#181c25] border border-white/[0.1] rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-center px-4 border-b border-white/[0.06]">
          <Search :size="16" class="text-slate-500 mr-2"/>
          <input ref="cmdInput" v-model="cmdQ" placeholder="Go to users, roles, database, collection…"
            class="flex-1 bg-transparent py-3 outline-none text-slate-100 placeholder-slate-500"/>
          <kbd class="text-[10px] text-slate-500">ESC</kbd>
        </div>
        <div class="max-h-80 overflow-auto py-1 text-sm">
          <button v-for="r in cmdResults" :key="r.href"
            @click="go(r.href)"
            class="w-full text-left px-4 py-2.5 hover:bg-white/[0.04] flex items-center gap-3">
            <component :is="r.icon" :size="15" class="text-slate-400"/>
            <div>
              <div class="text-slate-100">{{ r.label }}</div>
              <div class="text-[11px] text-slate-500">{{ r.desc }}</div>
            </div>
            <span class="ml-auto text-[10px] text-slate-600">{{ r.shortcut }}</span>
          </button>
        </div>
        <div class="px-4 py-2 text-[10px] text-slate-500 border-t border-white/[0.06]">
          Navigate by role • {{ userRole }} • {{ filteredNavCount }} items accessible
        </div>
      </div>
    </div>

    <ToastContainer/>
  </div>
</template>

<script setup>
<<<<<<< HEAD
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import axios from 'axios'
import {
  LayoutDashboard, Database, FileText, FolderOpen,
  Users, Shield, KeyRound, Lock, ShieldCheck,
  FileEdit, ClipboardList, Activity, Settings,
  Box, Menu, X, Plus, ChevronRight, ChevronDown, Search, LogOut, LogIn
} from 'lucide-vue-next'
import ToastContainer from '@/Components/ToastContainer.vue'

const mobileOpen = ref(false)
const userMenu = ref(false)
const openCommand = ref(false)
const cmdQ = ref('')
const cmdInput = ref(null)

const authUser = ref(null)
const userRole = computed(()=> authUser.value?.role || authUser.value?.roles?.[0] || 'guest')

const databases = reactive([])
const expandedDbs = reactive(new Set())

// RBAC nav – single source of truth
const allNav = [
  // Access Control – admin+
  { label:'Users', href:'/users', icon:Users, roles:['admin','superadmin'], perm:'manage_users' },
  { label:'Roles', href:'/roles', icon:Shield, roles:['admin','superadmin'], perm:'manage_acl' },
  { label:'Permissions', href:'/permissions', icon:KeyRound, roles:['superadmin','admin'], perm:'manage_acl', badge:'NEW' },
  { label:'Tokens', href:'/tokens', icon:Lock, roles:['editor','admin','superadmin','user'], perm:'read' },
  { label:'ACL', href:'/acl', icon:ShieldCheck, roles:['admin','superadmin'], perm:'manage_acl' },
  // Editor
  { label:'My Drafts', href:'/drafts', icon:FileEdit, roles:['editor','admin','superadmin'], group:'editor' },
  // Admin
  { label:'Audit Log', href:'/audit', icon:ClipboardList, roles:['admin','superadmin'], group:'admin' },
  { label:'Health', href:'/health', icon:Activity, roles:['superadmin'], group:'admin', ping:true },
  { label:'Config', href:'/config', icon:Settings, roles:['admin','superadmin'], group:'admin' },
]

const canSee = (item) => {
  if(!item.roles) return true
  return item.roles.includes(userRole.value) || userRole.value==='superadmin'
}
const accessNav = computed(()=> allNav.filter(n=> ['Users','Roles','Permissions','Tokens','ACL'].includes(n.label) && canSee(n)))
const editorNav = computed(()=> allNav.filter(n=> n.group==='editor' && canSee(n)))
const adminNav  = computed(()=> allNav.filter(n=> n.group==='admin' && canSee(n)))
const filteredNavCount = computed(()=> accessNav.value.length + editorNav.value.length + adminNav.value.length)

// load auth
onMounted(async ()=>{
  try{
    const r = await axios.get('/auth/me', {withCredentials:true})
    authUser.value = r.data.user || r.data
  }catch{
    try{
      const token = localStorage.getItem('token')
      if(token){
        axios.defaults.headers.common['Authorization']='Bearer '+token
        const r2 = await axios.get('/auth/me')
        authUser.value = r2.data.user
      }
    }catch{}
  }
  loadDatabases()
  // ⌘K
  const onKey = (e)=>{ if((e.metaKey||e.ctrlKey) && e.key.toLowerCase()==='k'){ e.preventDefault(); openCommand.value=true; nextTick(()=>cmdInput.value?.focus()) } if(e.key==='Escape') openCommand.value=false }
  window.addEventListener('keydown', onKey)
  onUnmounted(()=> window.removeEventListener('keydown', onKey))
})

async function loadDatabases(){
  try {
    const r = await axios.get('/databases', {withCredentials:true})
    const names = r.data.data || []
    databases.splice(0)
    names.forEach(n=> databases.push(reactive({name:n, collections:[], loading:false})))
  } catch {}
}

async function toggleDb(name){
  if(expandedDbs.has(name)){ expandedDbs.delete(name); return }
  expandedDbs.add(name)
  const db = databases.find(d=>d.name===name)
  if(!db || db.loading) return
  db.loading=true
  try{
    const r = await axios.get(`/databases/${name}/collections`, {withCredentials:true})
    db.collections = r.data.data || []
  }catch{ db.collections=[] }
  finally{ db.loading=false }
}

async function doLogout(){
  try{ await axios.post('/logout', {}, {withCredentials:true}) }catch{}
  try{ await axios.post('/auth/logout') }catch{}
  localStorage.removeItem('token')
  window.location.href='/auth/login'
}

function openCreateDb(){
  const name = prompt('Database name (a-z0-9_):')
  if(!name || !/^[a-z0-9_]+$/.test(name)) return
  axios.post('/databases', {name}, {withCredentials:true}).then(loadDatabases).catch(e=>alert(e.response?.data?.message||e.message))
}

const isActive = (href)=> href==='/' ? window.location.pathname==='/' : window.location.pathname.startsWith(href)
const isColActive = (db,col)=> window.location.pathname===`/databases/${db}/collections/${col}`

// command palette results – RBAC filtered
const cmdResults = computed(()=>{
  const q = cmdQ.value.toLowerCase()
  const base = [
    {label:'Dashboard', href:'/', icon:LayoutDashboard, desc:'Overview & KPI', shortcut:'⌘1', roles:['guest','user','editor','admin','superadmin']},
    {label:'Users', href:'/users', icon:Users, desc:'Manage auth.users', shortcut:'', roles:['admin','superadmin']},
    {label:'Roles', href:'/roles', icon:Shield, desc:'auth.roles', shortcut:'', roles:['admin','superadmin']},
    {label:'Permissions', href:'/permissions', icon:KeyRound, desc:'auth.permissions – dynamic', shortcut:'', roles:['admin','superadmin']},
    {label:'Tokens & API Keys', href:'/tokens', icon:Lock, desc:'Refresh tokens + API keys', shortcut:'', roles:['editor','admin','superadmin','user']},
    {label:'ACL Matrix', href:'/acl', icon:ShieldCheck, desc:'Test access', shortcut:'', roles:['admin','superadmin']},
    ...databases.flatMap(d=> [
      {label:`DB: ${d.name}`, href:`/databases/${d.name}`, icon:Database, desc:'Open database', roles:['user','editor','admin','superadmin']},
      ...(d.collections||[]).map(c=>({label:`${d.name}.${c}`, href:`/databases/${d.name}/collections/${c}`, icon:FileText, desc:'Collection', roles:['user','editor','admin','superadmin']}))
    ])
  ]
  return base.filter(r => 
    r.roles.includes(userRole.value) &&
    (!q || r.label.toLowerCase().includes(q) || r.desc.toLowerCase().includes(q))
  ).slice(0,12)
})
function go(href){ openCommand.value=false; window.location.href=href }

const breadcrumb = computed(()=>{
  const p = window.location.pathname
  if(p==='/') return 'Dashboard'
  if(p.startsWith('/users')) return 'Access / Users'
  if(p.startsWith('/roles')) return 'Access / Roles'
  if(p.startsWith('/permissions')) return 'Access / Permissions'
  if(p.startsWith('/tokens')) return 'Access / Tokens'
  if(p.startsWith('/acl')) return 'Access / ACL'
  const m = p.match(/^\/databases\/([^/]+)(?:\/collections\/([^/]+))?/)
  if(m) return m[2] ? `${m[1]} / ${m[2]}` : m[1]
  return p.replace(/^\//,'') || 'Dashboard'
})
</script>

<style>
/* Design System v3 – Clarity */
@reference "tailwindcss";
.nav-section { @apply text-[10px] uppercase tracking-wider text-slate-500 px-3 mb-1.5 font-[600]; }
.nav-group-label { @apply text-[10px] uppercase tracking-wider text-slate-500 px-3; }
.nav-item { @apply flex items-center gap-2.5 px-3 py-2 mx-1 rounded-xl text-[13px] text-slate-300 hover:bg-white/[0.04] hover:text-white transition-all; }
.nav-item.active { @apply bg-indigo-500/10 text-indigo-300 border border-indigo-500/15; }
.nav-item-icon { @apply w-[16px] h-[16px] opacity-80; }
.card { @apply bg-[#181c25]/90 backdrop-blur border border-white/[0.07] rounded-2xl p-5 shadow-sm; }
.card-hover { @apply card hover:border-white/[0.12] transition; }
.btn { @apply px-3.5 py-2 rounded-xl text-[13px] font-[550] bg-gradient-to-r from-indigo-500 to-violet-600 text-white shadow hover:opacity-95 active:scale-[0.98] transition; }
.btn-ghost { @apply px-3 py-2 rounded-xl text-[13px] border border-white/[0.1] text-slate-300 hover:bg-white/[0.04]; }
.btn-sm { @apply px-3 py-1.5 rounded-xl text-[12px] font-[550] bg-indigo-600 text-white; }
.btn-ghost-sm { @apply px-2.5 py-1.5 rounded-lg text-[12px] border border-white/[0.1] text-slate-300 hover:bg-white/[0.04]; }
.input { @apply w-full bg-[#0f131c] border border-white/[0.1] rounded-xl px-3 py-2 text-[13px] text-slate-100 outline-none focus:border-indigo-500/60 focus:ring-2 focus:ring-indigo-500/15; }
.badge { @apply text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700; }
.data-table { @apply w-full text-[13px]; }
.data-table th { @apply text-left text-[11px] uppercase tracking-wider text-slate-500 font-[600] py-2 border-b border-white/[0.07]; }
.data-table td { @apply py-2.5 border-b border-white/[0.04]; }
.code-block { @apply bg-black/40 border border-white/[0.06] rounded-xl p-3 text-[11px] font-mono text-indigo-200 overflow-auto; }

/* bottom nav mobile */
.bottom-nav { @apply fixed lg:hidden bottom-0 inset-x-0 bg-[#141824]/95 backdrop-blur-xl border-t border-white/[0.06] flex justify-around py-1.5 z-40; }
.bottom-nav-item { @apply flex flex-col items-center text-[10px] text-slate-500 px-3 py-1 rounded-lg; }
.bottom-nav-item.active { @apply text-indigo-300; }
.bottom-nav-item svg { @apply w-[18px] h-[18px] mb-0.5; }

/* animations */
@keyframes fade-in { from{opacity:0; transform:translateY(4px)} to{opacity:1; transform:translateY(0)} }
.animate-fade-in { animation:fade-in .25s ease-out; }
@keyframes scale-in { from{opacity:0; transform:scale(.97)} to{opacity:1; transform:scale(1)} }
.animate-scale-in { animation:scale-in .2s ease-out; }
</style>
=======
import { ref } from 'vue'
import Sidebar from '@/Components/Sidebar.vue'
import MobileHeader from '@/Components/MobileHeader.vue'
import BottomNav from '@/Components/BottomNav.vue'
import DatabaseModals from '@/Components/DatabaseModals.vue'
import GlobalDialogs from '@/Components/GlobalDialogs.vue'
import ToastContainer from '@/Components/ToastContainer.vue'

const mobileOpen = ref(false)
</script>
>>>>>>> 2649ce77a485fe976186d38e231134378ddc6160
