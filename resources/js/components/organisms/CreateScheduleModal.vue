<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999] p-4" @click.self="closeModal">
        <div class="bg-white rounded-[1.5rem] w-full max-w-[821px] overflow-hidden flex flex-col max-h-[648px] shadow-2xl">
          <!-- Header -->
          <div class="p-6 pb-4 flex items-center justify-between sticky top-0 bg-white z-10">
            <div class="flex items-center gap-3">
              <h3 class="text-xl font-bold text-[#3E414C]">Tạo lịch sinh hoạt</h3>
              <div class="px-3 py-1 bg-[#FBEAEB] text-[#D72D36] rounded-full text-sm font-semibold flex items-center gap-1.5 border border-[#FBEAEB] cursor-pointer">
                <ForderSpecialIcon class="w-4 h-4" />
                <span class="text-sm font-semibold">Mẫu</span>
              </div>
            </div>
            <button @click="closeModal" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
              <XMarkIcon class="w-7 h-7 text-gray-400" />
            </button>
          </div>

          <!-- Stepper -->
          <div class="px-8 py-2 flex items-center justify-start gap-4 relative mb-2">
            <template v-for="(step, index) in steps" :key="step.id">
              <div class="flex items-center gap-2 z-10">
                <div 
                  @click="goToStep(step.id)"
                  class="flex items-center gap-2 px-6 py-2 rounded-full border transition-all duration-300 cursor-pointer"
                  :class="[
                    currentStep === step.id 
                      ? 'bg-[#D72D36] text-white border-[#D72D36]' 
                      : 'bg-white text-[#3E414C] border-[#BBBFCC]'
                  ]"
                >
                  <component :is="step.icon" class="w-5 h-5" />
                  <span class="font-semibold whitespace-nowrap">{{ step.name }}</span>
                </div>
              </div>
              <!-- Connector -->
              <div v-if="index < steps.length - 1" class="flex justify-center">
                <ChevronRightIcon class="w-5 h-5 text-[#3E414C]" />
              </div>
            </template>
          </div>

          <!-- Body -->
          <div class="px-8 py-4 overflow-y-auto custom-scrollbar flex-1">
            <!-- Step 1: Thông tin cơ bản -->
            <div v-if="currentStep === 1" class="grid grid-cols-1 lg:grid-cols-2 gap-10">
              <!-- Left Column: Form Fields -->
              <div class="space-y-6">
                <!-- Event Name -->
                <div>
                  <label class="block text-sm font-semibold text-[#838799] uppercase mb-2 tracking-wider">TÊN SỰ KIỆN</label>
                  <input v-model="form.title" type="text"
                    class="w-full px-5 py-2.5 bg-[#F0F2F5] border-none rounded-[4px] focus:outline-none focus:ring-2 focus:ring-[#D72D36]/20 transition-all placeholder:text-[#9EA2B3] font-medium text-gray-900 text-sm"
                    :class="{ 'ring-2 ring-red-500/50 bg-red-50': errors.title }"
                    placeholder="VD: Kèo cố định 3-5-7" @input="errors.title = ''" />
                  <span v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title }}</span>
                </div>

                <!-- Description -->
                <div>
                  <label class="block text-sm font-semibold text-[#838799] uppercase mb-2 tracking-wider">GHI CHÚ</label>
                  <textarea v-model="form.description" rows="5" maxlength="500"
                    class="w-full px-5 py-4 bg-[#F0F2F5] border-none rounded-[4px] focus:outline-none focus:ring-2 focus:ring-[#D72D36]/20 transition-all resize-none placeholder:text-[#9EA2B3] font-medium text-gray-900 text-sm"
                    placeholder="Chia sẻ về sự kiện này"></textarea>
                </div>

                <!-- Max Participants -->
                <div class="flex items-center justify-between py-2">
                  <label class="text-sm font-semibold text-[#838799] uppercase tracking-wider">GIỚI HẠN NGƯỜI CHƠI</label>
                  <div class="flex items-center p-1.5 gap-4">
                    <button @click="form.max_participants > 0 && form.max_participants--" 
                      class="w-8 h-8 flex items-center justify-center bg-[#EDEEF2] rounded-[4px] shadow-sm border border-[#EDEEF2] hover:bg-gray-50 transition-colors">
                       <MinusIcon class="w-4 h-4 text-[#141519]" />
                    </button>
                    <span class="text-xl font-bold text-[#141519] min-w-[20px] text-center">{{ form.max_participants }}</span>
                    <button @click="form.max_participants++" 
                      class="w-8 h-8 flex items-center justify-center bg-[#D72D36] rounded-[4px] shadow-sm hover:bg-[#c9252e] transition-colors">
                      <PlusIcon class="w-4 h-4 text-white" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Right Column: Summary & Privacy -->
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-bold text-[#838799] uppercase mb-2 tracking-wider">THÔNG TIN CHUNG</label>
                  <div class="bg-[#FBEAEB] rounded-md border border-[#F7D5D7] overflow-hidden divide-y divide-[#F7D5D7]/50">
                    <!-- Time Card -->
                    <div class="flex flex-col">
                      <div 
                        @click="toggleSection('time')"
                        class="p-4 flex items-center gap-4 hover:bg-[#FBEAEB]/60 transition-colors cursor-pointer group"
                      >
                        <div class="w-12 h-12 bg-white rounded-md flex items-center justify-center shadow-sm border border-[#F7D5D7] text-[#D72D36]">
                          <ClockIcon class="w-6 h-6" />
                        </div>
                        <div class="flex-1">
                          <div class="flex items-center gap-2">
                            <span class="font-semibold text-[#3E414C]">
                               {{ dayjs(form.start_time).format('HH:mm') }} - {{ dayjs(form.end_time).format('HH:mm') }}
                            </span>
                            <span class="px-2 py-0.5 bg-[#D72D36] text-white text-[11px] rounded-[4px] font-bold">
                              {{ dayjs(form.end_time).diff(dayjs(form.start_time), 'hour') }} tiếng
                            </span>
                          </div>
                          <div class="text-xs text-[#6B6F80] font-normal capitalize">{{ dayjs(form.start_time).format('dddd, DD/MM/YYYY') }}</div>
                        </div>
                        <ChevronRightIcon 
                          class="w-5 h-5 text-[#D72D36]/40 group-hover:text-[#D72D36] transition-all duration-300"
                          :class="{ 'rotate-90': expandedSection === 'time' }"
                        />
                      </div>
                      
                      <!-- Time Dropdown -->
                      <div v-show="expandedSection === 'time'" class="px-4 pb-4 space-y-3">
                        <div class="grid grid-cols-1 gap-3">
                          <div class="datepicker-container">
                            <label class="block text-[10px] font-bold text-[#838799] uppercase mb-1">BẮT ĐẦU</label>
                            <VueDatePicker 
                              v-model="form.start_time" 
                              :locale="'vi'" 
                              auto-apply 
                              enable-time-picker
                              :format="'HH:mm dd/MM/yyyy'"
                              text-input
                              teleport="body"
                              class="custom-datepicker"
                            />
                          </div>
                          <div class="datepicker-container">
                            <label class="block text-[10px] font-bold text-[#838799] uppercase mb-1">KẾT THÚC</label>
                            <VueDatePicker 
                              v-model="form.end_time" 
                              :locale="'vi'" 
                              auto-apply 
                              enable-time-picker
                              :format="'HH:mm dd/MM/yyyy'"
                              text-input
                              teleport="body"
                              class="custom-datepicker"
                            />
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Location Card -->
                    <div class="flex flex-col">
                      <div 
                        @click="toggleSection('location')"
                        class="p-4 flex items-center gap-4 hover:bg-[#FBEAEB]/60 transition-colors cursor-pointer group"
                      >
                        <div class="w-12 h-12 bg-white rounded-md flex items-center justify-center shadow-sm border border-[#F7D5D7] text-[#D72D36]">
                          <MapPinIcon class="w-6 h-6" />
                        </div>
                        <div class="flex-1">
                          <div class="font-semibold text-[#3E414C] truncate max-w-[200px]">{{ form.address || 'Chọn địa điểm' }}</div>
                          <div v-if="form.address" class="text-xs text-[#6B6F80] font-normal">{{ form.address }}</div>
                        </div>
                        <ChevronRightIcon 
                          class="w-5 h-5 text-[#D72D36]/40 group-hover:text-[#D72D36] transition-all duration-300"
                          :class="{ 'rotate-90': expandedSection === 'location' }"
                        />
                      </div>

                      <!-- Location Dropdown -->
                      <div v-show="expandedSection === 'location'" class="px-4 pb-4">
                        <div class="relative">
                          <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                              <MapPinIcon class="h-4 w-4 text-[#D72D36]/60" />
                            </div>
                            <input 
                              v-model="form.address" 
                              type="text" 
                              placeholder="Tìm kiếm hoặc nhập địa điểm..."
                              class="w-full pl-9 pr-4 py-2.5 bg-[#F8F9FA] border border-[#EDEEF2] rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#D72D36]/10 focus:border-[#D72D36] transition-all placeholder:text-[#A1A5B7]"
                            />
                          </div>
                          
                          <div v-if="locations.length > 0" class="mt-2 bg-white border border-gray-100 rounded-xl shadow-xl max-h-56 overflow-y-auto custom-scrollbar z-20">
                            <div 
                              v-for="loc in locations" 
                              :key="loc.place_id"
                              @click="onLocationSelect(loc)"
                              class="px-4 py-3 text-sm flex items-start gap-3 hover:bg-[#F8F9FA] hover:text-[#D72D36] cursor-pointer border-b border-gray-50 last:border-none transition-all group relative overflow-hidden"
                            >
                              <div class="absolute left-0 top-0 bottom-0 w-1 bg-[#D72D36] transform -translate-x-full group-hover:translate-x-0 transition-transform"></div>
                              <div class="flex-1">
                                <div class="font-semibold leading-tight">{{ loc.name }}</div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Repeat Card -->
                    <div class="flex flex-col">
                      <div 
                        @click="toggleSection('repeat')"
                        class="p-4 flex items-center gap-4 hover:bg-[#FBEAEB]/60 transition-colors cursor-pointer group"
                      >
                        <div class="w-12 h-12 bg-white rounded-md flex items-center justify-center shadow-sm border border-[#F7D5D7] text-[#D72D36]">
                          <ArrowPathRoundedSquareIcon class="w-6 h-6" />
                        </div>
                        <div class="flex-1">
                          <div class="font-semibold text-[#3E414C]">{{ getRepeatText }}</div>
                        </div>
                        <ChevronRightIcon 
                          class="w-5 h-5 text-[#D72D36]/40 group-hover:text-[#D72D36] transition-all duration-300"
                          :class="{ 'rotate-90': expandedSection === 'repeat' }"
                        />
                      </div>

                      <!-- Repeat Dropdown -->
                      <div v-show="expandedSection === 'repeat'" class="px-4 pb-4">
                        <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-[#F7D5D7]">
                          <template v-for="day in daysOfWeek" :key="day.value">
                            <button 
                              @click="toggleDay(day.value)"
                              class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold transition-all"
                              :class="form.repeat_days.includes(day.value) ? 'bg-[#D72D36] text-white' : 'bg-[#F0F2F5] text-[#838799] hover:bg-gray-200'"
                            >
                              {{ day.label }}
                            </button>
                          </template>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Privacy Toggle -->
                <div class="space-y-2">
                  <label class="block text-sm font-bold text-[#838799] uppercase tracking-wider">QUYỀN RIÊNG TƯ</label>
                  <div class="flex bg-[#F0F2F5] p-1 rounded-[8px]">
                    <button 
                      @click="form.is_public = true"
                      class="flex-1 flex items-center justify-center gap-2 py-1.5 rounded-[4px] transition-all"
                      :class="form.is_public ? 'bg-[#D72D36] text-white shadow-lg' : 'text-gray-500 hover:text-gray-700'"
                    >
                      <GlobeAsiaAustraliaIcon class="w-4 h-4" />
                      <span class="text-sm font-semibold">Mở rộng</span>
                    </button>
                    <button 
                      @click="form.is_public = false"
                      class="flex-1 flex items-center justify-center gap-2 py-1.5 rounded-[4px] transition-all"
                      :class="!form.is_public ? 'bg-[#D72D36] text-white shadow-lg' : 'text-gray-500 hover:text-gray-700'"
                    >
                      <LockClosedIcon class="w-4 h-4" />
                      <span class="text-sm font-semibold">Riêng tư</span>
                    </button>
                  </div>
                  <p class="text-sm font-normal italic" :class="form.is_public ? 'text-[#00B377]' : 'text-[#D72D36]'">
                    {{ form.is_public ? '*Cho phép thành viên mời thêm khách mời' : '*Không cho phép thành viên mời thêm khách mời' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Step 2: Cơ chế chia tiền -->
            <div v-else-if="currentStep === 2">
              <div class="grid grid-cols-1 gap-10 lg:grid-cols-2">
                <!-- Left Column -->
                <div class="space-y-6">
                  <!-- Split Type Tabs -->
                  <div class="flex bg-[#EDEEF2] p-1.5 rounded-[8px]">
                    <button 
                      v-for="type in splitTypes" 
                      :key="type.value"
                      @click="form.split_type = type.value"
                      class="flex-1 py-1.5 text-sm font-bold rounded-[4px] transition-all"
                      :class="form.split_type === type.value ? 'bg-white text-[#141519] shadow-sm' : 'text-[#838799] hover:text-[#141519]'"
                    >
                      {{ type.label }}
                    </button>
                  </div>

                  <!-- Total Amount -->
                  <div>
                    <label class="block text-sm font-semibold text-[#838799] uppercase mb-3 tracking-widest">
                      {{ form.split_type === 'fixed' ? 'THU CỐ ĐỊNH' : 'TỔNG SỐ TIỀN' }}
                    </label>
                    <div class="relative bg-[#F0F2F5] rounded-[4px] overflow-hidden group border border-transparent focus-within:border-[#D72D36]/20 transition-all">
                      <div class="absolute left-5 top-1/2 -translate-y-1/2 text-xs font-bold text-[#838799]">VNĐ</div>
                      <input 
                        v-model="formattedTotalAmount" 
                        type="text"
                        inputmode="numeric"
                        @keypress="onlyNumbers"
                        class="w-full pl-16 py-3 bg-transparent border-none focus:outline-none transition-all font-bold text-[#D72D36] text-[1.75rem]"
                        :class="form.split_type === 'fixed' ? 'pr-20' : 'pr-6'"
                        placeholder="0"
                      />
                      <div v-if="form.split_type === 'fixed'" class="absolute right-5 top-1/2 -translate-y-1/2 text-xs font-medium text-[#838799]">/người</div>
                    </div>
                  </div>

                  <!-- Guest Fee -->
                  <div v-if="form.split_type !== 'fund'" class="space-y-4 pb-4 border-b border-[#EDEEF2]">
                    <Toggle 
                      label="Thu phí khách mời" 
                      description="Bật để thiết lập phí cho khách mời" 
                      :value="form.has_guest_fee" 
                      @update="val => form.has_guest_fee = val" 
                    />
                    
                    <div v-if="form.has_guest_fee" class="relative bg-[#F0F2F5] rounded-[4px] overflow-hidden group border border-transparent focus-within:border-[#D72D36]/20 transition-all">
                      <div class="absolute left-5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-[#838799]">VNĐ</div>
                      <input 
                        v-model="formattedGuestFee" 
                        type="text"
                        inputmode="numeric"
                        @keypress="onlyNumbers"
                        class="w-full pl-16 pr-20 py-2 bg-transparent border-none focus:outline-none transition-all font-bold text-[#3E414C] text-base"
                        placeholder="0"
                      />
                      <div class="absolute right-5 top-1/2 -translate-y-1/2 text-xs font-medium text-[#838799]">/người</div>
                    </div>
                  </div>

                  <!-- Fund Note (Left column when fund selected) -->
                  <div v-if="form.split_type === 'fund'">
                    <label class="block text-xs font-bold text-[#838799] uppercase mb-1.5 tracking-widest">NỘI DUNG KHOẢN CHI</label>
                    <div class="relative bg-[#F0F2F5] rounded-[4px] overflow-hidden group border border-transparent focus-within:border-[#D72D36]/20 transition-all">
                      <div class="absolute left-3 top-1/2 -translate-y-1/2">
                        <EditNoteIcon class="w-5 h-5 text-[#838799]" />
                      </div>
                      <input 
                        v-model="form.payment_note"
                        type="text"
                        class="w-full pl-10 pr-6 py-3 bg-transparent border-none focus:outline-none transition-all font-medium text-[#3E414C] text-[13px]"
                        placeholder="VD: Tiền sân, nước, cầu..."
                      />
                    </div>
                  </div>

                  <!-- Helper Info -->
                  <div class="bg-[#F0F2F5] p-3 rounded-[8px] flex items-center gap-3">
                    <InformationCircleIcon class="flex-shrink-0 w-5 h-5 text-[#141519]" />
                    <p class="text-xs text-[#838799] font-normal">
                      {{ getSplitHelperText }}
                    </p>
                  </div>
                </div>

                <!-- Right Column -->
                <div v-if="form.split_type !== 'fund'" class="space-y-6">
                  <!-- QR Upload -->
                  <div>
                    <label class="block text-xs font-bold text-[#838799] uppercase mb-1.5 tracking-widest">MÃ QR</label>
                    <div 
                      @click="triggerQrUpload"
                      class="border-2 border-dashed border-[#EDEEF2] rounded-[8px] h-[248px] flex flex-col items-center justify-center gap-4 cursor-pointer hover:bg-gray-50 transition-colors relative overflow-hidden group"
                    >
                      <input 
                        ref="qrInput"
                        type="file" 
                        class="hidden" 
                        accept="image/*"
                        @change="handleQrUpload"
                      />
                      <template v-if="!form.payment_qr">
                        <div class="w-12 h-12 bg-[#F0F2F5] rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                          <PhotoIcon class="w-6 h-6 text-[#838799]" />
                        </div>
                        <div class="text-center">
                          <p class="text-sm font-bold text-[#3E414C]">Nhấn để tải ảnh lên</p>
                          <p class="text-[10px] text-[#A1A5B7]">PNG, JPG, GIF (Tối đa 5MB)</p>
                        </div>
                      </template>
                      <img v-else :src="form.payment_qr" class="w-full h-full object-contain p-2" />
                    </div>
                  </div>

                  <!-- Payment Note -->
                  <div>
                    <div class="relative bg-[#F0F2F5] rounded-[4px] overflow-hidden group border border-transparent focus-within:border-[#D72D36]/20 transition-all">
                      <div class="absolute left-3 top-1/2 -translate-y-1/2">
                        <EditNoteIcon class="w-5 h-5 text-[#838799]" />
                      </div>
                      <input 
                        v-model="form.payment_note"
                        type="text"
                        class="w-full pl-10 pr-6 py-3 bg-transparent border-none focus:outline-none transition-all font-medium text-[#3E414C] text-[13px]"
                        placeholder="Nội dung khoản thu"
                      />
                    </div>
                  </div>
                </div>
                <div v-if="form.split_type === 'fund'" class="space-y-6 flex justify-center items-center">
                  <p class="text-sm font-semibold text-[#838799] uppercase mb-1.5 tracking-widest">Sử dụng quỹ câu lạc bộ chi trả</p>
                </div>
              </div>
            </div>

            <!-- Step 3: Quy định & Kỷ luật -->
            <div v-else-if="currentStep === 3" class="grid grid-cols-1 lg:grid-cols-2 gap-10">
              <div class="space-y-8">
                <!-- Cancellation Deadline -->
                <div class="flex items-center justify-between py-2">
                  <div>
                    <label class="block text-sm font-bold text-[#3E414C]">Hạn chót hủy kèo</label>
                    <p class="text-xs text-[#838799] mt-0.5">Trước giờ bắt đầu</p>
                  </div>
                  <div class="relative" v-click-outside="closeDeadlineDropdown">
                    <div 
                      @click="isDeadlineOpen = !isDeadlineOpen"
                      class="flex items-center gap-2 py-2 cursor-pointer font-bold text-[#3E414C] transition-all"
                    >
                      <span>{{ form.cancel_deadline }} Tiếng</span>
                      <ChevronDownIcon 
                        class="w-5 h-5 text-[#838799] transition-transform duration-300" 
                        :class="{ 'rotate-180': isDeadlineOpen }" 
                      />
                    </div>

                    <!-- Custom Dropdown Menu -->
                    <Transition
                      enter-active-class="transition duration-100 ease-out"
                      enter-from-class="transform scale-95 opacity-0"
                      enter-to-class="transform scale-100 opacity-100"
                      leave-active-class="transition duration-75 ease-in"
                      leave-from-class="transform scale-100 opacity-100"
                      leave-to-class="transform scale-95 opacity-0"
                    >
                      <div v-if="isDeadlineOpen" class="absolute right-0 z-50 mt-1 w-[160px] bg-white rounded-xl shadow-xl border border-gray-100 py-1 max-h-[150px] overflow-y-auto custom-scrollbar">
                        <div
                          v-for="h in [1, 2, 4, 6, 12, 24, 48]"
                          :key="h"
                          @click="selectDeadline(h)"
                          class="px-4 py-3 hover:bg-[#FBEAEB] hover:text-[#D72D36] cursor-pointer text-[#3E414C] text-sm font-semibold transition-colors text-start"
                          :class="{ 'bg-[#FBEAEB] text-[#D72D36] border-r-4 border-[#D72D36]': form.cancel_deadline === h }"
                        >
                          {{ h }} Tiếng
                        </div>
                      </div>
                    </Transition>
                  </div>
                </div>

                <!-- Late Cancellation Penalty -->
                <div class="space-y-4">
                  <Toggle 
                    label="Phạt hủy muộn" 
                    description="Tự động công nợ xấu theo mức phạt" 
                    :value="form.has_cancel_penalty" 
                    @update="val => form.has_cancel_penalty = val" 
                  />

                  <div v-if="form.has_cancel_penalty" class="bg-[#F8F9FA] rounded-[4px] p-4 flex items-center group focus-within:ring-1 focus-within:ring-[#D72D36]/20 transition-all">
                    <span class="text-xs font-bold text-[#838799] mr-4">VNĐ</span>
                    <input 
                      v-model="formattedCancelPenaltyAmount"
                      type="text"
                      inputmode="numeric"
                      @keypress="onlyNumbers"
                      class="bg-transparent border-none focus:outline-none text-[#D72D36] font-bold text-[1.5rem] w-full"
                      placeholder="0"
                    />
                  </div>
                </div>
              </div>
              <!-- Right column empty to match design layout -->
              <div class="hidden lg:block"></div>
            </div>
          </div>

          <!-- Footer -->
          <div class="p-6 pt-2 bg-white flex justify-end">
            <Button size="md" color="danger" class="w-full lg:w-44 bg-[#D72D36] hover:bg-[#c9252e] text-white py-3 font-bold rounded-2xl shadow-xl transition-transform active:scale-95" @click="nextStep" :disabled="isLoading">
              <span v-if="isLoading" class="w-6 h-6 border-3 border-white/30 border-t-white rounded-full animate-spin mr-2 inline-block align-middle"></span>
              <span>{{ currentStep === 3 ? (isLoading ? 'Đang lưu...' : 'Tạo lịch') : 'Tiếp' }}</span>
            </Button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, onUnmounted, nextTick, computed } from 'vue'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { 
  XMarkIcon, 
  MapPinIcon, 
  ClockIcon, 
  ChevronRightIcon, 
  IdentificationIcon,
  LockClosedIcon,
  MinusIcon,
  PlusIcon,
  ArrowPathRoundedSquareIcon,
  InformationCircleIcon,
  PhotoIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'
import EditNoteIcon from "@/assets/images/edit_note.svg";
import PriceCheckIcon from '@/assets/images/price_check.svg'
import RuleIcon from '@/assets/images/rule.svg'
import { GlobeAsiaAustraliaIcon } from '@heroicons/vue/24/solid'
import { vClickOutside } from "@/directives/clickOutside";
import Button from '@/components/atoms/Button.vue'
import Toggle from '@/components/atoms/Toggle.vue'
import ForderSpecialIcon from '@/assets/images/folder_special.svg'
import * as ClubService from '@/service/club'
import debounce from 'lodash.debounce'
import { toast } from 'vue3-toastify'
import dayjs from 'dayjs'
import 'dayjs/locale/vi'

dayjs.locale('vi')

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  clubId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['update:modelValue', 'save'])

const isLoading = ref(false)
const isSelecting = ref(false)
const locations = ref([])
const errors = ref({})
const currentStep = ref(1)
const expandedSection = ref(null) // 'time', 'location', 'repeat'
const isDeadlineOpen = ref(false)

const daysOfWeek = [
  { label: 'T2', value: 1 },
  { label: 'T3', value: 2 },
  { label: 'T4', value: 3 },
  { label: 'T5', value: 4 },
  { label: 'T6', value: 5 },
  { label: 'T7', value: 6 },
  { label: 'CN', value: 0 }
]

const steps = [
  { id: 1, name: 'Thông tin cơ bản', icon: IdentificationIcon },
  { id: 2, name: 'Cơ chế chia tiền', icon: PriceCheckIcon },
  { id: 3, name: 'Quy định & Kỷ luật', icon: RuleIcon }
]
const qrInput = ref(null)

const splitTypes = [
  { label: 'Chia đều', value: 'equal' },
  { label: 'Cố định', value: 'fixed' },
  { label: 'Quỹ bao', value: 'fund' }
]

const getDefaultForm = () => ({
  title: '',
  type: 'other',
  start_time: dayjs().toDate(),
  end_time: dayjs().add(2, 'hour').toDate(),
  address: '',
  latitude: null,
  longitude: null,
  max_participants: 4,
  member_fee: 0,
  guest_fee: 0,
  description: '',
  is_public: true,
  repeat_days: [],
  split_type: 'equal',
  total_amount: 0,
  has_guest_fee: false,
  payment_qr: null,
  payment_note: '',
  cancel_deadline: 4,
  has_cancel_penalty: true,
  cancel_penalty_amount: 20000
})

const form = ref(getDefaultForm())

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

const onlyNumbers = (event) => {
  if (!/^[0-9]$/.test(event.key)) {
    event.preventDefault()
  }
}

// Reset form when modal opens
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    form.value = getDefaultForm()
    errors.value = {}
    currentStep.value = 1
  }
})

const closeModal = () => {
  if (isLoading.value) return
  emit('update:modelValue', false)
}

const toggleSection = (section) => {
  if (expandedSection.value === section) {
    expandedSection.value = null
  } else {
    expandedSection.value = section
  }
}

const toggleDay = (day) => {
  const index = form.value.repeat_days.indexOf(day)
  if (index === -1) {
    form.value.repeat_days.push(day)
  } else {
    form.value.repeat_days.splice(index, 1)
  }
}

const getRepeatText = computed(() => {
  if (form.value.repeat_days.length === 0) return 'Không lặp lại'
  if (form.value.repeat_days.length === 7) return 'Lặp lại hàng tuần'
  
  const selectedLabels = daysOfWeek
    .filter(d => form.value.repeat_days.includes(d.value))
    .map(d => d.label)
  
  return `Lặp lại: ${selectedLabels.join(', ')}`
})

const getSplitHelperText = computed(() => {
  switch (form.value.split_type) {
    case 'equal':
      return 'Sau trận, App tự chia đều'
    case 'fixed':
      return 'Khoản thu cố định từng người'
    case 'fund':
      return 'Quỹ tự động thanh toán chi phí'
    default:
      return ''
  }
})

const triggerQrUpload = () => {
  qrInput.value?.click()
}

const handleQrUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      toast.error('Kích thước ảnh không được vượt quá 5MB')
      return
    }
    const reader = new FileReader()
    reader.onload = (e) => {
      form.value.payment_qr = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const goToStep = (stepId) => {
  // Allow switching steps, check validation if moving forward from step 1
  if (stepId > currentStep.value && currentStep.value === 1) {
    if (!validateStep1()) return
  }
  currentStep.value = stepId
}

const nextStep = () => {
  if (currentStep.value < 3) {
    if (currentStep.value === 1 && !validateStep1()) return
    currentStep.value++
  } else {
    handleSubmit()
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const selectDeadline = (h) => {
  form.value.cancel_deadline = h
  isDeadlineOpen.value = false
}

const closeDeadlineDropdown = () => {
  isDeadlineOpen.value = false
}

const validateStep1 = () => {
  errors.value = {}
  let isValid = true

  if (!form.value.title?.trim()) {
    errors.value.title = 'Tên sự kiện là bắt buộc'
    isValid = false
  }

  if (dayjs(form.value.end_time).isBefore(dayjs(form.value.start_time))) {
    toast.error('Thời gian kết thúc phải sau thời gian bắt đầu')
    isValid = false
  }

  return isValid
}

// Location Search logic
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
    console.error('Error fetching locations:', e)
  }
}

const debouncedFetchLocations = debounce(fetchLocations, 500)

watch(() => form.value.address, (val) => {
  if (!isSelecting.value && val) {
    debouncedFetchLocations(val)
  }
})

const onLocationSelect = async (item) => {
  debouncedFetchLocations.cancel()
  if (item && item.place_id) {
    isSelecting.value = true
    form.value.address = item.name
    await nextTick()
    isSelecting.value = false

    try {
      const detail = await ClubService.locationDetail({ place_id: item.place_id })
      if (detail?.data?.result?.geometry?.location) {
        form.value.latitude = detail.data.result.geometry.location.lat
        form.value.longitude = detail.data.result.geometry.location.lng
      }
    } catch (e) {
      console.error('Error fetching location detail:', e)
    }
  }
}

onUnmounted(() => {
  debouncedFetchLocations.cancel()
})

const validateForm = () => {
  errors.value = {}
  let isValid = true

  if (!form.value.title?.trim()) {
    errors.value.title = 'Tiêu đề hoạt động là bắt buộc'
    isValid = false
  }

  if (dayjs(form.value.end_time).isBefore(dayjs(form.value.start_time))) {
    toast.error('Thời gian kết thúc phải sau thời gian bắt đầu')
    isValid = false
  }

  return isValid
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  isLoading.value = true
  try {
    const feePerPerson = form.value.split_type === 'equal' 
      ? (form.value.total_amount / (form.value.max_participants || 1)) 
      : form.value.total_amount;
    
    const penaltyPercentage = (form.value.has_cancel_penalty && feePerPerson > 0)
      ? Math.min(100, Math.round((form.value.cancel_penalty_amount / feePerPerson) * 100))
      : 0;

    const payload = {
      title: form.value.title,
      type: form.value.type,
      start_time: dayjs(form.value.start_time).format('YYYY-MM-DD HH:mm:ss'),
      end_time: dayjs(form.value.end_time).format('YYYY-MM-DD HH:mm:ss'),
      location: form.value.address,
      venue_address: form.value.address,
      description: form.value.description,
      max_participants: form.value.max_participants || null,
      fee_amount: form.value.split_type === 'fund' ? 0 : form.value.total_amount,
      fee_split_type: form.value.split_type === 'fund' ? 'fixed' : form.value.split_type,
      guest_fee: form.value.guest_fee,
      allow_member_invite: form.value.is_public,
      is_recurring: form.value.repeat_days.length > 0,
      recurring_schedule: form.value.repeat_days.length > 0 ? form.value.repeat_days.join(',') : null,
      cancellation_deadline: dayjs(form.value.start_time).subtract(form.value.cancel_deadline, 'hour').format('YYYY-MM-DD HH:mm:ss'),
      penalty_percentage: penaltyPercentage
    }

    await ClubService.createActivity(props.clubId, payload)
    toast.success('Tạo lịch hoạt động thành công')
    emit('save')
    closeModal()
  } catch (error) {
    console.error('Error creating activity:', error)
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors
      Object.keys(backendErrors).forEach(key => {
        errors.value[key] = backendErrors[key][0]
      })
    }
    toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi tạo lịch')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
  transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
  transform: scale(0.9) translateY(20px);
  opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #F1F1F1;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #E5E7EB;
}

/* Hide spin buttons */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
  appearance: textfield;
}

/* Custom group hover transitions */
.group:hover .group-hover\:text-\[#D72D36\] {
  color: #D72D36;
}

/* Custom styles for VueDatePicker to match theme */
:deep(.custom-datepicker) {
  --dp-background-color: #ffffff;
  --dp-text-color: #3E414C;
  --dp-hover-color: #f3f3f3;
  --dp-hover-text-color: #3E414C;
  --dp-column-gap: 10px;
  --dp-row-gap: 5px;
  --dp-border-radius: 6px;
  --dp-cell-border-radius: 4px;
  --dp-primary-color: #D72D36;
  --dp-primary-text-color: #ffffff;
  --dp-secondary-color: #838799;
}

:deep(.custom-datepicker .dp__input) {
  padding: 8px 12px 8px 35px;
  background-color: white;
  border: 1px solid #F7D5D7;
  font-size: 12px;
}

:deep(.custom-datepicker .dp__input:focus) {
  border-color: #D72D36;
  outline: none;
  box-shadow: 0 0 0 2px rgba(215, 45, 54, 0.1);
}

:deep(.custom-datepicker .dp__input_icon) {
  padding-left: 10px;
  color: #D72D36;
}
</style>
