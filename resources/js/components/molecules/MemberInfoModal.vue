<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
        @click.self="closeModal">
        <div class="bg-white rounded-[32px] shadow-2xl w-full max-w-md overflow-hidden animate-scaleIn relative">
          <!-- Header -->
          <div class="px-6 py-5 flex items-center justify-between border-b border-gray-50">
            <h2 class="text-[20px] font-bold text-[#374151]">Thông tin thành viên CLB</h2>
            <button @click="closeModal" class="p-2 text-gray-500 hover:text-gray-700 transition-colors">
              <XMarkIcon class="w-7 h-7" />
            </button>
          </div>

          <!-- Content -->
          <div class="p-6">
            <!-- Member Header -->
            <div class="flex items-center gap-4 mb-8">
              <div class="relative">
                <img :src="member?.avatar" :alt="member?.name" class="w-16 h-16 rounded-full object-cover bg-blue-50">
                <div v-if="member?.level"
                  class="absolute -bottom-1 -left-1 w-6 h-6 bg-[#4B88FF] rounded-full flex items-center justify-center border-2 border-white text-white text-[10px] font-bold">
                  {{ member?.level }}
                </div>
              </div>
              <div class="flex flex-col">
                <span class="text-[18px] font-bold text-[#374151]">{{ member?.name }}</span>
                <span class="text-[14px] text-gray-400 font-medium">{{ member?.lastSeen }}</span>
              </div>
            </div>

            <!-- Role Section -->
            <div class="mb-8">
              <h3 class="text-[14px] font-bold text-[#9EA2B3] uppercase tracking-wider mb-3">VAI TRÒ TRONG CLB</h3>
              <div class="flex items-center gap-4 p-4 border border-[#EDEEF2] rounded-2xl bg-white">
                <div class="w-12 h-12 rounded-full bg-[#F3F4F6] flex items-center justify-center">
                  <UserIcon class="w-6 h-6 text-[#374151]" />
                </div>
                <span class="text-[16px] font-bold text-[#374151]">
                  {{ member?.role === 'Admin' ? 'Chủ câu lạc bộ' : (member?.role === 'Mod' ? 'Phó câu lạc bộ' : 'Thành viên') }}
                </span>
              </div>
            </div>

            <!-- Specialization Section (Optional based on data) -->
            <div v-if="member?.status === 'Thủ quỹ'" class="mb-8">
              <h3 class="text-[14px] font-bold text-[#9EA2B3] uppercase tracking-wider mb-3">PHỤ TRÁCH CHUYÊN MÔN</h3>
              <div class="flex items-center gap-2 px-6 py-2.5 bg-[#DC2626] text-white rounded-full w-fit">
                <MoneyIcon class="w-5 h-5" />
                <span class="text-[16px] font-bold">Thủ quỹ</span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
              <button
                class="w-full py-3 border-2 border-[#4392E0] text-[#4392E0] bg-[#f2f7fc] font-bold rounded-full flex items-center justify-center gap-2 hover:bg-[#4392E0] hover:text-white transition-all active:scale-[0.98]">
                <UserPlusIcon class="w-6 h-6" />
                Kết bạn
              </button>
              <button @click="closeModal"
                class="w-full py-3 bg-[#F3F4F6] text-[#374151] font-bold rounded-full transition-all hover:bg-gray-200 active:scale-[0.98]">
                Hủy
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { XMarkIcon, UserIcon, UserPlusIcon } from '@heroicons/vue/24/outline'
import MoneyIcon from "@/assets/images/money.svg";

const props = defineProps({
  modelValue: Boolean,
  member: { type: Object, default: null }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v)
})

const closeModal = () => (isOpen.value = false)
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

@keyframes scaleIn {
  from {
    transform: scale(0.95);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.animate-scaleIn {
  animation: scaleIn 0.3s ease-out;
}
</style>
