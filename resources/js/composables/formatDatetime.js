export const formatDatetime = (dateString) => {
    if (!dateString) return '';

    const date = new Date(dateString);

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are zero-based
    const year = date.getFullYear();

    return `${day}-${month}-${year}`;
}

export function useFormatDate() {
    function formatDateTime(dateString) {
      if (!dateString) return ''
  
      const date = new Date(dateString)
      const weekdayFormatter = new Intl.DateTimeFormat('vi-VN', { weekday: 'long' })
      const dayName = weekdayFormatter.format(date)
      const day = String(date.getDate()).padStart(2, '0')
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const hours = String(date.getHours()).padStart(2, '0')
      const minutes = String(date.getMinutes()).padStart(2, '0')
  
      return `${dayName}, ${day}/${month} ${hours}:${minutes}`
    }
  
    return { formatDateTime }
  }

export function formatEventDate(dateString) {
  const date = new Date(dateString);

  const daysOfWeek = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];

  const months = [
    'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
    'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
  ];

  const dayOfWeek = daysOfWeek[date.getDay()];
  const day = date.getDate();
  const month = months[date.getMonth()];
  const hours = date.getHours().toString().padStart(2, '0');
  const minutes = date.getMinutes().toString().padStart(2, '0');

  return `${dayOfWeek} ${day} ${month} lúc ${hours}:${minutes}`;
}