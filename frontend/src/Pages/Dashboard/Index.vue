<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Page Header -->
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center shadow-lg shadow-indigo-500/20">
        <Box class="w-5 h-5 text-white" />
      </div>
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-white">Overview</h1>
        <p class="text-slate-500 text-sm mt-0.5">System status and quick access to all features</p>
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

    <!-- Quick Access + System -->
    <div class="grid lg:grid-cols-3 gap-4">
      <div class="card lg:col-span-2">
        <h3 class="font-semibold text-white mb-4">Quick Access</h3>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <a v-for="q in quick" :key="q.to" :href="q.to"
             class="group flex flex-col gap-2.5 p-4 rounded-xl border border-white/[0.06]
                    hover:border-white/[0.12] hover:bg-white/[0.02] transition-all duration-200 cursor-pointer">
            <div class="w-9 h-9 rounded-lg bg-indigo-500/10 border border-indigo-500/20 grid place-items-center
                        group-hover:bg-indigo-500/20 transition-colors">
              <component :is="q.icon" class="w-4 h-4 text-indigo-400" />
            </div>
            <div class="font-medium text-sm text-white">{{ q.title }}</div>
            <div class="text-slate-500 text-xs leading-relaxed">{{ q.desc }}</div>
          </a>
        </div>
      </div>

      <div class="card">
        <h3 class="font-semibold text-white mb-4">System</h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500">PHP</span>
            <span class="font-mono text-xs text-slate-300">{{ stats.php_version || '8.1+' }}</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500">Storage</span>
            <span class="font-mono text-xs text-slate-300">{{ stats.total_size_mb ?? '-' }} MB</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500">Encryption</span>
            <span class="badge-success">AES-256-GCM</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500">Engine</span>
            <span class="badge">SQLite</span>
          </div>
        </div>
        <button class="btn-ghost w-full mt-5" @click="refresh">
          <RefreshCw class="w-4 h-4" />
          Refresh
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import {
  Box, Database, FolderOpen, FileText, Search, Key, Puzzle,
  RefreshCw, Users, Shield, Zap
} from 'lucide-vue-next'

const page = usePage()
const stats = computed(() => page.props.stats || {})

const kpis = computed(() => [
  { label: 'Databases', value: stats.value.databases ?? 0, icon: Database },
  { label: 'Collections', value: stats.value.collections ?? 0, icon: FolderOpen },
  { label: 'Documents', value: stats.value.documents ?? 0, icon: FileText },
  { label: 'Health', value: stats.value.health?.status || 'OK', icon: Zap },
])

const quick = [
  { icon: Database, title: 'Databases', desc: 'Create and manage databases', to: '/app/databases' },
  { icon: FileText, title: 'Documents', desc: 'Browse and edit records', to: '/app/documents' },
  { icon: Puzzle, title: 'Schema', desc: 'Design collection schemas', to: '/app/schema' },
  { icon: Search, title: 'Query', desc: 'Build advanced queries', to: '/app/query' },
  { icon: Users, title: 'Users', desc: 'Manage accounts and roles', to: '/app/users' },
  { icon: Shield, title: 'Access', desc: 'Configure permissions', to: '/app/acl' },
]

function refresh() { router.reload({ only: ['stats'] }) }
</script>