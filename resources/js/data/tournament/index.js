import mixedIcon from '@/assets/images/mixed.svg';
import directIcon from '@/assets/images/direct.svg';
import roundRobinIcon from '@/assets/images/round-robin.svg';

export const TABS = [
    { name: 'detail', label: 'Chi tiết' },
    { name: 'list', label: 'Danh sách' },
    { name: 'type', label: 'Thể thức' },
    { name: 'schedule', label: 'Lịch thi đấu' },
    { name: 'discuss', label: 'Thảo luận' }
]

export const SCHEDULE_TABS = [
  {id: 'ranking', label: 'Bảng xếp hạng'},
  {id: 'matches', label: 'Trận đấu'},
]

export const LIST_TABS = [
    { id: 'staffs', label: 'Ban tổ chức' },
    { id: 'paticipants', label: 'VĐV' },
    { id: 'split', label: 'Chia đội' },
    { id: 'invite', label: 'Đã mời' }
  ]

  export const BACKGROUND_COLORS = [
    'bg-red-500',
    'bg-blue-500',
    'bg-teal-500',
    'bg-green-500',
    'bg-yellow-500',
    'bg-indigo-500',
    'bg-purple-500',
    'bg-pink-500',
  ];

  export const FORMAT_DETAILS = {
    1: {
      icon: mixedIcon,
      title: 'Hỗn hợp',
      description: 'Bao gồm vòng đấu bảng để chọn đội, sau đó đấu loại trực tiếp để tìm ra đội vô địch.'
    },
    2: {
      icon: directIcon,
      title: 'Loại trực tiếp',
      description: 'Đấu loại trực tiếp theo nhánh. Đội thắng sẽ đi tiếp vào vòng trong, đội thua bị loại khỏi nhánh đấu.'
    },
    3: {
      icon: roundRobinIcon,
      title: 'Vòng tròn',
      description: 'Các đội thi đấu với nhau một hoặc nhiều lần. Đội có thành tích tốt nhất sẽ vô địch.'
    }
  };