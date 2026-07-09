<template>
  <div class="animate-fade-in">
    <!-- Brand -->
    <div class="text-center mb-8">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 grid place-items-center shadow-xl shadow-indigo-500/25 mb-4">
        <Box class="w-7 h-7 text-white" />
      </div>
      <h1 class="text-[22px] font-extrabold text-white tracking-tight">Welcome Back</h1>
      <p class="text-slate-500 text-[13px] mt-1">Sign in to Bangron Studio</p>
    </div>

    <div class="setup-card">
      <form @submit.prevent="login">
        <div class="space-y-4">
          <div>
            <label class="setup-label">Username or Email</label>
            <div class="relative">
              <User class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
              <input v-model="form.username" class="input !pl-10" placeholder="admin" autofocus />
            </div>
          </div>
          <div>
            <label class="setup-label">Password</label>
            <div class="relative">
              <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
              <input v-model="form.password" :type="showPass ? 'text' : 'password'" class="input !pl-10 !pr-10" placeholder="Enter your password" />
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

        <div class="setup-btn-row mt-6">
          <button type="submit" class="btn w-full" :disabled="loading">
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            <span v-else>Sign In</span>
          </button>
        </div>
      </form>

      <div class="text-center mt-5">
        <span class="text-slate-500 text-[13px]">Don't have an account? </span>
        <a href="/auth/register" class="text-indigo-400 hover:text-indigo-300 text-[13px] font-medium transition-colors">Register</a>
      </div>
    </div>

    <div class="text-center mt-6 text-[11px] text-slate-700">Bangron Studio v2.1 – Session + JWT hybrid</div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Box, User, Lock, Eye, EyeOff, AlertCircle, Loader2 } from 'lucide-vue-next'

defineOptions({ layout: AuthLayout })

const form = reactive({ username: '', password: '' })
const showPass = ref(false)
const loading = ref(false)
const error = ref('')

async function login() {
  error.value = ''
  if (!form.username || !form.password) { error.value = 'Username dan password wajib diisi'; return }
  loading.value = true
  try {
    // Hybrid: coba session login dulu (/login), fallback ke JWT (/auth/login) untuk API
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const r = await axios.post('/login', { 
      username: form.username, 
      password: form.password,
      _token: csrf
    }, { withCredentials: true })
    if (r.data.ok) {
      // session cookie sudah diset HttpOnly – redirect
      const dest = r.data.redirect || '/'
      window.location.href = dest
      return
    } else {
      error.value = r.data.message || 'Login gagal'
    }
  } catch (e) {
    // fallback JWT untuk external / API mode
    try {
      const r2 = await axios.post('/auth/login', { username: form.username, password: form.password })
      if (r2.data.ok || r2.data.access_token) {
        localStorage.setItem('token', r2.data.token || r2.data.access_token)
        localStorage.setItem('user', JSON.stringify(r2.data.user || {}))
        // set default Authorization untuk API calls berikutnya
        axios.defaults.headers.common['Authorization'] = 'Bearer ' + (r2.data.token || r2.data.access_token)
        window.location.href = '/'
        return
      }
    } catch (e2) {}
    error.value = e.response?.data?.message || e.message || 'Koneksi gagal'
  } finally {
    loading.value = false
  }
}
</script>