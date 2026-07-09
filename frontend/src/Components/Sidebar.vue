<template>
  <aside :class="[
    'fixed top-0 left-0 h-screen z-50 overflow-y-auto transition-transform duration-300 ease-out',
    'w-[250px]',
    mobileOpen ? 'translate-x-0' : '-translate-x-full',
    'lg:translate-x-0 lg:sticky lg:z-auto',
    'bg-[#161922]/95 backdrop-blur-2xl border-r border-white/[0.07]'
  ]">
    <div class="p-4 flex flex-col h-full">
      <!-- Brand -->
      <div class="flex items-center gap-3 px-2 mb-4 mt-1">
        <a href="/" class="flex items-center gap-3 min-w-0">
          <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center shadow-lg shadow-indigo-500/20 flex-shrink-0">
            <Box class="w-4 h-4 text-white" />
          </div>
          <div class="min-w-0">
            <div class="font-bold text-[14px] text-white tracking-tight">Bangron Studio</div>
            <div class="text-[10px] text-slate-500 font-medium">Backend Platform</div>
          </div>
        </a>
        <button class="ml-auto lg:hidden p-1.5 rounded-lg hover:bg-white/5 text-slate-500" @click="$emit('close')">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Database Tree -->
      <nav class="space-y-0.5 flex-1">
        <div class="flex items-center justify-between px-3 py-1">
          <div class="nav-group-label">Databases</div>
          <button class="text-slate-600 hover:text-indigo-400 transition-colors" @click="actions.openCreate()" title="New Database">
            <Plus class="w-3.5 h-3.5" />
          </button>
        </div>

        <a href="/"
           :class="['nav-item', isActive('/') ? 'active' : '']"
           @click="$emit('close')">
          <LayoutDashboard class="nav-item-icon" />
          <span>Overview</span>
        </a>

        <!-- Database list -->
        <div v-for="db in databases" :key="db.name" class="db-tree-item">
          <a :href="`/databases/${db.name}`"
             :class="['nav-item', isDbActive(db.name) ? 'active' : '']"
             @click="toggleDb(db.name); $emit('close')">
            <Database class="nav-item-icon" />
            <span class="truncate flex-1">{{ db.name }}</span>
            <ChevronRight :class="['w-3.5 h-3.5 text-slate-600 transition-transform duration-200', expandedDbs.has(db.name) ? 'rotate-90' : '']" />
          </a>

          <!-- Collections under this DB -->
          <div v-if="expandedDbs.has(db.name)" class="ml-5 pl-3 border-l border-white/[0.05] space-y-0.5 mt-0.5 mb-1">
            <div v-if="db.loading" class="px-3 py-2 text-[11px] text-slate-600">Loading...</div>
            <a v-for="col in db.collections" :key="col"
               :href="`/databases/${db.name}/collections/${col}`"
               :class="['nav-item !py-1.5 text-[12px]', isColActive(db.name, col) ? 'active' : '']"
               @click="$emit('close')">
              <FolderOpen class="w-[16px] h-[16px] flex-shrink-0" />
              <span class="truncate">{{ col }}</span>
            </a>
            <div v-if="!db.loading && db.collections.length === 0" class="px-3 py-1.5 text-[11px] text-slate-700">No collections</div>
          </div>
        </div>

        <div v-if="databases.length === 0 && !dbLoading" class="px-3 py-3 text-[11px] text-slate-700 text-center">No databases</div>

        <!-- Access Section -->
        <div class="pt-4 pb-1.5">
          <div class="nav-group-label">Access Control</div>
        </div>
        <a href="/users" :class="['nav-item', isActive('/users') ? 'active' : '']" @click="$emit('close')">
          <Users class="nav-item-icon" /><span>Users</span>
        </a>
        <a href="/roles" :class="['nav-item', isActive('/roles') ? 'active' : '']" @click="$emit('close')">
          <Shield class="nav-item-icon" /><span>Roles</span>
        </a>
        <a href="/permissions" :class="['nav-item', isActive('/permissions') ? 'active' : '']" @click="$emit('close')">
          <KeyRound class="nav-item-icon" /><span>Permissions</span>
        </a>
        <a href="/tokens" :class="['nav-item', isActive('/tokens') ? 'active' : '']" @click="$emit('close')">
          <Lock class="nav-item-icon" /><span>Tokens</span>
        </a>
        <a href="/acl" :class="['nav-item', isActive('/acl') ? 'active' : '']" @click="$emit('close')">
          <ShieldCheck class="nav-item-icon" /><span>ACL</span>
        </a>

        <!-- Auth Section -->
        <div class="pt-3 pb-1">
          <div class="nav-group-label">Auth</div>
        </div>
        <a href="/auth/login" :class="['nav-item', isActive('/auth/') ? 'active' : '']" @click="$emit('close')">
          <LogIn class="nav-item-icon" /><span>Login</span>
        </a>
      </nav>

      <!-- Sidebar Footer: user + logout -->
      <div class="pt-3 border-t border-white/[0.06] mt-2 space-y-2">
        <div v-if="user" class="flex items-center gap-2.5 px-2 py-1.5">
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center text-white text-[12px] font-bold flex-shrink-0">
            {{ initials }}
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-[12px] font-medium text-slate-200 truncate">{{ user.name || user.username }}</div>
            <div class="text-[10px] text-slate-500 truncate capitalize">{{ user.role || 'user' }}</div>
          </div>
          <button class="p-1.5 rounded-lg hover:bg-white/[0.06] text-slate-500 hover:text-red-400 transition-colors" title="Logout" @click="logout">
            <LogOut class="w-4 h-4" />
          </button>
        </div>
        <div v-else class="px-3 text-[11px] text-slate-600">Not signed in</div>
        <div class="px-3 flex items-center justify-between">
          <div class="text-[11px] text-slate-600">v2.1.0-perm</div>
          <div class="text-[9px] text-slate-700">RBAC dynamic</div>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import {
  LayoutDashboard, Database, FolderOpen, Users,
  X, Box, Plus, ChevronRight,
  Shield, KeyRound, Lock, ShieldCheck, LogIn, LogOut,
} from 'lucide-vue-next'
import { useDatabaseActions, DB_CHANGED_EVENT } from '@/composables/useDatabaseActions'

const props = defineProps({
  mobileOpen: { type: Boolean, default: false },
})
defineEmits(['close'])

const page = usePage()
const currentPath = computed(() => page.url || window.location.pathname)

const api = axios.create({ baseURL: '' })
const actions = useDatabaseActions()

const dbLoading = ref(false)
const databases = reactive([])
const expandedDbs = reactive(new Set())

const user = ref(null)
try {
  const raw = localStorage.getItem('user')
  if (raw) user.value = JSON.parse(raw)
} catch {}

const initials = computed(() => {
  if (!user.value) return '?'
  const n = user.value.name || user.value.username || ''
  return n.slice(0, 2).toUpperCase()
})

onMounted(async () => {
  await loadDatabases()
  const m = currentPath.value.match(/^\/databases\/([^/]+)/)
  if (m) toggleDb(m[1])
  window.addEventListener(DB_CHANGED_EVENT, loadDatabases)
})

onBeforeUnmount(() => window.removeEventListener(DB_CHANGED_EVENT, loadDatabases))

async function loadDatabases() {
  dbLoading.value = true
  try {
    const r = await api.get('/databases')
    const names = r.data.data || []
    databases.length = 0
    for (const name of names) {
      databases.push(reactive({ name, collections: [], loading: false }))
    }
  } catch {}
  finally { dbLoading.value = false }
}

async function toggleDb(name) {
  if (expandedDbs.has(name)) return
  expandedDbs.add(name)
  const db = databases.find(d => d.name === name)
  if (!db || db.loading) return
  db.loading = true
  try {
    const r = await api.get(`/databases/${name}/collections`)
    db.collections = r.data.data || []
  } catch { db.collections = [] }
  finally { db.loading = false }
}

async function logout() {
  const token = localStorage.getItem('token')
  try {
    await api.post('/auth/logout', {}, {
      headers: token ? { Authorization: 'Bearer ' + token } : {},
    })
  } catch {}
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  window.location.href = '/auth/login'
}

const isActive = (href) => {
  if (href === '/') return currentPath.value === '/'
  return currentPath.value.startsWith(href)
}
const isDbActive = (name) => currentPath.value === `/databases/${name}`
const isColActive = (db, col) => currentPath.value === `/databases/${db}/collections/${col}`
</script>
