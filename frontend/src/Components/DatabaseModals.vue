<template>
  <div>
    <!-- Create Database -->
    <AppModal v-model="actions.state.showCreate">
      <template #default>
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-lg text-white">New Database</h3>
          <button class="btn-ghost-sm" @click="actions.state.showCreate = false">
            <X class="w-4 h-4" />
          </button>
        </div>
        <div class="mb-5">
          <label class="section-label">Database Name</label>
          <input
            v-model="actions.state.newName"
            class="input"
            placeholder="app_v2"
            @keyup.enter="actions.submitCreate()"
            ref="createInput"
          />
          <p v-if="actions.state.createError" class="text-red-400 text-xs mt-1.5">
            {{ actions.state.createError }}
          </p>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-white/[0.06]">
          <button class="btn-ghost" @click="actions.state.showCreate = false">Cancel</button>
          <button class="btn" :disabled="actions.state.busy" @click="actions.submitCreate()">
            <Loader2 v-if="actions.state.busy" class="w-4 h-4 animate-spin" />
            <Plus v-else class="w-4 h-4" />Create
          </button>
        </div>
      </template>
    </AppModal>

    <!-- Rename Database -->
    <AppModal v-model="actions.state.showRename">
      <template #default>
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-lg text-white">Rename Database</h3>
          <button class="btn-ghost-sm" @click="actions.state.showRename = false">
            <X class="w-4 h-4" />
          </button>
        </div>
        <div class="mb-5">
          <label class="section-label">New Name</label>
          <input
            v-model="actions.state.renameNew"
            class="input"
            @keyup.enter="actions.submitRename()"
            ref="renameInput"
          />
          <p class="text-slate-500 text-xs mt-1.5">From <span class="font-mono text-slate-400">{{ actions.state.renameOld }}</span></p>
          <p v-if="actions.state.renameError" class="text-red-400 text-xs mt-1.5">
            {{ actions.state.renameError }}
          </p>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-white/[0.06]">
          <button class="btn-ghost" @click="actions.state.showRename = false">Cancel</button>
          <button class="btn" :disabled="actions.state.busy" @click="actions.submitRename()">
            <Loader2 v-if="actions.state.busy" class="w-4 h-4 animate-spin" />
            <Pencil v-else class="w-4 h-4" />Rename
          </button>
        </div>
      </template>
    </AppModal>

    <!-- Drop Database -->
    <AppModal v-model="actions.state.showDrop">
      <template #default>
        <div class="flex items-start gap-3 mb-4">
          <div class="w-10 h-10 rounded-xl bg-red-500/10 border border-red-500/20 grid place-items-center flex-shrink-0">
            <AlertTriangle class="w-5 h-5 text-red-400" />
          </div>
          <div>
            <h3 class="font-bold text-white text-sm">Delete Database</h3>
            <p class="text-xs text-slate-400 mt-1">
              Permanently delete <span class="font-mono text-slate-300">{{ actions.state.dropTarget }}</span> and all its collections?
            </p>
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <button class="btn-ghost-sm" @click="actions.state.showDrop = false">Cancel</button>
          <button class="btn-sm !bg-red-600 hover:!bg-red-500" :disabled="actions.state.busy" @click="actions.submitDrop()">
            <Loader2 v-if="actions.state.busy" class="w-3.5 h-3.5 animate-spin" />
            <Trash2 v-else class="w-3.5 h-3.5" />Delete
          </button>
        </div>
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { watch, ref, nextTick } from 'vue'
import { Plus, X, Loader2, Pencil, Trash2, AlertTriangle } from 'lucide-vue-next'
import AppModal from '@/Components/AppModal.vue'
import { useDatabaseActions } from '@/composables/useDatabaseActions'

const actions = useDatabaseActions()

const createInput = ref(null)
const renameInput = ref(null)

watch(() => actions.state.showCreate, (v) => {
  if (v) nextTick(() => createInput.value?.focus())
})
watch(() => actions.state.showRename, (v) => {
  if (v) nextTick(() => renameInput.value?.focus())
})
</script>
