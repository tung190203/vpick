<script setup>
import { reactive, computed } from 'vue'
import useVuelidate from '@vuelidate/core'
import { required, email, minLength, helpers } from '@vuelidate/validators'
import { useRoute, useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import Button from '@/components/atoms/Button.vue'
import { useUserStore } from '@/store/auth'

const userStore = useUserStore()
const route = useRoute()
const router = useRouter()

const data = reactive({
    email: route.query.email || '',
    token: route.query.token || '',
    password: '',
    password_confirmation: ''
})

const rules = computed(() => ({
    email: {
        required: helpers.withMessage('Email không được để trống', required),
        email: helpers.withMessage('Email không đúng định dạng', email)
    },
    token: {
        required: helpers.withMessage('Token không được để trống', required)
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
            setTimeout(() => {
                router.push({ name: 'login' })
            }, 1000)
        } catch (error) {
            toast.error(error.response?.data?.message || 'Đặt lại mật khẩu thất bại!')
        }
    }
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
        <div class="w-full max-w-md p-8 bg-white rounded shadow">
            <h2 class="text-2xl font-bold mb-6 text-center">Đặt lại mật khẩu</h2>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <input type="email" placeholder="Email" v-model="data.email" disabled
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <span v-for="err in v$.email.$errors" :key="err.$uid" class="text-red-500 text-sm">
                        {{ err.$message }}
                    </span>
                </div>

                <div>
                    <input type="hidden" placeholder="Token" v-model="data.token"
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <span v-for="err in v$.token.$errors" :key="err.$uid" class="text-red-500 text-sm">
                        {{ err.$message }}
                    </span>
                </div>

                <div>
                    <input type="password" placeholder="Mật khẩu mới" v-model="data.password"
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <span v-for="err in v$.password.$errors" :key="err.$uid" class="text-red-500 text-sm">
                        {{ err.$message }}
                    </span>
                </div>

                <div>
                    <input type="password" placeholder="Xác nhận mật khẩu" v-model="data.password_confirmation"
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <span v-if="v$.password_confirmation.$dirty && v$.password_confirmation.$errors.length"
                        class="text-red-500 text-sm">
                        {{ v$.password_confirmation.$errors[0].$message }}
                    </span>

                </div>

                <Button type="submit" class="w-full">Đặt lại mật khẩu</Button>
            </form>
        </div>
    </div>
</template>
