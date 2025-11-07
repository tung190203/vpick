<template>
    <button @click="$emit('click')" class="w-full flex items-center justify-between py-3">
        <div class="text-left flex-1">
            <div class="text-sm text-gray-900">{{ label }}</div>
            <div v-if="subtitle" class="text-xs text-gray-500 mt-1">{{ subtitle }}</div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">{{ value }}</span>
            <ChevronRightIcon 
                :class="[
                    'w-4 h-4 text-gray-400 transition-transform duration-200',
                    { 'rotate-90': isOpen }
                ]" 
            />
        </div>
    </button>
</template>

<script setup>
import { ChevronRightIcon } from '@heroicons/vue/24/solid';
import { defineProps, defineEmits } from 'vue';

defineProps({
    label: {
        type: String,
        required: true
    },
    value: {
        type: String,
        required: true
    },
    subtitle: {
        type: String,
        default: ''
    },
    isOpen: {
        type: Boolean,
        default: false
    }
});

defineEmits(['click']);
</script>