<template>
  <div class="max-w-8xl rounded-md flex flex-col overflow-hidden">
    <!-- Header -->
    <div class="bg-transparent backdrop-blur-md px-6 py-4 flex items-center justify-between sticky top-0 z-20">
      <div class="flex items-center gap-4">
        <button @click="goBack" class="hover:bg-gray-100 rounded-full transition-colors p-1">
          <ArrowLeftIcon class="w-6 h-6 text-[#3E414C]" stroke-width="2.5" />
        </button>
        <h1 class="text-xl font-bold text-[#3E414C]">Tạo lịch sinh hoạt</h1>
        <div
          class="px-3 py-1 bg-[#FBEAEB] text-[#D72D36] rounded-full text-sm font-semibold flex items-center gap-1.5 border border-[#FBEAEB] cursor-pointer hover:bg-[#F7D5D7] transition-colors">
          <FolderSpecialIcon class="w-4 h-4" />
          <span>Mẫu</span>
        </div>
      </div>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
      <div class="grid grid-cols-12 gap-6">
        <!-- Left Column -->
        <div class="col-span-12 lg:col-span-8 space-y-6">
          <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-50">
            <div class="flex items-center gap-3 mb-8">
              <div
                class="w-10 h-10 bg-[#D72D36] rounded-full flex items-center justify-center text-white shadow-red-200 shadow-md">
                <IdentificationIcon class="w-5 h-5 text-white" stroke-width="2.5" />
              </div>
              <h2 class="text-xl font-bold text-[#3E414C]">Thông tin cơ bản</h2>
            </div>

            <div class="space-y-6">
              <!-- Event Name -->
              <div>
                <label class="block text-sm font-bold text-[#838799] uppercase mb-2 tracking-wider">TÊN SỰ KIỆN</label>
                <input v-model="form.title" type="text"
                  class="w-full px-5 py-3 bg-[#F0F2F5] border-none rounded-[8px] focus:outline-none focus:ring-2 focus:ring-[#D72D36]/20 transition-all placeholder:text-[#9EA2B3] font-semibold text-gray-900"
                  :class="{ 'ring-2 ring-red-500/50 bg-red-50': errors.title }" placeholder="VD: Kèo cố định 3-5-7"
                  @input="errors.title = ''" />
                <span v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title }}</span>
              </div>

              <!-- Description -->
              <div>
                <div class="flex justify-between mb-2">
                  <label class="text-sm font-bold text-[#838799] uppercase tracking-wider">GHI CHÚ</label>
                  <span class="text-xs text-[#838799] font-medium">{{ form.description.length }}/300</span>
                </div>
                <textarea v-model="form.description" rows="4" maxlength="300"
                  class="w-full px-5 py-4 bg-[#F0F2F5] border-none rounded-[8px] focus:outline-none focus:ring-2 focus:ring-[#D72D36]/20 transition-all resize-none placeholder:text-[#9EA2B3] font-medium text-gray-900"
                  placeholder="Chia sẻ về sự kiện này"></textarea>
              </div>

              <!-- Location Search -->
              <div class="relative">
                <label class="block text-sm font-bold text-[#838799] uppercase mb-2 tracking-wider">ĐỊA ĐIỂM</label>
                <div class="relative cursor-pointer group">
                  <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <MagnifyingGlassIcon
                      class="h-5 w-5 text-[#838799] group-focus-within:text-[#D72D36] transition-colors" />
                  </div>
                  <input v-model="form.address" type="text" placeholder="Tìm kiếm địa điểm sinh hoạt"
                    class="w-full pl-12 pr-10 py-3 bg-[#F0F2F5] border-none rounded-[8px] focus:outline-none focus:ring-2 focus:ring-[#D72D36]/20 transition-all text-sm font-semibold text-[#3E414C] placeholder:text-[#9EA2B3]" />
                  <div class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer pointer-events-none">
                    <ChevronDownIcon class="h-4 w-4 text-[#838799]" stroke-width="2.5" />
                  </div>
                </div>

                <!-- Location Results -->
                <div v-if="locations.length > 0"
                  class="absolute left-0 right-0 mt-2 bg-white border border-gray-100 rounded-xl shadow-xl max-h-56 overflow-y-auto custom-scrollbar z-50">
                  <div v-for="loc in locations" :key="loc.place_id" @click="onLocationSelect(loc)"
                    class="px-4 py-3 text-sm flex items-start gap-3 hover:bg-[#F8F9FA] hover:text-[#D72D36] cursor-pointer border-b border-gray-50 last:border-none transition-all font-medium">
                    <div class="leading-tight">{{ loc.name }}</div>
                  </div>
                </div>
              </div>

              <!-- Max Participants -->
              <div class="flex items-center justify-between py-2">
                <label class="text-sm font-bold text-[#838799] uppercase tracking-wider">GIỚI HẠN NGƯỜI CHƠI</label>
                <div class="flex items-center gap-4 p-1.5 rounded-[8px]">
                  <button @click="form.max_participants > 0 && form.max_participants--"
                    class="w-8 h-8 flex items-center justify-center bg-[#EDEEF2] rounded-[4px] shadow-sm hover:bg-gray-200 transition-colors">
                    <MinusIcon class="w-4 h-4 text-[#3E414C]" stroke-width="2.5" />
                  </button>
                  <span class="text-xl font-bold text-[#3E414C] min-w-[30px] text-center">{{ form.max_participants
                    }}</span>
                  <button @click="form.max_participants++"
                    class="w-8 h-8 flex items-center justify-center bg-[#D72D36] rounded-[4px] shadow-sm hover:bg-[#c9252e] transition-colors">
                    <PlusIcon class="w-4 h-4 text-white" stroke-width="2.5" />
                  </button>
                </div>
              </div>

              <!-- Time Section -->
              <div class="space-y-4">
                <h3 class="text-sm font-bold text-[#838799] uppercase tracking-wider">THỜI GIAN</h3>

                <div class="grid grid-cols-2 gap-6">
                  <div>
                    <label class="block text-xs font-bold text-[#3E414C] mb-2">Ngày bắt đầu</label>
                    <VueDatePicker v-model="form.start_date" :locale="'vi'" auto-apply :format="'dd/MM/yyyy'" :clearable="false"
                      :enable-time-picker="false" class="custom-datepicker-icon"
                      input-class-name="!bg-white !border-gray-200 !text-[#3E414C] !font-bold !py-2.5 !rounded-[8px]">
                      <template #input-icon>
                        <div class="p-2.5">
                          <CalendarIcon class="w-5 h-5 text-[#D72D36]" />
                        </div>
                      </template>
                    </VueDatePicker>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-[#3E414C] mb-2">Giờ bắt đầu</label>
                    <VueDatePicker v-model="form.start_time_picker" time-picker auto-apply :clearable="false"
                      class="custom-datepicker-icon"
                      input-class-name="!bg-white !border-gray-200 !text-[#3E414C] !font-bold !py-2.5 !rounded-[8px]">
                      <template #input-icon>
                        <div class="p-2.5">
                          <ClockIcon class="w-5 h-5 text-[#D72D36]" />
                        </div>
                      </template>
                    </VueDatePicker>
                  </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-xs font-bold text-[#3E414C] my-3">Thời lượng</label>
                    <div class="flex gap-3">
                      <button v-for="duration in [1, 2, 3]" :key="duration" @click="form.duration = duration"
                        class="flex-1 py-2.5 rounded-[4px] border font-bold transition-all text-sm"
                        :class="form.duration === duration ? 'bg-[#D72D36] border-[#D72D36] text-white shadow-md shadow-red-100' : 'bg-white border-gray-200 text-[#838799] hover:border-gray-300'">
                        {{ duration }}H
                      </button>
                      <button @click="form.duration = 'custom'"
                        class="flex-1 py-2.5 rounded-[4px] border font-bold transition-all uppercase text-sm"
                        :class="form.duration === 'custom' ? 'bg-[#D72D36] border-[#D72D36] text-white shadow-md shadow-red-100' : 'bg-white border-gray-200 text-[#838799] hover:border-gray-300'">
                        TÙY CHỈNH
                      </button>
                    </div>
                  </div>

                  <!-- Repeat Settings -->
                  <div class="">
                    <div class="flex items-center justify-between">
                      <label class="block text-xs font-bold text-[#3E414C]">Thiết lập lặp lại</label>
                      <Toggle v-model="form.is_repeated" />
                    </div>

                    <div v-if="form.is_repeated" class="space-y-4 animate-in fade-in slide-in-from-top-2 duration-300">
                      <div class="grid grid-cols-4 gap-2">
                        <button v-for="unit in ['Tuần', 'Tháng', 'Quý', 'Năm']" :key="unit"
                          @click="form.repeat_unit = unit"
                          class="py-2.5 text-sm font-bold rounded-[4px] transition-all border"
                          :class="form.repeat_unit === unit ? 'bg-[#D72D36] border-[#D72D36] text-white shadow-md shadow-red-100' : 'bg-white border-gray-200 text-[#838799] hover:border-gray-300'">
                          {{ unit }}
                        </button>
                      </div>

                      <div v-if="form.repeat_unit === 'Tuần'" class="flex justify-between gap-2 p-1 bg-white">
                        <button v-for="day in daysOfWeek" :key="day.value" @click="toggleDay(day.value)"
                          class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold transition-all border"
                          :class="form.repeat_days.includes(day.value) ? 'bg-[#D72D36] text-white border-[#D72D36] shadow-red-100 shadow-md' : 'bg-white text-[#838799] border-gray-200 hover:bg-gray-50'">
                          {{ day.label }}
                        </button>
                      </div>

                      <div
                        class="bg-[#FFF5F5] border border-[#FBEAEB] px-4 py-2 rounded-[4px] flex items-center justify-center gap-3">
                        <ArrowPathRoundedSquareIcon class="w-5 h-5 text-[#D72D36]" />
                        <p class="text-sm font-normal text-[#D72D36]">
                          Kèo này sẽ tự động tạo vào <span class="font-bold">{{ formattedRepeatTime }}</span>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Button color="danger"
            class="flex-1 lg:flex-none lg:w-44 py-3 font-bold text-white rounded-[4px] shadow-lg shadow-red-200 hover:shadow-red-300 transition-all active:scale-95"
            @click="handleSubmit" :disabled="isLoading">
            <div v-if="isLoading"
              class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin mx-auto"></div>
            <span v-else>Tạo lịch</span>
          </Button>
          <Button color="white"
            class="flex-1 lg:flex-none lg:w-44 py-3 font-bold text-[#3E414C] bg-[#F0F2F5] rounded-[4px] border-none hover:bg-gray-200 transition-colors"
            @click="saveAsTemplate">
            Lưu mẫu
          </Button>
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-span-12 lg:col-span-4 space-y-6">
          <!-- Privacy -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-50">
            <h3 class="text-sm font-bold text-[#838799] uppercase tracking-wider mb-4">QUYỀN RIÊNG TƯ</h3>
            <div class="flex bg-[#EDEEF2] p-1 rounded-[8px] mb-3">
              <button @click="form.is_public = true"
                class="flex-1 py-2 text-xs font-bold rounded-[6px] transition-all flex items-center justify-center gap-2"
                :class="form.is_public ? 'bg-[#D72D36] text-white shadow-sm' : 'text-[#838799] hover:text-[#3E414C]'">
                <GlobeAsiaAustraliaIcon class="w-4 h-4" />
                <span>Mở rộng</span>
              </button>
              <button @click="form.is_public = false"
                class="flex-1 py-2 text-xs font-bold rounded-[6px] transition-all flex items-center justify-center gap-2"
                :class="!form.is_public ? 'bg-[#D72D36] text-white shadow-sm' : 'text-[#838799] hover:text-[#3E414C]'">
                <LockClosedIcon class="w-4 h-4" />
                <span>Riêng tư</span>
              </button>
            </div>
            <p class="text-sm font-medium italic text-[#00B377]" v-if="form.is_public">
              *Cho phép thành viên mời thêm khách mời
            </p>
            <p class="text-sm font-medium italic text-[#D72D36]" v-else>
              *Không cho phép thành viên mời thêm khách mời
            </p>
          </div>

          <!-- Payment -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-50">
            <div class="flex items-center gap-3 mb-6">
              <div
                class="w-10 h-10 bg-[#D72D36] rounded-full flex items-center justify-center text-white shadow-red-200 shadow-md">
                <PriceCheckIcon class="w-5 h-5 text-white" />
              </div>
              <h2 class="text-lg font-bold text-[#3E414C]">Cơ chế chia tiền</h2>
            </div>

            <div class="space-y-6">
              <!-- Type Tabs -->
              <div class="flex bg-[#EDEEF2] p-1 rounded-[8px]">
                <button v-for="type in splitTypes" :key="type.value" @click="form.split_type = type.value"
                  class="flex-1 py-1.5 text-xs font-bold rounded-[6px] transition-all"
                  :class="form.split_type === type.value ? 'bg-white text-[#3E414C] shadow-sm' : 'text-[#838799]'">
                  {{ type.label }}
                </button>
              </div>

              <!-- Amount -->
              <div>
                <label class="block text-xs font-bold text-[#838799] uppercase mb-2 tracking-widest">
                  {{ form.split_type === 'fixed' ? 'THU CỐ ĐỊNH' : 'TỔNG SỐ TIỀN' }}
                </label>
                <div
                  class="relative bg-[#F8F9FA] rounded-[8px] h-14 flex items-center px-4 border border-[#EDEEF2] group focus-within:border-[#D72D36]/30 transition-all">
                  <span class="text-xs font-bold text-[#838799] mr-2">VNĐ</span>
                  <input v-model="formattedTotalAmount" type="text"
                    class="bg-transparent border-none focus:outline-none flex-1 font-bold text-[#D72D36] text-xl"
                    placeholder="0" />
                  <span v-if="form.split_type === 'fixed'" class="text-xs font-medium text-[#838799]">/người</span>
                </div>
              </div>

              <!-- QR Upload -->
              <div v-if="form.split_type !== 'fund'">
                <label class="block text-xs font-bold text-[#838799] uppercase mb-2 tracking-widest">MÃ QR</label>
                <div @click="triggerQrUpload"
                  class="border-2 border-dashed border-[#EDEEF2] rounded-[12px] h-40 flex flex-col items-center justify-center gap-3 cursor-pointer hover:bg-gray-50 transition-colors group bg-[#FAFAFA]">
                  <input ref="qrInput" type="file" @change="handleQrUpload" class="hidden" accept="image/*" />
                  <template v-if="!form.payment_qr">
                    <div
                      class="w-10 h-10 bg-[#EDEEF2] rounded-full flex items-center justify-center text-[#838799] group-hover:bg-white group-hover:shadow-sm transition-all">
                      <PhotoIcon class="w-5 h-5" />
                    </div>
                    <div class="text-center">
                      <p class="text-sm font-bold text-[#3E414C]">Nhấn để tải ảnh lên</p>
                      <p class="text-[10px] text-[#A1A5B7]">PNG, JPG, GIF (Tối đa 5MB)</p>
                    </div>
                  </template>
                  <img v-else :src="form.payment_qr" class="w-full h-full object-contain p-2 rounded-xl" />
                </div>
              </div>

              <!-- Payment Note -->
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <EditNoteIcon class="h-5 w-5 text-[#838799]" />
                </div>
                <input v-model="form.payment_note" type="text" placeholder="Nội dung khoản thu"
                  class="w-full pl-10 pr-4 py-3 bg-[#F8F9FA] border-none rounded-[8px] focus:outline-none text-sm font-bold text-[#3E414C] placeholder:text-[#A1A5B7]" />
              </div>

              <!-- Guest Fee -->
              <div v-if="form.split_type !== 'fund'" class="space-y-3 pt-4 border-t border-gray-100">
                <div class="flex items-center justify-between">
                  <label class="text-xs font-bold text-[#838799]">Thu phí khách mời</label>
                  <Toggle v-model="form.has_guest_fee" />
                </div>
                <div v-if="form.has_guest_fee"
                  class="relative bg-[#F8F9FA] rounded-[8px] h-12 flex items-center px-4 animate-in fade-in slide-in-from-top-1">
                  <span class="text-xs font-bold text-[#838799] mr-2">VNĐ</span>
                  <input v-model="formattedGuestFee" type="text"
                    class="bg-transparent border-none focus:outline-none flex-1 font-bold text-[#3E414C]"
                    placeholder="0" />
                  <span class="text-xs font-medium text-[#838799]">/người</span>
                </div>
              </div>

              <div class="bg-[#EDEEF2] p-3 rounded-[8px] flex items-center gap-2">
                <InformationCircleIcon class="w-5 h-5 text-[#838799] mt-0.5 flex-shrink-0" />
                <p class="text-[11px] text-[#838799] font-medium leading-tight">
                  {{ splitHelperText }}
                </p>
              </div>
            </div>
          </div>

          <!-- Rules -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-50">
            <div class="flex items-center gap-3 mb-6">
              <div
                class="w-10 h-10 bg-[#D72D36] rounded-full flex items-center justify-center text-white shadow-red-200 shadow-md">
                <RuleIcon class="w-5 h-5 text-white" />
              </div>
              <h2 class="text-lg font-bold text-[#3E414C]">Quy định & Kỷ luật</h2>
            </div>

            <div class="space-y-6">
              <div class="flex items-center justify-between">
                <div>
                  <label class="block text-sm font-bold text-[#3E414C]">Hạn chót hủy kèo</label>
                  <p class="text-[11px] text-[#838799] mt-0.5">Trước giờ bắt đầu</p>
                </div>
                <div class="relative" v-click-outside="() => isDeadlineDropdownOpen = false">
                  <button @click="isDeadlineDropdownOpen = !isDeadlineDropdownOpen"
                    class="flex items-center gap-1 font-bold text-[#3E414C] hover:text-[#D72D36] transition-colors">
                    <span>{{ form.cancel_deadline }} Tiếng</span>
                    <ChevronDownIcon class="w-4 h-4 transition-transform duration-200"
                      :class="{ 'rotate-180': isDeadlineDropdownOpen }" stroke-width="2.5" />
                  </button>
                  <div v-if="isDeadlineDropdownOpen"
                    class="absolute right-0 mt-2 w-32 bg-white rounded-xl shadow-xl border border-gray-100 py-1 z-50 animate-in fade-in zoom-in duration-200 max-h-32 overflow-y-auto custom-scrollbar">
                    <button v-for="h in [1, 2, 4, 6, 12, 24, 48]" :key="h" @click="selectDeadline(h)"
                      class="w-full px-4 py-2 text-left text-sm hover:bg-[#FBEAEB] hover:text-[#D72D36] font-semibold transition-colors"
                      :class="{ 'text-[#D72D36] bg-[#FBEAEB]': form.cancel_deadline === h }">
                      {{ h }} Tiếng
                    </button>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <div>
                    <label class="block text-sm font-bold text-[#3E414C]">Phạt hủy muộn</label>
                    <p class="text-[11px] text-[#838799] mt-0.5">Tự động công nợ xấu theo mức phạt</p>
                  </div>
                  <Toggle v-model="form.has_cancel_penalty" />
                </div>
                <div v-if="form.has_cancel_penalty"
                  class="relative bg-[#F8F9FA] rounded-[8px] h-12 flex items-center px-4 animate-in fade-in slide-in-from-top-1">
                  <span class="text-xs font-bold text-[#838799] mr-2">VNĐ</span>
                  <input v-model="formattedCancelPenaltyAmount" type="text"
                    class="bg-transparent border-none focus:outline-none flex-1 font-bold text-[#D72D36] text-lg"
                    placeholder="0" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  ArrowLeftIcon,
  MagnifyingGlassIcon,
  IdentificationIcon,
  ChevronDownIcon,
  MinusIcon,
  PlusIcon,
  ArrowPathRoundedSquareIcon,
  GlobeAsiaAustraliaIcon,
  LockClosedIcon,
  PhotoIcon,
  InformationCircleIcon,
  ClockIcon,
  CalendarIcon
} from '@heroicons/vue/24/outline'
import FolderSpecialIcon from '@/assets/images/folder_special.svg'
import EditNoteIcon from "@/assets/images/edit_note.svg";
import PriceCheckIcon from '@/assets/images/price_check.svg'
import RuleIcon from '@/assets/images/rule.svg'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import Toggle from '@/components/atoms/Toggle.vue'
import Button from '@/components/atoms/Button.vue'
import { vClickOutside } from "@/directives/clickOutside";
import * as ClubService from '@/service/club'
import { toast } from 'vue3-toastify'
import dayjs from 'dayjs'
import debounce from 'lodash.debounce'

const router = useRouter()
const route = useRoute()
const clubId = route.params.id

const isLoading = ref(false)
const errors = ref({})
const locations = ref([])
const isDeadlineDropdownOpen = ref(false)
const isSelectingLocation = ref(false)
const qrInput = ref(null)

const daysOfWeek = [
  { label: 'T2', value: 1 },
  { label: 'T3', value: 2 },
  { label: 'T4', value: 3 },
  { label: 'T5', value: 4 },
  { label: 'T6', value: 5 },
  { label: 'T7', value: 6 },
  { label: 'CN', value: 0 }
]

const splitTypes = [
  { label: 'Chia đều', value: 'equal' },
  { label: 'Cố định', value: 'fixed' },
  { label: 'Quỹ bao', value: 'fund' }
]

const form = ref({
  title: '',
  type: 'other',
  description: '',
  address: '',
  latitude: null,
  longitude: null,
  max_participants: 4,
  start_date: dayjs().toDate(),
  start_time_picker: { hours: dayjs().hour(), minutes: 0 },
  duration: 2,
  is_repeated: false,
  repeat_unit: 'Tuần',
  repeat_days: [],
  is_public: true,
  split_type: 'equal',
  total_amount: 50000,
  payment_qr: null,
  payment_note: '',
  has_guest_fee: true,
  guest_fee: 20000,
  cancel_deadline: 4,
  has_cancel_penalty: true,
  cancel_penalty_amount: 20000
})

const formattedTotalAmount = computed({
  get: () => form.value.total_amount ? form.value.total_amount.toLocaleString('en-US') : '',
  set: (val) => {
    const rawValue = val.replace(/[^\d]/g, '')
    form.value.total_amount = rawValue ? parseInt(rawValue) : 0
  }
})

const formattedGuestFee = computed({
  get: () => form.value.guest_fee ? form.value.guest_fee.toLocaleString('en-US') : '',
  set: (val) => {
    const rawValue = val.replace(/[^\d]/g, '')
    form.value.guest_fee = rawValue ? parseInt(rawValue) : 0
  }
})

const formattedCancelPenaltyAmount = computed({
  get: () => form.value.cancel_penalty_amount ? form.value.cancel_penalty_amount.toLocaleString('en-US') : '',
  set: (val) => {
    const rawValue = val.replace(/[^\d]/g, '')
    form.value.cancel_penalty_amount = rawValue ? parseInt(rawValue) : 0
  }
})

const formattedRepeatTime = computed(() => {
  const timeStr = `${String(form.value.start_time_picker.hours).padStart(2, '0')}:${String(form.value.start_time_picker.minutes).padStart(2, '0')}`
  if (form.value.repeat_unit === 'Tuần') {
    const selectedDays = daysOfWeek
      .filter(d => form.value.repeat_days.includes(d.value))
      .map(d => d.value === 0 ? 'CN' : d.value + 1)
      .join('-')
    return `${timeStr} ${selectedDays} hàng tuần`
  }
  return timeStr
})

const splitHelperText = computed(() => {
  switch (form.value.split_type) {
    case 'equal': return 'Sau trận, App tự chia đều'
    case 'fixed': return 'Thu phí cố định từng người tham gia'
    case 'fund': return 'Sử dụng quỹ CLB để chi trả chi phí'
    default: return ''
  }
})

const goBack = () => router.back()

const toggleDay = (day) => {
  const idx = form.value.repeat_days.indexOf(day)
  if (idx === -1) form.value.repeat_days.push(day)
  else form.value.repeat_days.splice(idx, 1)
}

const selectDeadline = (h) => {
  form.value.cancel_deadline = h
  isDeadlineDropdownOpen.value = false
}

const triggerQrUpload = () => qrInput.value?.click()

const handleQrUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      toast.error('Kích thước ảnh không được vượt quá 5MB')
      return
    }
    const reader = new FileReader()
    reader.onload = (e) => form.value.payment_qr = e.target.result
    reader.readAsDataURL(file)
  }
}

const fetchLocations = async (query) => {
  if (!query || query.length < 2) return
  try {
    const res = await ClubService.searchLocation({ query })
    if (res?.data) {
      locations.value = res.data.map(p => ({
        name: p.description,
        place_id: p.place_id
      }))
    }
  } catch (e) {
    console.error(e)
  }
}

const debouncedFetch = debounce(fetchLocations, 500)

watch(() => form.value.address, (val) => {
  if (isSelectingLocation.value) {
    isSelectingLocation.value = false
    return
  }
  if (val && val.length >= 2) debouncedFetch(val)
  else locations.value = []
})

const onLocationSelect = async (loc) => {
  isSelectingLocation.value = true
  debouncedFetch.cancel()
  form.value.address = loc.name
  locations.value = []
  try {
    const res = await ClubService.locationDetail({ place_id: loc.place_id })
    const pos = res?.data?.result?.geometry?.location
    if (pos) {
      form.value.latitude = pos.lat
      form.value.longitude = pos.lng
    }
  } catch (e) {
    console.error(e)
  }
}

const handleSubmit = async () => {
  errors.value = {}
  if (!form.value.title.trim()) {
    errors.value.title = 'Tên sự kiện là bắt buộc'
    toast.error('Vui lòng nhập tên sự kiện')
    return
  }

  isLoading.value = true
  try {
    const start = dayjs(form.value.start_date)
      .hour(form.value.start_time_picker.hours)
      .minute(form.value.start_time_picker.minutes)

    const end = start.add(typeof form.value.duration === 'number' ? form.value.duration : 2, 'hour')

    const data = {
      title: form.value.title,
      type: form.value.type,
      description: form.value.description,
      address: form.value.address,
      latitude: form.value.latitude,
      longitude: form.value.longitude,
      max_participants: form.value.max_participants,
      start_time: start.format('YYYY-MM-DD HH:mm:ss'),
      end_time: end.format('YYYY-MM-DD HH:mm:ss'),
      is_public: form.value.is_public,
      repeat_days: form.value.is_repeated ? form.value.repeat_days : [],
      split_type: form.value.split_type,
      total_amount: form.value.total_amount,
      has_guest_fee: form.value.has_guest_fee,
      guest_fee: form.value.guest_fee,
      payment_qr: form.value.payment_qr,
      payment_note: form.value.payment_note,
      cancel_deadline: form.value.cancel_deadline,
      has_cancel_penalty: form.value.has_cancel_penalty,
      cancel_penalty_amount: form.value.cancel_penalty_amount
    }

    await ClubService.createActivity(clubId, data)
    toast.success('Tạo lịch sinh hoạt thành công')
    router.push({ name: 'club-detail', params: { id: clubId } })
  } catch (error) {
    console.log(error)
    toast.error(error.response?.data?.message || 'Có lỗi xảy ra')
  } finally {
    isLoading.value = false
  }
}

const saveAsTemplate = () => {
  toast.info('Tính năng lưu mẫu đang được phát triển')
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #E5E7EB;
  border-radius: 20px;
}

:deep(.custom-datepicker-icon .dp__input) {
  padding-left: 2.5rem !important;
}


</style>
