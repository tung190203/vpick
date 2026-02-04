<template>
    <div class="relative" ref="containerRef">
        <!-- Input Container -->
        <div class="relative">
            <!-- Left Icon -->
            <div v-if="hasIcon" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <slot name="icon">
                    <MagnifyingGlassIcon class="w-5 h-5 text-gray-400" />
                </slot>
            </div>

            <input
                ref="inputRef"
                type="text"
                :value="modelValue"
                @input="handleInput"
                @focus="isOpen = true"
                :placeholder="placeholder"
                class="w-full py-3 bg-gray-100 border-none rounded-lg focus:outline-none focus:ring-2 focus:ring-[#D72D36]/20 transition-colors placeholder:text-gray-400 text-gray-900"
                :class="[hasIcon ? 'pl-10' : 'px-4', hasArrow ? 'pr-10' : 'pr-4']"
            />

            <!-- Right Arrow Icon (for Bank selector style) -->
            <div v-if="hasArrow" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <ChevronDownIcon class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" />
            </div>
        </div>

        <!-- Dropdown List -->
        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div v-if="isOpen && filteredItems.length > 0" @scroll="onScroll" class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-lg border border-gray-100 max-h-60 overflow-y-auto py-1">
                <div
                    v-for="(item, index) in filteredItems"
                    :key="index"
                    @click="selectItem(item)"
                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer text-gray-700 text-sm font-medium transition-colors border-b border-gray-50 last:border-none"
                >
                    {{ itemLabel(item) }}
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { MagnifyingGlassIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    items: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: ''
    },
    hasIcon: {
        type: Boolean,
        default: false
    },
    hasArrow: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'select', 'load-more'])

const isOpen = ref(false)
const containerRef = ref(null)

const filteredItems = computed(() => {
    if (!props.modelValue) return props.items // Show all if input is empty
    const lowerQuery = props.modelValue.toLowerCase()
    return props.items.filter(item => {
        const label = typeof item === 'string' ? item : item.name
        return label.toLowerCase().includes(lowerQuery)
    })
})

const handleInput = (event) => {
    emit('update:modelValue', event.target.value)
    isOpen.value = true
}

const selectItem = (item) => {
    const value = typeof item === 'string' ? item : item.name
    emit('update:modelValue', value)
    emit('select', item)
    isOpen.value = false
}

const itemLabel = (item) => {
    return typeof item === 'string' ? item : item.name
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false
    }
}

const onScroll = (e) => {
    const { scrollTop, clientHeight, scrollHeight } = e.target
    if (scrollTop + clientHeight >= scrollHeight - 20) {
        emit('load-more')
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
