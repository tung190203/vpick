<template>
    <div class="flex items-center justify-between py-2">
        <div class="flex-1 pr-4" v-if="label || description">
            <div v-if="label" class="text-sm font-medium text-gray-900">{{ label }}</div>
            <div v-if="description" class="text-xs text-gray-500 mt-1">{{ description }}</div>
        </div>
        <button @click="toggle"
            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors flex-shrink-0"
            :class="isChecked ? 'bg-[#D72D36]' : 'bg-gray-300'">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                :class="isChecked ? 'translate-x-6' : 'translate-x-1'" />
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: undefined
    },
    value: {
        type: Boolean,
        default: undefined
    },
    label: {
        type: String,
        default: ''
    },
    description: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'update']);

const isChecked = computed(() => {
    if (props.modelValue !== undefined) return props.modelValue;
    return props.value;
});

const toggle = () => {
    const newValue = !isChecked.value;
    emit('update:modelValue', newValue);
    emit('update', newValue);
};
</script>