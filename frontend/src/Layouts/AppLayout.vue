<template>
  <div class="min-h-screen bg-[#0f1117]">
    <!-- Mobile Sidebar Overlay -->
    <div v-if="mobileOpen" class="sidebar-overlay" @click="mobileOpen=false"></div>

    <!-- ═══ Sidebar ═══ -->
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
          <button class="ml-auto lg:hidden p-1.5 rounded-lg hover:bg-white/5 text-slate-500" @click="mobileOpen=false">
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Database Tree -->
        <nav class="space-y-0.5 flex-1">
          <div class="flex items-center justify-between px-3 py-1">
            <div class="nav-group-label">Databases</div>
            <button class="text-slate-600 hover:text-indigo-400 transition-colors" @click="openCreateDb" title="New Database">
              <Plus class="w-3.5 h-3.5" />
            </button>
          </div>

          <a href="/"
             :class="['nav-item', isActive('/') ? 'active' : '']"
             @click="mobileOpen=false">
            <LayoutDashboard class="nav-item-icon" />
            <span>Overview</span>
          </a>

          <!-- Database list -->
          <div v-for="db in databases" :key="db.name" class="db-tree-item">
            <a :href="`/databases/${db.name}`"
               :class="['nav-item', isDbActive(db.name) ? 'active' : '']"
               @click="toggleDb(db.name); mobileOpen=false">
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
                 @click="mobileOpen=false">
                <FolderOpen class="w-[16px] h-[16px] flex-shrink-0" />
                <span class="truncate">{{ col }}</span>
              </a>
              <div v-if="!db.loading && db.collections.length === 0" class="px-3 py-1.5 text-[11px] text-slate-700">No collections</div>
            </div>
          </div>

          <div v-if="databases.length === 0 && !dbLoading" class="px-3 py-3 text-[11px] text-slate-700 text-center">No databases</div>

          <!-- Access Section -->
          <div class="pt-4 pb-1.5">
            <div class="nav-group-label">Access</div>
          </div>
          <a href="/auth/login"
             :class="['nav-item', isActive('/auth/') ? 'active' : '']"
             @click="mobileOpen=false">
            <Users class="nav-item-icon" />
            <span>Users</span>
          </a>
        </nav>

        <!-- Sidebar Footer -->
        <div class="pt-3 border-t border-white/[0.06] mt-2">
          <div class="px-3 flex items-center justify-between">
            <div class="text-[11px] text-slate-600">v2.0.0</div>
          </div>
        </div>
      </div>
    </aside>

    <!-- ═══ Main Area ═══ -->
    <div class="lg:ml-[250px] min-h-screen flex flex-col">
      <!-- Mobile Top Bar -->
      <header class="sticky top-0 z-30 lg:hidden bg-[#0f1117]/90 backdrop-blur-2xl border-b border-white/[0.07] px-4 py-3 flex items-center gap-3">
        <button class="p-2 -ml-1 rounded-xl hover:bg-white/5 text-slate-400" @click="mobileOpen=true">
          <Menu class="w-5 h-5" />
        </button>
        <div class="flex items-center gap-2.5">
          <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center">
            <Box class="w-3.5 h-3.5 text-white" />
          </div>
          <span class="font-semibold text-sm text-white">{{ pageTitle }}</span>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-5 md:p-6 lg:p-8 pb-24 lg:pb-8 animate-fade-in overflow-y-auto">
        <slot />
      </main>
    </div>

    <!-- Mobile Bottom Nav -->
    <nav class="bottom-nav">
      <a href="/" :class="['bottom-nav-item', isActive('/') ? 'active' : '']">
        <LayoutDashboard />
        <span>Home</span>
      </a>
      <a href="/auth/login" :class="['bottom-nav-item', isActive('/auth/') ? 'active' : '']">
        <Users />
        <span>Users</span>
      </a>
    </nav>

    <!-- Global Toast -->
    <ToastContainer />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import {
  LayoutDashboard, Database, FolderOpen, Users,
  Menu, X, Box, Plus, ChevronRight,
} from 'lucide-vue-next'
import ToastContainer from '@/Components/ToastContainer.vue'

const api = axios.create({ baseURL: '' })
const mobileOpen = ref(false)
const dbLoading = ref(false)
const databases = reactive([])
const expandedDbs = reactive(new Set())

// Expand the current DB based on URL
onMounted(async () => {
  await loadDatabases()
  const path = window.location.pathname
  const m = path.match(/^\/databases\/([^/]+)/)
  if (m) toggleDb(m[1])
})

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

function openCreateDb() {
  // Use Inertia visit or simple prompt for now
  const name = prompt('Database name:')
  if (!name || !/^[a-z0-9_]+$/.test(name)) return
  api.post('/databases', { name }).then(() => loadDatabases()).catch(e => alert(e.response?.data?.message || e.message))
}

const isActive = (href) => {
  if (href === '/') return window.location.pathname === '/'
  return window.location.pathname.startsWith(href)
}

const isDbActive = (name) => window.location.pathname === `/databases/${name}`
const isColActive = (db, col) => window.location.pathname === `/databases/${db}/collections/${col}`

const pageTitle = computed(() => {
  const path = window.location.pathname
  const m = path.match(/^\/databases\/([^/]+)(?:\/collections\/([^/]+))?/)
  if (m) return m[2] ? m[2] : m[1]
  if (path === '/') return 'Overview'
  if (path.startsWith('/auth/')) return 'Auth'
  return 'Bangron Studio'
})
</script>