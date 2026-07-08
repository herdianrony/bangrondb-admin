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
        <div class="flex items-center gap-3 px-2 mb-5 mt-1">
          <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center shadow-lg shadow-indigo-500/20 flex-shrink-0">
            <Box class="w-4 h-4 text-white" />
          </div>
          <div class="min-w-0">
            <div class="font-bold text-[14px] text-white tracking-tight">Bangron Studio</div>
            <div class="text-[10px] text-slate-500 font-medium">Backend Platform</div>
          </div>
          <button class="ml-auto lg:hidden p-1.5 rounded-lg hover:bg-white/5 text-slate-500" @click="mobileOpen=false">
            <X class="w-4 h-4" />
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

          <div class="pt-4 pb-1.5">
            <div class="nav-group-label">Data</div>
          </div>
          <a href="/collections"
             :class="['nav-item', isActive('/collections') ? 'active' : '']"
             @click="mobileOpen=false">
            <FolderOpen class="nav-item-icon" />
            <span>Collections</span>
          </a>
          <a href="/documents"
             :class="['nav-item', isActive('/documents') ? 'active' : '']"
             @click="mobileOpen=false">
            <FileText class="nav-item-icon" />
            <span>Documents</span>
          </a>
          <a href="/databases"
             :class="['nav-item', isActive('/databases') ? 'active' : '']"
             @click="mobileOpen=false">
            <Database class="nav-item-icon" />
            <span>Databases</span>
          </a>

          <div class="pt-4 pb-1.5">
            <div class="nav-group-label">Access</div>
          </div>
          <a href="/users"
             :class="['nav-item', isActive('/users') ? 'active' : '']"
             @click="mobileOpen=false">
            <Users class="nav-item-icon" />
            <span>Users</span>
          </a>
          <a href="/roles"
             :class="['nav-item', isActive('/roles') ? 'active' : '']"
             @click="mobileOpen=false">
            <Shield class="nav-item-icon" />
            <span>Roles</span>
          </a>

          <div class="pt-4 pb-1.5">
            <div class="nav-group-label">Developer</div>
          </div>
          <a href="/schema"
             :class="['nav-item', isActive('/schema') ? 'active' : '']"
             @click="mobileOpen=false">
            <Puzzle class="nav-item-icon" />
            <span>Schema</span>
          </a>
          <a href="/query"
             :class="['nav-item', isActive('/query') ? 'active' : '']"
             @click="mobileOpen=false">
            <Search class="nav-item-icon" />
            <span>Query</span>
          </a>
          <a href="/acl"
             :class="['nav-item', isActive('/acl') ? 'active' : '']"
             @click="mobileOpen=false">
            <Lock class="nav-item-icon" />
            <span>ACL</span>
          </a>
          <a href="/tokens"
             :class="['nav-item', isActive('/tokens') ? 'active' : '']"
             @click="mobileOpen=false">
            <KeyRound class="nav-item-icon" />
            <span>Tokens</span>
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
      <main class="flex-1 p-5 md:p-6 lg:p-8 pb-24 lg:pb-8 animate-fade-in">
        <slot />
      </main>
    </div>

    <!-- Mobile Bottom Nav -->
    <nav class="bottom-nav">
      <a href="/" :class="['bottom-nav-item', isActive('/') ? 'active' : '']">
        <LayoutDashboard />
        <span>Home</span>
      </a>
      <a href="/collections" :class="['bottom-nav-item', isActive('/collections') ? 'active' : '']">
        <FolderOpen />
        <span>Data</span>
      </a>
      <a href="/documents" :class="['bottom-nav-item', isActive('/documents') ? 'active' : '']">
        <FileText />
        <span>Docs</span>
      </a>
      <a href="/users" :class="['bottom-nav-item', isActive('/users') ? 'active' : '']">
        <Users />
        <span>Users</span>
      </a>
      <a href="/schema" :class="['bottom-nav-item', isActive('/schema') ? 'active' : '']">
        <Settings />
        <span>More</span>
      </a>
    </nav>

    <!-- Global Toast Notifications -->
    <ToastContainer />
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import {
  LayoutDashboard, Database, FolderOpen, FileText, Search, Puzzle,
  Users, Shield, KeyRound, Lock, Settings,
  Menu, X, Box
} from 'lucide-vue-next'
import ToastContainer from '@/Components/ToastContainer.vue'

const mobileOpen = ref(false)

const titleMap = {
  '/': 'Overview',
  '/collections': 'Collections',
  '/documents': 'Documents',
  '/databases': 'Databases',
  '/users': 'Users',
  '/roles': 'Roles',
  '/tokens': 'Tokens',
  '/acl': 'Access Control',
  '/schema': 'Schema',
  '/query': 'Query',
  '/config': 'Settings',
  '/health': 'Health',
  '/encryption': 'Encryption',
  '/indexes': 'Indexes',
  '/soft-deletes': 'Soft Deletes',
  '/hooks': 'Hooks',
  '/relations': 'Relations',
  '/setup': 'Setup',
}

const pageTitle = computed(() => {
  const path = typeof window !== 'undefined' ? window.location.pathname : '/'
  return titleMap[path] || 'Bangron Studio'
})

const isActive = (href) => {
  if (typeof window === 'undefined') return false
  if (href === '/') return window.location.pathname === '/'
  return window.location.pathname.startsWith(href)
}
</script>