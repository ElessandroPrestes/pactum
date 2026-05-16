<script setup lang="ts">
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppShell from '@/components/layout/AppShell.vue'
import { AppToastContainer } from '@/components/ui'

const route = useRoute()
const useShell = computed(() => route.meta.layout !== 'blank')
</script>

<template>
    <AppShell v-if="useShell">
        <RouterView />
    </AppShell>
    <RouterView v-else />
    <AppToastContainer />
</template>
