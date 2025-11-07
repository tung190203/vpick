<template>
    <div class="flex items-center justify-between py-2">
        <span class="text-sm text-gray-900">{{ label }}</span>
        <div class="flex items-center gap-3">
            <button @click="$emit('update', Math.max(0, value - 1))"
                class="w-6 h-6 flex items-center justify-center bg-gray-900 text-white rounded hover:bg-gray-800 transition-colors">
                −
            </button>
            <span class="text-lg font-semibold w-8 text-center">{{ String(value).padStart(2, '0') }}</span>
            <button @click="$emit('update', value + 1)"
                class="w-6 h-6 flex items-center justify-center bg-gray-900 text-white rounded hover:bg-gray-800 transition-colors">
                +
            </button>
        </div>
    </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

defineProps({
    label: {
        type: String,
        required: true
    },
    value: {
        type: Number,
        required: true
    }
});

defineEmits(['update']);
</script>