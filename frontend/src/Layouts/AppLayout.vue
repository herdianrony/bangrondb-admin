<template>
  <div class="min-h-screen bg-[#0f1117]">
    <!-- Mobile Sidebar Overlay -->
    <div v-if="mobileOpen" class="sidebar-overlay" @click="mobileOpen = false"></div>

    <!-- ═══ Sidebar ═══ -->
    <Sidebar :mobile-open="mobileOpen" @close="mobileOpen = false" />

    <!-- ═══ Main Area ═══ -->
    <div class="lg:ml-[250px] min-h-screen flex flex-col">
      <!-- Mobile Top Bar -->
      <MobileHeader @open="mobileOpen = true" />

      <!-- Page Content -->
      <main class="flex-1 p-5 md:p-6 lg:p-8 pb-24 lg:pb-8 animate-fade-in overflow-y-auto">
        <slot />
      </main>
    </div>

    <!-- Mobile Bottom Nav -->
    <BottomNav />

    <!-- Database action modals (create / rename / drop) -->
    <DatabaseModals />

    <!-- Global confirm / prompt dialogs -->
    <GlobalDialogs />

    <!-- Global Toast -->
    <ToastContainer />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Sidebar from '@/Components/Sidebar.vue'
import MobileHeader from '@/Components/MobileHeader.vue'
import BottomNav from '@/Components/BottomNav.vue'
import DatabaseModals from '@/Components/DatabaseModals.vue'
import GlobalDialogs from '@/Components/GlobalDialogs.vue'
import ToastContainer from '@/Components/ToastContainer.vue'

const mobileOpen = ref(false)
</script>
