<template>
  <div class="min-h-screen bg-[#09090b] flex items-center justify-center relative overflow-hidden">
    <!-- Background glow -->
    <div class="fixed inset-0 pointer-events-none z-0">
      <div class="absolute w-[600px] h-[400px] top-[10%] left-[10%] rounded-full bg-indigo-500/[0.06] blur-[120px]"></div>
      <div class="absolute w-[500px] h-[350px] bottom-[10%] right-[10%] rounded-full bg-violet-500/[0.05] blur-[100px]"></div>
    </div>

    <!-- Content -->
    <div class="relative z-10 w-full max-w-[520px] px-5 py-8">
      <slot />
    </div>
  </div>
</template>

<script setup>
// SetupLayout — minimal full-screen layout without sidebar
// Used only for the setup wizard
</script>