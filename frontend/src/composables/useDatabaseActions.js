import { reactive } from 'vue'
import axios from 'axios'
import { useToast } from '@/composables/useToast'

const api = axios.create({ baseURL: '' })
const toast = useToast()

const NAME_RE = /^[a-z0-9_]+$/

const state = reactive({
  showCreate: false,
  newName: '',
  createError: '',
  showRename: false,
  renameOld: '',
  renameNew: '',
  renameError: '',
  showDrop: false,
  dropTarget: '',
  busy: false,
})

export const DB_CHANGED_EVENT = 'bangron:databases-changed'

function notifyChanged() {
  window.dispatchEvent(new Event(DB_CHANGED_EVENT))
}

export function useDatabaseActions() {
  function openCreate() {
    state.newName = ''
    state.createError = ''
    state.showCreate = true
  }

  function openRename(oldName) {
    state.renameOld = oldName
    state.renameNew = oldName
    state.renameError = ''
    state.showRename = true
  }

  function openDrop(name) {
    state.dropTarget = name
    state.showDrop = true
  }

  async function submitCreate() {
    const name = state.newName.trim()
    if (!NAME_RE.test(name)) {
      state.createError = 'Nama hanya boleh huruf kecil, angka, dan underscore'
      return
    }
    state.busy = true
    try {
      await api.post('/databases', { name })
      state.showCreate = false
      toast.success('Database berhasil dibuat')
      notifyChanged()
    } catch (e) {
      state.createError = e.response?.data?.message || 'Gagal membuat database'
    } finally {
      state.busy = false
    }
  }

  async function submitRename() {
    const nn = state.renameNew.trim()
    if (!NAME_RE.test(nn)) {
      state.renameError = 'Nama hanya boleh huruf kecil, angka, dan underscore'
      return
    }
    state.busy = true
    try {
      await api.post(`/databases/${state.renameOld}/rename`, { new_name: nn })
      state.showRename = false
      toast.success('Database berhasil di-rename')
      notifyChanged()
    } catch (e) {
      state.renameError = e.response?.data?.message || 'Gagal me-rename database'
    } finally {
      state.busy = false
    }
  }

  async function submitDrop() {
    state.busy = true
    try {
      await api.delete(`/databases/${state.dropTarget}`)
      state.showDrop = false
      toast.success('Database berhasil dihapus')
      notifyChanged()
    } catch (e) {
      toast.error(e.response?.data?.message || 'Gagal menghapus database')
    } finally {
      state.busy = false
    }
  }

  return {
    state,
    openCreate,
    openRename,
    openDrop,
    submitCreate,
    submitRename,
    submitDrop,
  }
}
