export const formatDatetime = (dateString, separator = '-') => {
    if (!dateString) return ''

    const date = new Date(dateString)

    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const year = date.getFullYear()

    return `${day}${separator}${month}${separator}${year}`
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

export const formatDateForDB = (date) => {
  if (!date) return "";
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

export const convertToDateObject = (dateStr) => {
  if (!dateStr) return null;
  
  // Nếu đã là Date object thì return luôn
  if (dateStr instanceof Date) return dateStr;
  
  // Nếu là string DD-MM-YYYY thì chuyển sang Date object
  if (typeof dateStr === 'string' && dateStr.includes('-')) {
      const parts = dateStr.split('-');
      if (parts.length === 3) {
          // Kiểm tra xem có phải DD-MM-YYYY không
          if (parts[0].length <= 2) {
              // DD-MM-YYYY
              return new Date(parts[2], parts[1] - 1, parts[0]);
          }
      }
  }
  
  return dateStr;
};

export const getJoinedDate = (date, prefix = '') => {
  if (!date) return 'N/A'

  const joinedDate = new Date(date)
  const now = new Date()

  const diffMs = now - joinedDate
  const diffMinutes = Math.floor(diffMs / (1000 * 60))
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  //  Trong hôm nay
  if (diffDays === 0) {
    if (diffMinutes < 1) return `${prefix} Vừa xong`
    if (diffMinutes < 60) return `${prefix} ${diffMinutes} phút trước`
    return `${prefix} ${diffHours} giờ trước`
  }

  // Ngày
  if (diffDays === 1) return `${prefix} 1 ngày trước`
  if (diffDays < 30) return `${prefix} ${diffDays} ngày trước`

  // Tháng
  const diffMonths = Math.floor(diffDays / 30)
  if (diffMonths === 1) return `${prefix} 1 tháng trước`
  if (diffMonths < 12) return `${prefix} ${diffMonths} tháng trước`

  // Năm
  const diffYears = Math.floor(diffMonths / 12)
  return `${prefix} ${diffYears} năm trước`
}