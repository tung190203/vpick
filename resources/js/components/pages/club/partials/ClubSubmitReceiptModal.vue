<template>
    <Transition name="fade">
        <div v-if="isOpen" 
            class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            @click.self="close">
            <Transition name="scale">
                <div v-if="isOpen" 
                    class="bg-white rounded-[24px] w-full max-w-[500px] transition-all duration-300 flex flex-col p-8 relative shadow-2xl overflow-hidden">
                    <!-- Modal Close -->
                    <button 
                        @click="close"
                        class="absolute right-6 top-6 text-gray-400 hover:text-gray-600 transition-colors z-10">
                        <XMarkIcon class="w-6 h-6" />
                    </button>

                    <!-- Modal Header -->
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-[#2D3139]">Nộp biên lai thanh toán</h2>
                        <p class="text-sm text-gray-500 mt-1">Vui lòng tải lên ảnh minh chứng giao dịch</p>
                    </div>

                    <div class="space-y-6">
                        <!-- Upload Area -->
                        <div 
                            class="w-full h-48 bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl flex flex-col items-center justify-center cursor-pointer hover:border-[#D72D36] transition-colors relative group overflow-hidden"
                            @click="triggerFileInput"
                        >
                            <input 
                                type="file" 
                                ref="fileInput" 
                                class="hidden" 
                                accept="image/*" 
                                @change="handleFileUpload"
                            />
                            
                            <!-- Image Preview -->
                            <template v-if="previewImage">
                                <img :src="previewImage" class="w-full h-full object-contain" />
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <button 
                                        @click.stop="removePreview"
                                        class="bg-white/20 hover:bg-white/40 p-2 rounded-full backdrop-blur-md transition-colors"
                                    >
                                        <TrashIcon class="w-5 h-5 text-white" />
                                    </button>
                                </div>
                            </template>

                            <!-- Placeholder -->
                            <template v-else>
                                <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center mb-3 group-hover:bg-[#FEE2E2] transition-colors">
                                    <PhotoIcon class="w-6 h-6 text-gray-400 group-hover:text-[#D72D36]" />
                                </div>
                                <p class="font-bold text-[#1F2937]">Nhấn để tải ảnh lên</p>
                                <p class="text-[11px] text-[#838799] mt-1">PNG, JPG (Tối đa 5MB)</p>
                            </template>
                        </div>

                        <!-- Note Area -->
                        <div>
                            <label class="block text-sm font-bold text-[#1F2937] mb-2 uppercase tracking-wide">Ghi chú</label>
                            <textarea 
                                v-model="note"
                                rows="3"
                                placeholder="Nhập ghi chú giao dịch (nếu có)..."
                                class="w-full bg-[#EDEEF2] border-none rounded-xl py-4 px-4 text-sm focus:ring-2 focus:ring-[#D72D36]/20 placeholder:text-[#9EA2B3] resize-none"
                            ></textarea>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            @click="handleSubmit"
                            :disabled="isSubmitting || !selectedFile"
                            class="w-full py-4 bg-[#D72D36] text-white rounded-xl font-bold hover:bg-[#b91c1c] transition-all shadow-lg active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        >
                            <template v-if="isSubmitting">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Đang xử lý...
                            </template>
                            <template v-else>
                                Gửi biên lai
                            </template>
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<script setup>
import { ref } from 'vue'
import { XMarkIcon, PhotoIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { toast } from 'vue3-toastify'
import * as ClubService from '@/service/club.js'

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    clubId: {
        type: [String, Number],
        required: true
    },
    collectionId: {
        type: [String, Number],
        required: true
    }
})

const emit = defineEmits(['update:isOpen', 'success'])

const fileInput = ref(null)
const selectedFile = ref(null)
const previewImage = ref(null)
const note = ref('')
const isSubmitting = ref(false)

const close = () => {
    resetForm()
    emit('update:isOpen', false)
}

const resetForm = () => {
    selectedFile.value = null
    previewImage.value = null
    note.value = ''
    if (fileInput.value) fileInput.value.value = ''
}

const triggerFileInput = () => {
    fileInput.value?.click()
}

const handleFileUpload = (event) => {
    const file = event.target.files[0]
    if (file) {
        if (!file.type.startsWith('image/')) {
            toast.error('Vui lòng chọn tệp hình ảnh')
            return
        }
        if (file.size > 5 * 1024 * 1024) {
            toast.error('Kích thước ảnh không quá 5MB')
            return
        }
        selectedFile.value = file
        previewImage.value = URL.createObjectURL(file)
    }
}

const removePreview = () => {
    selectedFile.value = null
    previewImage.value = null
    if (fileInput.value) fileInput.value.value = ''
}

const handleSubmit = async () => {
    if (!selectedFile.value) {
        toast.error('Vui lòng chọn ảnh biên lai')
        return
    }

    try {
        isSubmitting.value = true
        const formData = new FormData()
        formData.append('image', selectedFile.value)
        if (note.value) {
            formData.append('note', note.value)
        }

        const response = await ClubService.submitContributionReceipt(props.clubId, props.collectionId, formData)
        toast.success(response.message || 'Gửi biên lai thành công')
        emit('success')
        close()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi gửi biên lai')
    } finally {
        isSubmitting.value = false
    }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
.scale-enter-active, .scale-leave-active {
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.scale-enter-from, .scale-leave-to {
    opacity: 0;
    transform: scale(0.9) translateY(20px);
}
</style>
