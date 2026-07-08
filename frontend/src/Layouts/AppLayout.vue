<template>
  <div class="min-h-screen bg-[#0f1117]">
    <!-- Mobile Sidebar Overlay -->
    <div v-if="mobileOpen" class="sidebar-overlay" @click="mobileOpen=false"></div>

    <!-- ═══ Sidebar ═══ -->
    <aside :class="[
      'fixed top-0 left-0 h-screen z-50 overflow-y-auto transition-transform duration-300 ease-out',
      'w-[260px]',
      mobileOpen ? 'translate-x-0' : '-translate-x-full',
      'lg:translate-x-0 lg:sticky lg:z-auto',
      'bg-[#161922]/95 backdrop-blur-2xl border-r border-white/[0.07]'
    ]">
      <div class="p-4 flex flex-col h-full">
        <!-- Brand -->
        <div class="flex items-center gap-3 px-2 mb-6 mt-1">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center shadow-lg shadow-indigo-500/20 flex-shrink-0">
            <Box class="w-[18px] h-[18px] text-white" />
          </div>
          <div class="min-w-0">
            <div class="font-bold text-[15px] text-white tracking-tight">Bangron Studio</div>
            <div class="text-[11px] text-slate-500 font-medium">Backend Platform</div>
          </div>
          <button class="ml-auto lg:hidden p-1.5 rounded-lg hover:bg-white/5 text-slate-500" @click="mobileOpen=false">
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Mode Switcher -->
        <div class="mode-switcher mb-5">
          <button :class="['mode-btn', mode === 'editor' ? 'active' : '']" @click="setMode('editor')">
            <PenTool class="w-3.5 h-3.5" />
            Editor
          </button>
          <button :class="['mode-btn', mode === 'developer' ? 'active' : '']" @click="setMode('developer')">
            <Code class="w-3.5 h-3.5" />
            Developer
          </button>
        </div>

        <!-- Navigation -->
        <nav class="space-y-0.5 flex-1">
          <a href="/"
             :class="['nav-item', isActive('/') ? 'active' : '']"
             @click="mobileOpen=false">
            <LayoutDashboard class="nav-item-icon" />
            <span>Overview</span>
          </a>

          <template v-if="mode === 'editor'">
            <div class="pt-5 pb-1.5">
              <div class="nav-group-label">Content</div>
            </div>
            <a v-for="i in editorNav" :key="i.href" :href="i.href"
               :class="['nav-item', isActive(i.href) ? 'active' : '']"
               @click="mobileOpen=false">
              <component :is="i.icon" class="nav-item-icon" />
              <span>{{ i.label }}</span>
            </a>
          </template>

          <template v-else>
            <div class="pt-5 pb-1.5">
              <div class="nav-group-label">Data</div>
            </div>
            <a v-for="i in navData" :key="i.href" :href="i.href"
               :class="['nav-item', isActive(i.href) ? 'active' : '']"
               @click="mobileOpen=false">
              <component :is="i.icon" class="nav-item-icon" />
              <span>{{ i.label }}</span>
            </a>

            <div class="pt-5 pb-1.5">
              <div class="nav-group-label">Access</div>
            </div>
            <a v-for="i in navAccess" :key="i.href" :href="i.href"
               :class="['nav-item', isActive(i.href) ? 'active' : '']"
               @click="mobileOpen=false">
              <component :is="i.icon" class="nav-item-icon" />
              <span>{{ i.label }}</span>
            </a>

            <div class="pt-5 pb-1.5">
              <div class="nav-group-label">System</div>
            </div>
            <a v-for="i in navSystem" :key="i.href" :href="i.href"
               :class="['nav-item', isActive(i.href) ? 'active' : '']"
               @click="mobileOpen=false">
              <component :is="i.icon" class="nav-item-icon" />
              <span>{{ i.label }}</span>
            </a>
          </template>
        </nav>

        <!-- Sidebar Footer -->
        <div class="pt-3 border-t border-white/[0.06] mt-2">
          <div class="px-3 flex items-center justify-between">
            <div class="flex items-center gap-2 text-[11px] text-slate-600">
              <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
              <span>v2.0.0</span>
            </div>
            <div :class="['mode-badge', mode === 'editor' ? 'mode-badge-editor' : 'mode-badge-dev']">
              {{ mode === 'editor' ? 'Editor' : 'Developer' }}
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- ═══ Main Area ═══ -->
    <div class="lg:ml-[260px] min-h-screen flex flex-col">
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
        <div class="ml-auto">
          <button @click="setMode(mode === 'editor' ? 'developer' : 'editor')"
                  class="p-1.5 rounded-lg hover:bg-white/5 text-slate-400">
            <component :is="mode === 'editor' ? Code : PenTool" class="w-4 h-4" />
          </button>
        </div>
      </header>

      <!-- Page Content — pages handle their own headers -->
      <main class="flex-1 p-5 md:p-6 lg:p-8 pb-24 lg:pb-8 animate-fade-in">
        <slot />
      </main>
    </div>

    <!-- Mobile Bottom Nav -->
    <nav class="bottom-nav">
      <template v-if="mode === 'editor'">
        <a v-for="i in editorBottomNav" :key="i.href" :href="i.href"
           :class="['bottom-nav-item', isActive(i.href) ? 'active' : '']">
          <component :is="i.icon" />
          <span>{{ i.label }}</span>
        </a>
      </template>
      <template v-else>
        <a v-for="i in devBottomNav" :key="i.href" :href="i.href"
           :class="['bottom-nav-item', isActive(i.href) ? 'active' : '']">
          <component :is="i.icon" />
          <span>{{ i.label }}</span>
        </a>
      </template>
    </nav>

    <!-- Global Toast Notifications -->
    <ToastContainer />
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import {
  LayoutDashboard, Database, FolderOpen, FileText, Search, Puzzle, Link,
  Users, Shield, KeyRound, Lock, Trash2, Anchor, Zap, Heart, Settings,
  Menu, X, Box, PenTool, Code
} from 'lucide-vue-next'
import ToastContainer from '@/Components/ToastContainer.vue'

const mobileOpen = ref(false)
const mode = ref('developer')

onMounted(() => {
  const saved = localStorage.getItem('bangron_mode')
  if (saved === 'editor' || saved === 'developer') {
    mode.value = saved
  }
})

function setMode(m) {
  mode.value = m
  localStorage.setItem('bangron_mode', m)
}

// Simplified title map — used only for mobile top bar
const titleMap = {
  Dashboard: 'Overview',
  Setup: 'Setup',
  Users: 'Users',
  Roles: 'Roles',
  Tokens: 'Tokens',
  Acl: 'Access Control',
  Databases: 'Databases',
  Collections: 'Collections',
  Documents: 'Documents',
  Query: 'Query',
  Encryption: 'Encryption',
  Schema: 'Schema',
  SoftDeletes: 'Soft Deletes',
  Hooks: 'Hooks',
  Relations: 'Relations',
  Indexes: 'Indexes',
  Health: 'Health',
  Config: 'Settings',
}

const pageTitle = computed(() => {
  const path = typeof window !== 'undefined' ? window.location.pathname : ''
  // Extract page name from /app/xxx or /
  if (path === '/') return 'Overview'
  const match = path.match(/^\/app\/([\w-]+)/)
  const name = match ? match[1].split('-').map(w => w[0].toUpperCase() + w.slice(1)).join('') : ''
  return titleMap[name] || name || 'Bangron Studio'
})

// Editor mode nav
const editorNav = [
  { label: 'Collections', href: '/app/collections', icon: FolderOpen },
  { label: 'Documents',   href: '/app/documents',   icon: FileText },
  { label: 'Users',       href: '/app/users',       icon: Users },
]

// Developer mode nav
const navData = [
  { label: 'Databases',   href: '/app/databases',   icon: Database },
  { label: 'Collections', href: '/app/collections', icon: FolderOpen },
  { label: 'Documents',   href: '/app/documents',   icon: FileText },
  { label: 'Query',       href: '/app/query',       icon: Search },
  { label: 'Schema',      href: '/app/schema',      icon: Puzzle },
  { label: 'Relations',   href: '/app/relations',   icon: Link },
]

const navAccess = [
  { label: 'Users',  href: '/app/users',  icon: Users },
  { label: 'Roles',  href: '/app/roles',  icon: Shield },
  { label: 'Tokens', href: '/app/tokens', icon: KeyRound },
  { label: 'ACL',    href: '/app/acl',    icon: Lock },
]

const navSystem = [
  { label: 'Encryption',   href: '/app/encryption',   icon: KeyRound },
  { label: 'Soft Deletes', href: '/app/soft-deletes', icon: Trash2 },
  { label: 'Hooks',        href: '/app/hooks',        icon: Anchor },
  { label: 'Indexes',      href: '/app/indexes',      icon: Zap },
  { label: 'Health',       href: '/app/health',       icon: Heart },
  { label: 'Settings',     href: '/app/config',       icon: Settings },
]

const editorBottomNav = [
  { label: 'Home',  href: '/',                icon: LayoutDashboard },
  { label: 'Data',  href: '/app/collections', icon: FolderOpen },
  { label: 'Docs',  href: '/app/documents',   icon: FileText },
  { label: 'Users', href: '/app/users',       icon: Users },
  { label: 'More',  href: '/app/config',      icon: Settings },
]

const devBottomNav = [
  { label: 'Home',  href: '/',              icon: LayoutDashboard },
  { label: 'Data',  href: '/app/documents', icon: FileText },
  { label: 'Query', href: '/app/query',     icon: Search },
  { label: 'Users', href: '/app/users',     icon: Users },
  { label: 'More',  href: '/app/config',    icon: Settings },
]

const isActive = (href) => {
  if (typeof window === 'undefined') return false
  if (href === '/') return window.location.pathname === '/'
  return window.location.pathname.startsWith(href)
}
</script>