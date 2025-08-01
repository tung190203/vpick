<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
    <div class="relative bg-white rounded-xl shadow-xl p-6 w-full max-w-3xl mx-4">
      <!-- Nút đóng -->
      <button @click="close" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600">
        <XMarkIcon class="w-5 h-5" />
      </button>

      <!-- Bước 1: Chọn trình độ -->
      <template v-if="step === 1">
        <h2 class="text-lg font-bold mb-4 text-center">Bạn đánh giá trình độ pickleball của mình ở mức nào?</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div v-for="level in levels" :key="level.label" @click="select(level)"
            class="cursor-pointer border rounded-lg p-4 flex flex-col gap-2 shadow-sm transition-all duration-150"
            :class="{
              'border-primary ring-1 ring-primary': selected?.label === level.label,
              'hover:shadow-md': selected?.label !== level.label
            }">
            <div class="flex items-center gap-2">
              <span :class="level.color + ' text-xl'">●</span>
              <span class="font-bold text-gray-800">{{ level.label }}</span>
            </div>
            <div class="text-sm text-gray-600">Điểm VNDUPR: <strong>{{ level.vndupr_score }}</strong></div>
            <div class="text-xs text-gray-500 italic">{{ level.description }}</div>
          </div>
        </div>

        <p class="text-sm text-gray-500 mt-3">Bạn sẽ trải qua 3 trận đầu để hệ thống xác thực và điều chỉnh điểm phù hợp hơn.</p>

        <div class="text-center mt-6">
          <button class="bg-primary text-white px-5 py-2 rounded disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed" :disabled="!selected"
            @click="handleStep1Confirm">
            Xác nhận
          </button>
        </div>
      </template>
      <!-- Bước 2: Chọn phương thức xác minh -->
      <template v-if="step === 2 && !verifyMethod">
        <h2 class="text-lg font-bold mb-4 text-center">Chọn phương thức xác minh trình độ</h2>
        <div class="grid gap-4">
          <button class="border rounded flex items-center justify-start p-4 hover:border-primary"
            @click="verifyMethod = 'upload'">
            <DocumentIcon class="w-5 h-5 inline-block mr-2" /> Tải giấy tờ chứng minh (giải đấu, bảng điểm…)
          </button>
          <button class="border rounded flex items-center justify-start p-4 hover:border-primary"
            @click="verifyMethod = 'referee'">
            <UserIcon class="w-5 h-5 inline-block mr-2" />
            Chọn người xác minh có VNDUPR > 4.0
          </button>
          <button class="border rounded flex items-center justify-start p-4 text-red-600 hover:border-red-400"
            @click="verifyMethod = 'skip'">
            <NoSymbolIcon class="w-5 h-5 inline-block mr-2 text-red-500" />
            Bỏ qua xác minh (điểm sẽ bị điều chỉnh nếu không đúng trình độ)
          </button>
        </div>
        <div class="text-sm text-gray-500 mt-4">
          Bạn có thể tải giấy tờ chứng minh trình độ hoặc chọn người xác minh. Nếu không có giấy tờ, hãy chọn người xác
          minh.
          <br />
          Nếu không muốn xác minh, bạn có thể bỏ qua bước này, nhưng điểm VNDUPR của bạn sẽ bị điều chỉnh nếu không đúng
          trình độ.
        </div>
        <div class="text-right mt-6">
          <button class="bg-gray-200 text-gray-700 px-4 py-2 rounded" @click="backStep">
            Quay lại
          </button>
        </div>
      </template>
      <!-- Upload giấy tờ -->
      <template v-if="step === 2 && verifyMethod === 'upload'">
        <h2 class="text-lg font-bold mb-4 text-center">Upload ảnh giấy chứng nhận (giải đấu, bảng điểm…)</h2>
        <!-- Preview -->
        <div v-if="uploadedFile" class="my-2 text-sm text-gray-700 flex flex-col items-center">
          <img v-if="isImageFile" :src="previewUrl" alt="Preview" class="max-h-48 rounded shadow border" />
          <div v-else class="text-center">
            <p><strong>{{ uploadedFile.name }}</strong></p>
            <p class="text-xs text-gray-500">{{ Math.round(uploadedFile.size / 1024) }} KB</p>
          </div>
        </div>
        <div
          class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-primary group transition"
          @dragover.prevent @drop.prevent="handleDrop" @click="fileInputRef?.click()">
          <svg class="mx-auto w-8 h-8 text-gray-400 group-hover:text-primary transition" fill="none"
            stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 16.5V19a2 2 0 002 2h14a2 2 0 002-2v-2.5M16 9l-4-4m0 0L8 9m4-4v12" />
          </svg>
          <p class="text-gray-600">Kéo & thả tệp vào đây, hoặc bấm để chọn</p>
          <p class="text-sm text-gray-400">Chấp nhận ảnh hoặc PDF (tối đa 5MB)</p>

          <input ref="fileInputRef" type="file" class="hidden" accept="image/*,application/pdf"
            @change="handleFileChange" />
        </div>
        <div class="flex items-center justify-end gap-2 mt-6">
          <button class="bg-gray-200 text-gray-700 px-4 py-2 rounded" @click="backStep">
            Quay lại
          </button>
          <button class="bg-primary text-white px-4 py-2 rounded" @click="confirmFinal">
            Xác nhận & hoàn tất
          </button>
        </div>
      </template>

      <!-- Chọn người xác minh -->
      <template v-if="step === 2 && verifyMethod === 'referee'">
        <h2 class="text-lg font-bold mb-4 text-center">Chọn người xác minh</h2>
        <div class="relative">
          <input v-model="search" @input="showDropdown = true" type="text" class="block w-full border rounded px-4 py-2 focus:outline-none focus:ring-1 focus:ring-primary"
            placeholder="Tìm tên người xác minh..." />

          <ul v-if="showDropdown && filteredReferees.length"
            class="absolute z-10 w-full border bg-white shadow rounded max-h-48 overflow-y-auto">
            <li v-for="(r, index) in filteredReferees" :key="index" @click="selectReferee(r)"
              class="px-4 py-2 cursor-pointer hover:bg-gray-100">
              {{ r.name }} - VNDUPR {{ r.vndupr }}
            </li>
          </ul>
        </div>
        <div class="text-sm text-gray-500 mb-4 mt-2">
          Hệ thống sẽ gửi mã xác minh đến người được chọn.
        </div>
        <div class="flex items-center justify-end gap-2">
          <button class="bg-gray-200 text-gray-700 px-4 py-2 rounded" @click="backStep">
            Quay lại
          </button>
          <button class="bg-primary text-white px-4 py-2 rounded" @click="confirmFinal">
            Xác nhận & hoàn tất
          </button>
        </div>
      </template>

      <!-- Bỏ qua xác minh -->
      <template v-if="step === 2 && verifyMethod === 'skip'">
        <h2 class="text-lg font-bold mb-4 text-center text-red-600">Bạn đang bỏ qua xác minh</h2>
        <p class="text-sm text-gray-600 text-center mb-4">
          Nếu thi đấu không đúng trình độ, điểm VNDUPR của bạn sẽ bị điều chỉnh mạnh.
        </p>
        <div class="flex items-center justify-end gap-2 mt-6">
          <button class="bg-gray-200 text-gray-700 px-4 py-2 rounded" @click="backStep">
            Quay lại
          </button>
          <button class="bg-red-600 text-white px-5 py-2 rounded" @click="confirmFinal">
            Đồng ý & hoàn tất
          </button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { XMarkIcon, DocumentIcon, UserIcon, NoSymbolIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  onClose: Function,
  onConfirm: Function
})

const selected = ref(null)
const step = ref(1)
const fileInputRef = ref(null)
const uploadedFile = ref(null)
const previewUrl = ref(null)
const verifyMethod = ref(null)
const search = ref('')
const selectedReferee = ref(null)
const showDropdown = ref(false)

const referees = ref([
  { name: 'Nguyễn Văn A', vndupr: 4.8 },
  { name: 'Trần Thị B', vndupr: 5.1 },
  { name: 'Lê C', vndupr: 4.2 },
  { name: 'Mai D', vndupr: 3.9 },
])

const filteredReferees = computed(() =>
  referees.value.filter(
    (r) =>
      r.vndupr > 4.0 &&
      r.name.toLowerCase().includes(search.value.toLowerCase())
  )
)

const selectReferee = (referee) => {
  selectedReferee.value = referee
  search.value = `${referee.name} - VNDUPR ${referee.vndupr}`
  showDropdown.value = false
}

const handleFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    preview(file)
  }
}

const handleDrop = (e) => {
  const file = e.dataTransfer.files[0]
  if (file) {
    preview(file)
  }
}

const backStep = () => {
  if (step.value === 2 && verifyMethod.value !== null) {
    step.value = 2
    verifyMethod.value = null
  } else if (step.value === 2) {
    step.value = 1
    verifyMethod.value = null
  } else {
    close()
  }
}

const preview = (file) => {
  uploadedFile.value = file
  if (file.type.startsWith('image/')) {
    const reader = new FileReader()
    reader.onload = () => {
      previewUrl.value = reader.result
    }
    reader.readAsDataURL(file)
  } else {
    previewUrl.value = null
  }
}

const isImageFile = computed(() => {
  return uploadedFile.value?.type?.startsWith('image/')
})

const levels = [
  { label: 'Beginner', vndupr_score: 1.5, description: 'Mới chơi, chưa thi đấu', color: 'text-green-500' },
  { label: 'Intermediate', vndupr_score: 2.8, description: 'Đã chơi vài tháng, biết kỹ thuật', color: 'text-yellow-500' },
  { label: 'Advanced', vndupr_score: 4.0, description: 'Từng dự giải phong trào hoặc CLB', color: 'text-blue-500' },
  { label: 'Pro/Tournament', vndupr_score: 5.5, description: 'Có thành tích giải đấu, chuyên nghiệp', color: 'text-red-500' }
]

const select = (level) => {
  selected.value = level
}

const handleStep1Confirm = () => {
  if (selected.value && selected.value.vndupr_score >= 4.0) {
    step.value = 2
  } else {
    confirmFinal()
  }
}

const confirmFinal = () => {
  const payload = {
    level: selected.value,
  }

  if (selected.value.vndupr_score >= 4.0) {
    if (verifyMethod.value === 'upload') {
      payload.verify_method = 'upload'
      payload.certified_file = uploadedFile.value
    } else if (verifyMethod.value === 'referee') {
      payload.verify_method = 'referee'
      payload.verifier_id = selectedReferee.value
    }
  }

  if (props.onConfirm) props.onConfirm(payload)
  reset()
  // props.onClose?.()
}


const close = () => {
  reset()
  props.onClose?.()
}

const reset = () => {
  selected.value = null
  step.value = 1
}
</script>
