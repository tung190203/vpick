<template>
    <button
      :class="computedClass"
      :type="type"
      @click="$emit('click', $event)"
    >
      <slot />
    </button>
  </template>
  
  <script setup>
  import { computed } from 'vue'
  
  const props = defineProps({
    variant: {
      type: String,
      default: 'solid', // 'solid', 'outline', 'text'
    },
    color: {
      type: String,
      default: 'primary', // 'primary', 'secondary', 'danger', etc.
    },
    size: {
      type: String,
      default: 'md', // 'sm', 'md', 'lg'
    },
    type: {
      type: String,
      default: 'button',
    }
  })
  
  const colorMap = {
    primary: {
      solid: 'bg-blue-600 text-white hover:bg-blue-700',
      outline: 'border border-blue-600 text-blue-600 hover:bg-blue-50',
      text: 'text-blue-600 hover:underline'
    },
    secondary: {
      solid: 'bg-[#EDEEF2] text-[#838799] hover:bg-gray-200',
      outline: 'border border-gray-300 text-gray-500 hover:bg-gray-50',
      text: 'text-gray-500 hover:underline'
    },
    danger: {
      solid: 'bg-red-600 text-white hover:bg-red-700',
      outline: 'border border-red-600 text-red-600 hover:bg-red-50',
      text: 'text-red-600 hover:underline'
    }
  }
  
  const sizeMap = {
    sm: 'px-3 py-1 text-sm rounded',
    md: 'px-4 py-2 text-base rounded-md',
    lg: 'px-5 py-3 text-lg rounded-lg'
  }
  
  const computedClass = computed(() => {
    const variantClass = colorMap[props.color]?.[props.variant] || ''
    const sizeClass = sizeMap[props.size] || ''
    const disabledClass = 'disabled:opacity-50 disabled:cursor-not-allowed'
    return `inline-flex items-center justify-center font-medium transition ${variantClass} ${sizeClass} ${disabledClass}`
  })
  </script>
  