<script setup lang="ts">
const appName = import.meta.env.VITE_APP_NAME ?? 'Pactum'
</script>

<template>
  <section class="mx-auto max-w-3xl px-6 py-16">
    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
      {{ appName }}
    </h1>
    <p class="mt-3 text-slate-600">SPA inicializada.</p>
  </section>
</template>
