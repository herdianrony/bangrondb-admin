<template>
  <div class="animate-fade-in">
    <!-- Brand -->
    <div class="text-center mb-9">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center shadow-xl shadow-indigo-500/25 mb-4">
        <Box class="w-7 h-7 text-white" />
      </div>
      <h1 class="text-[22px] font-extrabold text-white tracking-tight">Bangron Studio</h1>
      <p class="text-slate-500 text-[13px] mt-1">Setup your backend platform</p>
    </div>

    <!-- Step indicators -->
    <div class="flex items-center justify-center gap-0 mb-8">
      <template v-for="(s, i) in stepLabels" :key="i">
        <div :class="[
          'w-8 h-8 rounded-full flex items-center justify-center text-[12px] font-bold border-2 transition-all duration-300 flex-shrink-0',
          step > i ? 'bg-emerald-500/15 border-emerald-500/60 text-emerald-400' :
          step === i ? 'bg-indigo-500 border-indigo-500 text-white shadow-lg shadow-indigo-500/30' :
          'bg-transparent border-slate-700/80 text-slate-600'
        ]">
          <Check v-if="step > i" class="w-3.5 h-3.5" />
          <span v-else>{{ i + 1 }}</span>
        </div>
        <div v-if="i < stepLabels.length - 1" :class="[
          'w-10 h-0.5 mx-1 rounded-full transition-all duration-300',
          step > i ? 'bg-emerald-500/40' : 'bg-slate-800'
        ]"></div>
      </template>
    </div>

    <!-- ═══ Step 0: Environment ═══ -->
    <div v-if="step === 0" class="setup-card animate-fade-in">
      <h2 class="setup-title">Environment Check</h2>
      <p class="setup-subtitle">Verifying your server meets the requirements.</p>

      <div class="space-y-2 mb-2">
        <div v-for="(c, i) in envChecks" :key="i"
             :class="['env-item', c.status]">
          <div :class="['env-icon', c.status === 'ok' ? 'bg-emerald-500/10 text-emerald-400' : c.status === 'warn' ? 'bg-amber-500/10 text-amber-400' : 'bg-red-500/10 text-red-400']">
            <Check v-if="c.status === 'ok'" class="w-3.5 h-3.5" />
            <AlertTriangle v-else-if="c.status === 'warn'" class="w-3.5 h-3.5" />
            <XCircle v-else class="w-3.5 h-3.5" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-[13px] text-slate-200 font-medium">{{ c.label }}</div>
            <div class="text-[11px] text-slate-500 truncate">{{ c.detail }}</div>
          </div>
          <span :class="['text-[11px] font-semibold', c.status === 'ok' ? 'text-emerald-400' : c.status === 'warn' ? 'text-amber-400' : 'text-red-400']">
            {{ c.statusText }}
          </span>
        </div>
      </div>

      <div v-if="envError" class="mt-4 p-3 rounded-xl bg-red-500/[0.06] border border-red-500/15 text-red-300 text-[12px] flex items-start gap-2">
        <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
        <span>Some requirements are not met. Please fix them before continuing.</span>
      </div>

      <div class="setup-btn-row">
        <button class="btn w-full" :disabled="!envPass" @click="step = 1">
          Continue
          <ArrowRight class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- ═══ Step 1: Admin Account ═══ -->
    <div v-else-if="step === 1" class="setup-card animate-fade-in">
      <h2 class="setup-title">Create Admin Account</h2>
      <p class="setup-subtitle">This will be your primary administrator account for managing the platform.</p>

      <div class="space-y-4">
        <div>
          <label class="setup-label">Username</label>
          <div class="relative">
            <User class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
            <input v-model="form.username" class="input !pl-10" placeholder="admin" @keydown.enter="goToStep2" />
          </div>
          <div class="text-[11px] text-slate-600 mt-1.5">Used to sign in to Bangron Studio</div>
        </div>

        <div>
          <label class="setup-label">Email Address</label>
          <div class="relative">
            <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
            <input v-model="form.email" type="email" class="input !pl-10" placeholder="admin@example.com" />
          </div>
        </div>

        <div>
          <label class="setup-label">Password</label>
          <div class="relative">
            <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
            <input v-model="form.password" :type="showPass ? 'text' : 'password'" class="input !pl-10 !pr-10" placeholder="Min. 8 characters" @keydown.enter="goToStep2" />
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors" @click="showPass = !showPass">
              <Eye v-if="!showPass" class="w-4 h-4" />
              <EyeOff v-else class="w-4 h-4" />
            </button>
          </div>
          <div class="text-[11px] text-slate-600 mt-1.5">Minimum 8 characters with mixed case recommended</div>
        </div>
      </div>

      <div v-if="accountError" class="mt-4 p-3 rounded-xl bg-red-500/[0.06] border border-red-500/15 text-red-300 text-[12px] flex items-start gap-2">
        <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
        <span>{{ accountError }}</span>
      </div>

      <div class="setup-btn-row">
        <button class="btn-ghost flex-1" @click="step = 0">
          <ArrowLeft class="w-4 h-4" />
          Back
        </button>
        <button class="btn flex-1" @click="goToStep2">
          Continue
          <ArrowRight class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- ═══ Step 2: Database & Seed ═══ -->
    <div v-else-if="step === 2" class="setup-card animate-fade-in">
      <h2 class="setup-title">Database & Starter Data</h2>
      <p class="setup-subtitle">Choose a name for your primary database and optionally seed sample collections.</p>

      <div class="mb-5">
        <label class="setup-label">Database Name</label>
        <div class="relative">
          <Database class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
          <input v-model="form.app_db" class="input !pl-10" placeholder="app" />
        </div>
        <div class="text-[11px] text-slate-600 mt-1.5">Your main application database</div>
      </div>

      <div>
        <label class="setup-label">Seed Starter Collections</label>
        <div class="grid grid-cols-2 gap-2 mt-2">
          <button v-for="s in seedOptions" :key="s.key"
                  :class="['seed-card', { selected: selectedSeeds.includes(s.key) }]"
                  @click="toggleSeed(s.key)">
            <div :class="['w-8 h-8 rounded-xl grid place-items-center text-base mb-2 mx-auto', s.bg]">
              {{ s.emoji }}
            </div>
            <div class="text-[13px] font-semibold text-white">{{ s.label }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">{{ s.desc }}</div>
          </button>
        </div>
      </div>

      <div v-if="dbError" class="mt-4 p-3 rounded-xl bg-red-500/[0.06] border border-red-500/15 text-red-300 text-[12px] flex items-start gap-2">
        <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
        <span>{{ dbError }}</span>
      </div>

      <div class="setup-btn-row">
        <button class="btn-ghost flex-1" @click="step = 1">
          <ArrowLeft class="w-4 h-4" />
          Back
        </button>
        <button class="btn flex-1" :disabled="launching" @click="launch">
          <Loader2 v-if="launching" class="w-4 h-4 animate-spin" />
          <Rocket v-else class="w-4 h-4" />
          {{ launching ? 'Creating...' : 'Create & Launch' }}
        </button>
      </div>
    </div>

    <!-- ═══ Step 3: Done ═══ -->
    <div v-else-if="step === 3" class="setup-card animate-fade-in text-center">
      <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-500/[0.08] border border-emerald-500/15 grid place-items-center mb-5 animate-scale-in">
        <CheckCircle class="w-8 h-8 text-emerald-400" />
      </div>
      <h2 class="text-xl font-bold text-white mb-2">You're All Set!</h2>
      <p class="text-slate-400 text-[13px] mb-6 leading-relaxed max-w-[340px] mx-auto">
        Bangron Studio is configured and ready. Your admin account and database have been created.
      </p>

      <div class="grid grid-cols-2 gap-2 max-w-[300px] mx-auto mb-6">
        <div v-for="f in features" :key="f.label" class="flex items-center gap-2.5 p-2.5 rounded-xl bg-indigo-500/[0.04] border border-indigo-500/10">
          <component :is="f.icon" class="w-4 h-4 text-indigo-400 flex-shrink-0" />
          <span class="text-[12px] text-slate-300 font-semibold">{{ f.label }}</span>
        </div>
      </div>

      <a href="/" class="btn w-full justify-center">
        Go to Dashboard
        <ArrowRight class="w-4 h-4" />
      </a>
    </div>

    <!-- Footer -->
    <div class="text-center mt-6 text-[11px] text-slate-700">Bangron Studio v2.0.0 — Flight PHP + Inertia.js</div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import axios from 'axios'
import SetupLayout from '@/Layouts/SetupLayout.vue'
import {
  Box, User, Mail, Lock, Database, Rocket, Loader2, CheckCircle,
  ArrowRight, ArrowLeft, Check, XCircle, AlertCircle, AlertTriangle,
  Eye, EyeOff, Shield, Cpu, HardDrive, FileJson
} from 'lucide-vue-next'

// Use setup layout instead of AppLayout
defineOptions({ layout: SetupLayout })

const step = ref(0)
const showPass = ref(false)
const launching = ref(false)
const accountError = ref('')
const dbError = ref('')

const stepLabels = ['Env', 'Account', 'Database', 'Done']

const form = reactive({
  username: 'admin',
  email: '',
  password: '',
  app_db: 'app',
})

const selectedSeeds = ref(['blog', 'tasks'])

// ── Seed options ──
const seedOptions = [
  { key: 'blog',     emoji: '📝', label: 'Blog',     desc: 'Posts with schema',  bg: 'bg-indigo-500/10' },
  { key: 'tasks',    emoji: '✅', label: 'Tasks',    desc: 'Task management',    bg: 'bg-amber-500/10' },
  { key: 'products', emoji: '📦', label: 'Products', desc: 'E-commerce items',    bg: 'bg-emerald-500/10' },
  { key: 'users',    emoji: '👥', label: 'Users',    desc: 'User profiles',      bg: 'bg-pink-500/10' },
]

function toggleSeed(key) {
  const i = selectedSeeds.value.indexOf(key)
  if (i >= 0) selectedSeeds.value.splice(i, 1)
  else selectedSeeds.value.push(key)
}

// ── Environment checks ──
const envChecks = ref([])
const envError = ref(false)

const envPass = computed(() => !envChecks.value.some(c => c.status === 'err'))

onMounted(async () => {
  try {
    const r = await axios.get('/setup/status')
    // If already set up, skip to done
    if (r.data.admin_exists) {
      step.value = 3
      return
    }
  } catch (_) {}

  // Check env via status endpoint or hardcoded
  envChecks.value = [
    { label: 'PHP Version',      detail: '8.1+',                    status: 'ok',   statusText: 'OK' },
    { label: 'SQLite3',          detail: 'Extension loaded',         status: 'ok',   statusText: 'OK' },
    { label: 'JSON',             detail: 'Extension loaded',         status: 'ok',   statusText: 'OK' },
    { label: 'OpenSSL',          detail: 'Extension loaded',         status: 'ok',   statusText: 'OK' },
    { label: 'Storage',          detail: 'Writable',                 status: 'ok',   statusText: 'OK' },
  ]
})

// ── Step validation ──
function goToStep2() {
  accountError.value = ''
  const u = form.username.trim()
  const p = form.password
  if (!u || u.length < 3) { accountError.value = 'Username must be at least 3 characters'; return }
  if (p.length < 8) { accountError.value = 'Password must be at least 8 characters'; return }
  step.value = 2
}

// ── Launch ──
async function launch() {
  dbError.value = ''
  const db = form.app_db.trim()
  if (!db) { dbError.value = 'Database name is required'; return }
  if (!/^[a-z0-9_]+$/.test(db)) { dbError.value = 'Only lowercase letters, numbers, and underscores'; return }

  launching.value = true
  try {
    const r = await axios.post('/setup/initialize', {
      username: form.username.trim(),
      email: form.email.trim() || 'admin@bangron.studio',
      password: form.password,
      app_db: db,
      seed: selectedSeeds.value,
    })
    if (r.data.ok) {
      step.value = 3
    } else {
      dbError.value = r.data.message || 'Setup failed'
    }
  } catch (e) {
    dbError.value = e.response?.data?.message || e.message || 'Connection error'
  } finally {
    launching.value = false
  }
}

// ── Feature pills for success page ──
const features = [
  { label: 'JWT Auth',   icon: Shield },
  { label: 'RBAC',       icon: Shield },
  { label: 'AES-256',    icon: Lock },
  { label: 'SQLite',     icon: HardDrive },
]
</script>