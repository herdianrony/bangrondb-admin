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

    <!-- ═══ Step 0: Create Admin Account ═══ -->
    <div v-if="step === 0" class="setup-card animate-fade-in">
      <h2 class="setup-title">Create Admin Account</h2>
      <p class="setup-subtitle">This will be your primary administrator account for managing the platform.</p>

      <div class="space-y-4">
        <div>
          <label class="setup-label">Username</label>
          <div class="relative">
            <User class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
            <input v-model="form.username" class="input !pl-10" placeholder="admin" @keydown.enter="launch" />
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
            <input v-model="form.password" :type="showPass ? 'text' : 'password'" class="input !pl-10 !pr-10" placeholder="Min. 8 characters" @keydown.enter="launch" />
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors" @click="showPass = !showPass">
              <Eye v-if="!showPass" class="w-4 h-4" />
              <EyeOff v-else class="w-4 h-4" />
            </button>
          </div>
          <div class="text-[11px] text-slate-600 mt-1.5">Minimum 8 characters with mixed case recommended</div>
        </div>
      </div>

      <div v-if="error" class="mt-4 p-3 rounded-xl bg-red-500/[0.06] border border-red-500/15 text-red-300 text-[12px] flex items-start gap-2">
        <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
        <span>{{ error }}</span>
      </div>

      <div class="setup-btn-row">
        <button class="btn w-full" :disabled="launching" @click="launch">
          <Loader2 v-if="launching" class="w-4 h-4 animate-spin" />
          <Rocket v-else class="w-4 h-4" />
          {{ launching ? 'Creating...' : 'Create & Launch' }}
        </button>
      </div>
    </div>

    <!-- ═══ Step 1: Done ═══ -->
    <div v-else-if="step === 1" class="setup-card animate-fade-in text-center">
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
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import SetupLayout from '@/Layouts/SetupLayout.vue'
import {
  Box, User, Mail, Lock, Rocket, Loader2, CheckCircle,
  ArrowRight, AlertCircle, Shield, Eye, EyeOff, HardDrive
} from 'lucide-vue-next'

defineOptions({ layout: SetupLayout })

const step = ref(0)
const showPass = ref(false)
const launching = ref(false)
const error = ref('')

const form = reactive({
  username: 'admin',
  email: '',
  password: '',
})

onMounted(async () => {
  try {
    const r = await axios.get('/setup/status')
    if (r.data.admin_exists) {
      step.value = 1
      return
    }
  } catch (_) {}
})

async function launch() {
  error.value = ''
  const u = form.username.trim()
  const p = form.password
  if (!u || u.length < 3) { error.value = 'Username must be at least 3 characters'; return }
  if (p.length < 8) { error.value = 'Password must be at least 8 characters'; return }

  launching.value = true
  try {
    const r = await axios.post('/setup/initialize', {
      username: u,
      email: form.email.trim() || 'admin@bangron.studio',
      password: p,
      app_db: 'app',
      seed: ['blog', 'tasks'],
    })
    if (r.data.ok) {
      step.value = 1
    } else {
      error.value = r.data.message || 'Setup failed'
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Connection error'
  } finally {
    launching.value = false
  }
}

const features = [
  { label: 'JWT Auth', icon: Shield },
  { label: 'RBAC',     icon: Shield },
  { label: 'AES-256',  icon: Lock },
  { label: 'SQLite',   icon: HardDrive },
]
</script>