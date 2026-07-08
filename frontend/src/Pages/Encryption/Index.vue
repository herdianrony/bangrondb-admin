<template>
  <div class="space-y-5">
    <div class="card">
      <div class="flex items-center gap-2">
        <KeyRound :size="22" class="text-amber-400" />
        <h2 class="font-bold text-lg">Encryption</h2>
      </div>
      <p class="text-slate-400 text-sm mt-1">Configure AES-256-GCM encryption with searchable blind index.</p>
    </div>
    <div class="grid lg:grid-cols-2 gap-4">
      <div class="card space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="section-label">Database</label>
            <input v-model="db" class="input"/>
          </div>
          <div>
            <label class="section-label">Collection</label>
            <input v-model="col" class="input"/>
          </div>
        </div>
        <div>
          <label class="section-label">Encryption Key</label>
          <input v-model="key" class="input font-mono" placeholder="32+ char secret"/>
        </div>
        <div>
          <label class="section-label">Searchable Fields</label>
          <input v-model="fields" class="input" placeholder="email, phone"/>
        </div>
        <label class="text-sm flex items-center gap-2">
          <input type="checkbox" v-model="hash"/>
          Hash SHA-256 (recommended)
        </label>
        <button class="btn w-full flex items-center justify-center gap-2" @click="save">
          <Shield :size="16" />
          Apply & Save
        </button>
        <p class="text-xs text-slate-400">Kunci enkripsi TIDAK disimpan di database. Simpan di .env atau secret manager.</p>
        <div v-if="msg" class="badge-success flex items-center gap-2">
          <CheckCircle :size="14" />
          {{ msg }}
        </div>
      </div>
      <div class="card">
        <h3 class="font-semibold mb-2">Technical Notes</h3>
        <ul class="text-sm text-slate-300 space-y-2 list-disc pl-5">
          <li>AES-256-GCM, PBKDF2 SHA-256 key derivation</li>
          <li>Random IV per encryption, Base64 payload</li>
          <li>Searchable = blind index in additional column</li>
          <li>Unique constraint requires searchable=true</li>
          <li>saveConfiguration() called automatically</li>
        </ul>
        <pre class="code-block mt-3 overflow-auto">$col->setEncryptionKey($_ENV['KEY']);
$col->setSearchableFields(['email','phone'], true);
$col->saveConfiguration();</pre>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { KeyRound, Lock, Shield, Save, CheckCircle } from 'lucide-vue-next'

const db = ref('app'); const col = ref('users')
const key = ref(''); const fields = ref('email, phone'); const hash = ref(true)
const msg = ref('')
async function save(){
  await axios.post(`/databases/${db.value}/collections/${col.value}/encryption`, {
    key: key.value || null,
    searchable: fields.value.split(',').map(s=>s.trim()).filter(Boolean),
    hash: hash.value
  })
  msg.value = 'Konfigurasi enkripsi berhasil disimpan'
  setTimeout(()=>msg.value='',2500)
}
</script>