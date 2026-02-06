import dayjs from 'dayjs'

export function useTimeFormat() {
    const toHourMinute = (time) => {
      if (!time || typeof time !== 'string') return '';
      return time.slice(0, 5);
    };
    return { toHourMinute };
  }
  

export function diffTimeText(start, end) {
  if (!start || !end) return ''

  const diffMinutes = dayjs(end).diff(dayjs(start), 'minute')

  if (diffMinutes <= 0) return '0 phút'

  const hours = Math.floor(diffMinutes / 60)
  const minutes = diffMinutes % 60

  if (hours > 0 && minutes > 0) {
    return `${hours} h ${minutes} p`
  }

  if (hours > 0) {
    return `${hours} tiếng`
  }

  return `${minutes} phút`
}