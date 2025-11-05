import { computed } from 'vue'

export function useFormattedDate(date) {
  const formattedDate = computed(() => {
    if (!date.value) return ''
    const d = new Date(date.value)
    const day = d.getDate().toString().padStart(2, '0')
    const month = (d.getMonth() + 1).toString().padStart(2, '0')
    const hour = d.getHours().toString().padStart(2, '0')
    const minute = d.getMinutes().toString().padStart(2, '0')

    return `T${d.getDay() + 1} ${day} Tháng ${month} lúc ${hour}:${minute}`
  })

  return { formattedDate }
}
