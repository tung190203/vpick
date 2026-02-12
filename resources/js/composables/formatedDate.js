import { computed } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/vi'

export function useFormattedDate(date) {
  const formattedDate = computed(() => {
    if (!date.value) return ''

    const d = new Date(date.value)

    const day = d.getDate().toString().padStart(2, '0')
    const month = (d.getMonth() + 1).toString().padStart(2, '0')
    const hour = d.getHours().toString().padStart(2, '0')
    const minute = d.getMinutes().toString().padStart(2, '0')

    const dayOfWeek = d.getDay() === 0 ? 'CN' : `T${d.getDay() + 1}`

    return `${dayOfWeek} ${day} Tháng ${month} lúc ${hour}:${minute}`
  })

  return { formattedDate }
}

export const getVietnameseDay = (date) => {
    const days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
    return days[dayjs(date).day()]
}

