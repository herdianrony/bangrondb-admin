<template>
  <div class="min-h-[85vh] flex items-center justify-center">
    <div class="w-full max-w-lg animate-scale-in">
      <!-- Logo & Title -->
      <div class="text-center mb-8">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center shadow-xl shadow-indigo-500/20 mb-5">
          <Box class="w-8 h-8 text-white" />
        </div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Welcome to Bangron Studio</h1>
        <p class="text-slate-400 text-sm mt-2 max-w-sm mx-auto leading-relaxed">
          Let's get your backend platform ready. This will only take a minute.
        </p>
      </div>

      <!-- Step Indicators -->
      <div class="flex items-center justify-center gap-2 mb-8">
        <div v-for="(s, i) in steps" :key="i" class="flex items-center">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all duration-300',
            step > i ? 'bg-emerald-500/20 border-emerald-500 text-emerald-400' :
            step === i ? 'bg-indigo-500 border-indigo-500 text-white shadow-lg shadow-indigo-500/30' :
            'bg-transparent border-slate-700 text-slate-600'
          ]">
            <Check v-if="step > i" class="w-3.5 h-3.5" />
            <span v-else>{{ i + 1 }}</span>
          </div>
          <div v-if="i < steps.length - 1" :class="[
            'w-10 h-0.5 mx-1 rounded-full transition-all duration-300',
            step > i ? 'bg-emerald-500/40' : 'bg-slate-700'
          ]"></div>
        </div>
      </div>

      <!-- Step 0: Welcome -->
      <div v-if="step === 0" class="card !p-8">
        <h2 class="text-lg font-semibold text-white mb-2">Get Started</h2>
        <p class="text-slate-400 text-sm leading-relaxed mb-6">
          Bangron Studio is a dynamic backend platform with an embedded document database,
          AES-256-GCM encryption, and role-based access control. We'll create your admin
          account and a default database to start with.
        </p>
        <div class="space-y-3 mb-6">
          <div class="flex items-start gap-3 text-sm">
            <div class="w-6 h-6 rounded-lg bg-indigo-500/10 border border-indigo-500/20 grid place-items-center flex-shrink-0 mt-0.5">
              <Shield class="w-3 h-3 text-indigo-400" />
            </div>
            <div>
              <div class="text-slate-200 font-medium">Secure by Default</div>
              <div class="text-slate-500 text-xs">AES-256-GCM encryption, JWT authentication, RBAC</div>
            </div>
          </div>
          <div class="flex items-start gap-3 text-sm">
            <div class="w-6 h-6 rounded-lg bg-indigo-500/10 border border-indigo-500/20 grid place-items-center flex-shrink-0 mt-0.5">
              <Database class="w-3 h-3 text-indigo-400" />
            </div>
            <div>
              <div class="text-slate-200 font-medium">Embedded Database</div>
              <div class="text-slate-500 text-xs">SQLite-based, 24 field types, Mongo-style queries</div>
            </div>
          </div>
          <div class="flex items-start gap-3 text-sm">
            <div class="w-6 h-6 rounded-lg bg-indigo-500/10 border border-indigo-500/20 grid place-items-center flex-shrink-0 mt-0.5">
              <Zap class="w-3 h-3 text-indigo-400" />
            </div>
            <div>
              <div class="text-slate-200 font-medium">Zero Dependencies</div>
              <div class="text-slate-500 text-xs">Single PHP project, no external database server needed</div>
            </div>
          </div>
        </div>
        <button class="btn w-full !py-3" @click="step = 1">
          Continue
          <ArrowRight class="w-4 h-4" />
        </button>
      </div>

      <!-- Step 1: Account -->
      <div v-else-if="step === 1" class="card !p-8">
        <h2 class="text-lg font-semibold text-white mb-1">Create Admin Account</h2>
        <p class="text-slate-500 text-sm mb-6">This will be your primary administrator account.</p>

        <div class="space-y-4">
          <div>
            <label class="section-label">Username</label>
            <div class="relative">
              <User class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
              <input v-model="form.username" class="input !pl-10" placeholder="admin" @keydown.enter="step=2" />
            </div>
          </div>
          <div>
            <label class="section-label">Email</label>
            <div class="relative">
              <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
              <input v-model="form.email" type="email" class="input !pl-10" placeholder="admin@example.com" />
            </div>
          </div>
          <div>
            <label class="section-label">Password</label>
            <div class="relative">
              <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
              <input v-model="form.password" type="password" class="input !pl-10" placeholder="Min. 8 characters" />
              <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300" @click="showPass=!showPass">
                <Eye v-if="!showPass" class="w-4 h-4" />
                <EyeOff v-else class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <div class="flex gap-3 mt-6">
          <button class="btn-ghost flex-1" @click="step=0">
            <ArrowLeft class="w-4 h-4" />
            Back
          </button>
          <button class="btn flex-1 !py-2.5" @click="step=2" :disabled="!form.username || !form.password">
            Continue
            <ArrowRight class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Step 2: Database -->
      <div v-else-if="step === 2" class="card !p-8">
        <h2 class="text-lg font-semibold text-white mb-1">Create Your Database</h2>
        <p class="text-slate-500 text-sm mb-6">A default database will be created with sample collections to get you started.</p>

        <div>
          <label class="section-label">Database Name</label>
          <div class="relative">
            <Database class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
            <input v-model="form.app_db" class="input !pl-10" placeholder="app" />
          </div>
          <p class="text-[11px] text-slate-600 mt-2 ml-1">Collections "users", "posts", and "tasks" will be created automatically.</p>
        </div>

        <div class="flex gap-3 mt-6">
          <button class="btn-ghost flex-1" @click="step=1">
            <ArrowLeft class="w-4 h-4" />
            Back
          </button>
          <button class="btn flex-1 !py-2.5" @click="initialize" :disabled="loading">
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            <Rocket v-else class="w-4 h-4" />
            {{ loading ? 'Creating...' : 'Create & Start' }}
          </button>
        </div>

        <div v-if="error" class="mt-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs">
          {{ error }}
        </div>
      </div>

      <!-- Step 3: Done -->
      <div v-else-if="step === 3" class="card !p-8 text-center">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-500/10 border border-emerald-500/20 grid place-items-center mb-5 animate-scale-in">
          <CheckCircle class="w-8 h-8 text-emerald-400" />
        </div>
        <h2 class="text-xl font-bold text-white mb-2">You're All Set!</h2>
        <p class="text-slate-400 text-sm mb-6 leading-relaxed">
          Your Bangron Studio instance is ready. Start managing your data, or explore the API.
        </p>
        <a href="/" class="btn w-full !py-3">
          <ArrowRight class="w-4 h-4" />
          Go to Dashboard
        </a>
      </div>

      <!-- Already configured hint -->
      <div class="text-center mt-6">
        <span v-if="!statusChecked" class="badge cursor-pointer" @click="checkStatus">
          <Loader2 class="w-3 h-3 animate-spin inline" /> Checking status...
        </span>
        <span v-else-if="status.needs_setup" class="badge-warning">Initial setup required</span>
        <span v-else class="badge-success">Already configured</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import {
  Box, User, Mail, Lock, Database, Rocket, Loader2, CheckCircle,
  ArrowRight, ArrowLeft, Shield, Zap, Check, Eye, EyeOff
} from 'lucide-vue-next'

const loading = ref(false)
const error = ref('')
const showPass = ref(false)
const statusChecked = ref(false)
const step = ref(0)

const steps = ['Welcome', 'Account', 'Database', 'Ready']

const form = reactive({
  username: 'admin',
  email: 'admin@bangron.studio',
  password: 'Admin123!',
  app_db: 'app'
})

const status = ref({ needs_setup: true })

async function checkStatus() {
  const r = await axios.get('/api/setup/status').catch(() => ({ data: { needs_setup: true } }))
  status.value = r.data
  statusChecked.value = true
  if (!r.data.needs_setup) step.value = 3
}

async function initialize() {
  loading.value = true
  error.value = ''
  try {
    const r = await axios.post('/api/setup/initialize', { ...form })
    if (r.data.ok) {
      step.value = 3
    } else {
      error.value = r.data.message || 'Terjadi kesalahan saat setup'
    }
  } catch (e) {
    const msg = e.response?.data?.message || e.message
    error.value = msg
  } finally {
    loading.value = false
  }
}

onMounted(checkStatus)
</script>