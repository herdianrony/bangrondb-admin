<template>
  <div class="space-y-4">
    <div class="card">
      <div class="flex items-center gap-2">
        <KeyRound :size="20" class="text-indigo-400" />
        <h2 class="text-xl font-bold">Tokens</h2>
      </div>
      <p class="text-sm text-slate-400">Access TTL: 15 min · Refresh TTL: 30 days · rotate on refresh</p>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <div class="card">
        <div class="flex justify-between items-center mb-2">
          <h3 class="font-semibold">Active Refresh Tokens</h3>
          <button class="btn-ghost-sm" @click="load"><RefreshCw :size="14" /></button>
        </div>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr><th class="text-left">JTI</th><th>User</th><th>Expires</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="t in tokens" :key="t.jti">
                <td class="font-mono">{{ t.jti?.substring(0,12) }}…</td>
                <td>{{ t.username || t.user_id }}</td>
                <td>{{ new Date(t.exp*1000).toLocaleString() }}</td>
                <td>
                  <button class="btn-ghost-sm badge-danger" @click="revoke(t.jti)" title="Revoke">
                    <AlertTriangle :size="14" class="inline mr-1" /> revoke
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card">
        <div class="flex justify-between items-center mb-2">
          <h3 class="font-semibold">Blacklist</h3>
          <button class="btn-ghost-sm" @click="loadBL"><RefreshCw :size="14" /></button>
        </div>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr><th class="text-left">JTI</th><th>Reason</th><th>Revoked</th></tr>
            </thead>
            <tbody>
              <tr v-for="b in blacklist" :key="b.jti">
                <td class="font-mono">{{ b.jti?.substring(0,12) }}…</td>
                <td>{{ b.reason }}</td>
                <td>{{ b.revoked_at }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="card text-xs text-slate-400">
      <div class="flex items-center gap-2 mb-2">
        <Shield :size="14" class="text-slate-400" />
        <b>API Endpoints</b>
      </div>
      POST /auth/login → access_token (15m) + refresh_token (30d)<br/>
      POST /auth/refresh {refresh_token} → rotate<br/>
      POST /auth/logout → revoke access+refresh (blacklist)<br/>
      POST /auth/revoke {jti} → manual revoke<br/>
      GET /auth/tokens · GET /auth/blacklist
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { KeyRound, RefreshCw, AlertTriangle, Shield } from 'lucide-vue-next'
const tokens = ref([])
const blacklist = ref([])
async function load(){ const r = await axios.get('/auth/tokens'); tokens.value = r.data.data||[] }
async function loadBL(){ const r = await axios.get('/auth/blacklist'); blacklist.value = r.data.data||[] }
async function revoke(jti){ await axios.post('/auth/revoke', {jti}); load(); loadBL() }
onMounted(()=>{ load(); loadBL() })
</script>