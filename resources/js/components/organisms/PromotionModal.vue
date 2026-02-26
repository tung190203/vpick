<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999] p-4" @click.self="closeModal">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden">
          <!-- Header -->
          <div class="p-6 pb-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">Quảng bá</h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors" :disabled="sending">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <!-- Body -->
          <div class="p-6">
            <p class="text-gray-600">
              Bạn có chắc muốn quảng bá tới 50 người (bạn bè + gần khu vực) để tìm người chơi / thành viên?
            </p>
          </div>

          <!-- Footer -->
          <div class="p-6 pt-4 border-t border-gray-100 flex gap-3 justify-end">
            <button
              type="button"
              class="px-6 py-2.5 font-semibold rounded border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-50"
              @click="closeModal"
              :disabled="sending"
            >
              Hủy
            </button>
            <button
              type="button"
              class="px-6 py-2.5 font-semibold rounded bg-[#D72D36] hover:bg-[#c9252e] text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              @click="handleSend"
              :disabled="sending"
            >
              <span v-if="sending">Đang gửi...</span>
              <span v-else>Xác nhận quảng bá</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { sendPromotion } from '@/service/promotion.js'
import { toast } from 'vue3-toastify'

const props = defineProps({
  modelValue: Boolean,
  promotableType: { type: String, required: true },
  promotableId: { type: [Number, String], required: true }
})

const emit = defineEmits(['update:modelValue', 'success'])

const sending = ref(false)

const closeModal = () => {
  if (!sending.value) {
    emit('update:modelValue', false)
  }
}

const handleSend = async () => {
  sending.value = true
  try {
    await sendPromotion(props.promotableType, props.promotableId)
    emit('success')
    closeModal()
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Không thể gửi quảng bá')
  } finally {
    sending.value = false
  }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
