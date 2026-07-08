<template>
  <div class="animate-fade-in">
    <!-- Brand -->
    <div class="text-center mb-8">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center shadow-xl shadow-indigo-500/25 mb-4">
        <Box class="w-7 h-7 text-white" />
      </div>
      <h1 class="text-[22px] font-extrabold text-white tracking-tight">Create Account</h1>
      <p class="text-slate-500 text-[13px] mt-1">Register a new Bangron Studio account</p>
    </div>

    <div class="setup-card">
      <form @submit.prevent="register">
        <div class="space-y-4">
          <div>
            <label class="setup-label">Username</label>
            <div class="relative">
              <User class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
              <input v-model="form.username" class="input !pl-10" placeholder="admin" />
            </div>
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
              <input v-model="form.password" :type="showPass ? 'text' : 'password'" class="input !pl-10 !pr-10" placeholder="Min. 8 characters" />
              <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors" @click="showPass = !showPass">
                <Eye v-if="!showPass" class="w-4 h-4" />
                <EyeOff v-else class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <div v-if="error" class="mt-4 p-3 rounded-xl bg-red-500/[0.06] border border-red-500/15 text-red-300 text-[12px] flex items-start gap-2">
          <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
          <span>{{ error }}</span>
        </div>

        <div v-if="success" class="mt-4 p-3 rounded-xl bg-emerald-500/[0.06] border border-emerald-500/15 text-emerald-300 text-[12px] flex items-start gap-2">
          <CheckCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
          <span>Account created! Redirecting to login...</span>
        </div>

        <div class="setup-btn-row mt-6">
          <a href="/auth/login" class="btn-ghost flex-1">
            <ArrowLeft class="w-4 h-4" />
            Back
          </a>
          <button type="submit" class="btn flex-1" :disabled="loading">
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            <span v-else>Register</span>
          </button>
        </div>
      </form>

      <div class="text-center mt-5">
        <span class="text-slate-500 text-[13px]">Already have an account? </span>
        <a href="/auth/login" class="text-indigo-400 hover:text-indigo-300 text-[13px] font-medium transition-colors">Sign In</a>
      </div>
    </div>

    <div class="text-center mt-6 text-[11px] text-slate-700">Bangron Studio v2.0.0</div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Box, User, Mail, Lock, Eye, EyeOff, ArrowLeft, AlertCircle, CheckCircle, Loader2 } from 'lucide-vue-next'

defineOptions({ layout: AuthLayout })

const form = reactive({ username: '', email: '', password: '' })
const showPass = ref(false)
const loading = ref(false)
const error = ref('')
const success = ref(false)

async function register() {
  error.value = ''
  success.value = false
  if (!form.username || !form.password) { error.value = 'Username dan password wajib diisi'; return }
  if (form.password.length < 8) { error.value = 'Password minimal 8 karakter'; return }
  loading.value = true
  try {
    const r = await axios.post('/auth/register', { username: form.username, email: form.email, password: form.password })
    if (r.data.ok) {
      success.value = true
      setTimeout(() => { window.location.href = '/auth/login' }, 1500)
    } else {
      error.value = r.data.message || 'Registrasi gagal'
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Koneksi gagal'
  } finally {
    loading.value = false
  }
}
</script>