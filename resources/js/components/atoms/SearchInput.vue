<template>
    <input
      v-model="modelValueComputed"
      :placeholder="placeholder"
      class="w-full pl-9 pr-4 py-1.5 h-9 text-sm
             border border-gray-300 bg-gray-100 rounded
             focus:outline-none focus:ring-1 focus:ring-blue-500
             placeholder:text-gray-400"
    />
  </template>
  
  <script setup>
  import { computed } from 'vue'
  
  const props = defineProps({
    modelValue: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      default: ''
    }
  })
  
  const emit = defineEmits(['update:modelValue'])
  
  const modelValueComputed = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value)
  })
  </script>
  