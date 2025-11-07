<template>
    <div class="bg-white px-4 pb-4 border-b border-gray-200 last:border-0">
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ title }}</h2>
        <div class="space-y-2">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { defineProps } from 'vue';

defineProps({
    title: {
        type: String,
        required: true
    }
});
</script>