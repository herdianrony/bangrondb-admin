<template>
  <nav class="bottom-nav">
    <a href="/" :class="['bottom-nav-item', isActive('/') ? 'active' : '']">
      <LayoutDashboard /><span>Home</span>
    </a>
    <a href="/users" :class="['bottom-nav-item', isActive('/users') ? 'active' : '']">
      <Users /><span>Users</span>
    </a>
    <a href="/roles" :class="['bottom-nav-item', isActive('/roles') ? 'active' : '']">
      <Shield /><span>Roles</span>
    </a>
    <a href="/permissions" :class="['bottom-nav-item', isActive('/permissions') ? 'active' : '']">
      <KeyRound /><span>Perm</span>
    </a>
    <a href="/auth/login" :class="['bottom-nav-item', isActive('/auth/') ? 'active' : '']">
      <LogIn /><span>Login</span>
    </a>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { LayoutDashboard, Users, Shield, KeyRound, LogIn } from 'lucide-vue-next'

const page = usePage()
const currentPath = computed(() => page.url || window.location.pathname)

const isActive = (href) => {
  if (href === '/') return currentPath.value === '/'
  return currentPath.value.startsWith(href)
}
</script>
