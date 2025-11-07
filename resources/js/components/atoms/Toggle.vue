<template>
    <div class="flex items-center justify-between py-2">
        <div class="flex-1 pr-4">
            <div class="text-sm font-medium text-gray-900">{{ label }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ description }}</div>
        </div>
        <button @click="$emit('update', !value)"
            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
            :class="value ? 'bg-[#D72D36]' : 'bg-gray-300'">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                :class="value ? 'translate-x-6' : 'translate-x-1'" />
        </button>
    </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

defineProps({
    label: {
        type: String,
        required: true
    },
    description: {
        type: String,
        required: true
    },
    value: {
        type: Boolean,
        required: true
    }
});

defineEmits(['update']);
</script>