<template>
    <div class="border rounded-lg p-3 cursor-pointer hover:border-gray-300 shadow-md">
      <h3 class="font-semibold text-gray-900 text-base leading-tight line-clamp-2">
        {{ match }}
      </h3>
    </div>
  </template>
  
  <script setup>
  defineProps({
    match: {
      type: [String, Object],
      required: true
    }
  })
  </script>
  