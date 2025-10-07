<script setup>
import { reactive, computed, ref } from 'vue'
import useVuelidate from '@vuelidate/core'
import { required, email, minLength, helpers } from '@vuelidate/validators'
import { useRoute, useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import Button from '@/components/atoms/Button.vue'
import { useUserStore } from '@/store/auth'
import { CheckCircleIcon } from '@heroicons/vue/24/solid'

const userStore = useUserStore()
const route = useRoute()
const router = useRouter()
const isSuccess = ref(false)

const data = reactive({
    email: route.query.email || '',
    password: '',
    password_confirmation: ''
})

const rules = computed(() => ({
    email: {
        required: helpers.withMessage('Email không được để trống', required),
        email: helpers.withMessage('Email không đúng định dạng', email)
    },
    password: {
        required: helpers.withMessage('Mật khẩu không được để trống', required),
        minLength: helpers.withMessage('Mật khẩu tối thiểu 6 ký tự', minLength(6))
    },
    password_confirmation: {
        required: helpers.withMessage('Vui lòng xác nhận mật khẩu', required),
        sameAsPassword: helpers.withMessage('Mật khẩu xác nhận không khớp', (value, vm) => {
            return value === vm.password
        })
    }
}))

const v$ = useVuelidate(rules, data)

const submit = async () => {
    v$.value.$touch()
    if (!v$.value.$invalid) {
        try {
            await userStore.resetPassword(data)
            toast.success('Đặt lại mật khẩu thành công!')
            isSuccess.value = true
        } catch (error) {
            toast.error(error.response?.data?.message || 'Đặt lại mật khẩu thất bại!')
        }
    }
}
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center px-4">
        <img src="@/assets/images/logo-splash.svg" class="w-[60%]" alt="">
        <div class="text-center mb-8 mt-8">
            <h1 class="text-white text-2xl mb-2">Đặt lại mật khẩu</h1>
            <p class="text-sm text-white font-light">
                Tận hưởng toàn bộ tính năng của Pickleball, bao gồm cập nhật giải đấu,
                bảng xếp hạng và thông báo trận đấu độc quyền!
            </p>
        </div>

        <div v-if="!isSuccess" class="w-full max-w-md p-8 bg-white rounded shadow">
            <form @submit.prevent="submit" class="space-y-4">
                <input type="hidden" v-model="data.email" />

                <div>
                    <input
                        type="password"
                        placeholder="Mật khẩu mới"
                        v-model="data.password"
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                    <span
                        v-for="err in v$.password.$errors"
                        :key="err.$uid"
                        class="text-red-500 text-sm"
                    >
                        {{ err.$message }}
                    </span>
                </div>

                <div>
                    <input
                        type="password"
                        placeholder="Xác nhận mật khẩu"
                        v-model="data.password_confirmation"
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                    <span
                        v-if="v$.password_confirmation.$dirty && v$.password_confirmation.$errors.length"
                        class="text-red-500 text-sm"
                    >
                        {{ v$.password_confirmation.$errors[0].$message }}
                    </span>
                </div>

                <Button type="submit" class="w-full bg-primary hover:bg-secondary">
                    Đặt lại mật khẩu
                </Button>
            </form>
        </div>

        <div v-else class="w-full max-w-md p-8 bg-white rounded shadow text-center flex flex-col items-center">
            <div class="w-20 h-20 flex items-center justify-center bg-green-100 rounded-full mb-4">
                <CheckCircleIcon class="text-green-500 w-10 h-10" />
            </div>
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Thay đổi mật khẩu thành công!</h2>
            <Button @click="router.push({ name: 'login' })" class="w-full bg-primary hover:bg-secondary">Đăng nhập ngay</Button>
        </div>
    </div>
</template>
