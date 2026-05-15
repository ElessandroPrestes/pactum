<script setup lang="ts">
const appName = import.meta.env.VITE_APP_NAME ?? 'Pactum'
</script>

<template>
  <main>
    <h1>{{ appName }}</h1>
    <p>SPA inicializada.</p>
  </main>
</template>
