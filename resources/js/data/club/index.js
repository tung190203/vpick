import GroupUserIcon from '@/assets/images/groups.svg';
import BarChart from '@/assets/images/bar_chart.svg';
import MoneyTization from '@/assets/images/monetization_on.svg';
import MessageIcon from "@/assets/images/message.svg";
import CalendarIcon from "@/assets/images/calendar.svg";
import FundIcon from "@/assets/images/fund.svg";
import NotificationsIcon from "@/assets/images/notifications.svg";
import ShieldCheckIcon from "@/assets/images/shield_check.svg";
import MoneyIcon from "@/assets/images/money.svg";
import { UserIcon } from '@heroicons/vue/24/outline'
import DateIcon from "@/assets/images/date.svg";
import FinancialIcon from "@/assets/images/money-atm.svg";
import MembersIcon from "@/assets/images/members.svg";
import IcPickleball from "@/assets/images/ic_pickleball.svg";

export const CLUB_STATS = [
  {
    key: 'members',
    icon: GroupUserIcon,
    label: 'Thành viên'
  },
  {
    key: 'level',
    icon: IcPickleball,
    label: 'Trình độ'
  },
  {
    key: 'price',
    icon: BarChart,
    label: 'Thứ hạng CLB'
  }
];

export const CLUB_MODULES = [
    {
        icon: FundIcon,
        label: 'Quỹ CLB',
        key: 'fund'
    },
    {
        icon: CalendarIcon,
        label: 'Tạo lịch',
        key: 'schedule'
    },
    {
        icon: NotificationsIcon,
        label: 'Thông báo',
        key: 'notification'
    },
    {
        icon: MessageIcon,
        label: 'Nhóm chat',
        key: 'chat'
    }
]

export const ROLE_LABELS = {
  admin: 'Chủ câu lạc bộ',
  treasurer: 'Thủ quỹ',
  secretary: 'Thư ký',
  manager: 'Quản lý',
  member: 'Thành viên'
}

export const ROLE_SPECIALIZATION = {
  admin: {
    label: ROLE_LABELS.admin,
    icon: ShieldCheckIcon,
    bg: 'bg-blue-500',
    text: 'text-white',
  },
  treasurer: {
    label: ROLE_LABELS.treasurer,
    icon: MoneyIcon,
    bg: 'bg-orange-400',
    text: 'text-white',
  },
  secretary: {
    label: ROLE_LABELS.secretary,
    icon: UserIcon,
    bg: 'bg-green-400',
    text: 'text-white',
  },
  manager: {
    label: ROLE_LABELS.manager,
    icon: UserIcon,
    bg: 'bg-purple-500',
    text: 'text-white',
  },
  member: {
    label: ROLE_LABELS.member,
    icon: UserIcon,
    bg: 'bg-gray-500',
    text: 'text-white',
  }
}

export const ROLE_COLORS = {
  'admin': 'bg-blue-500',
  'manager': 'bg-purple-500',
  'treasurer': 'bg-orange-400',
  'secretary': 'bg-green-500'
}

export const NOTIFICATION_COLOR_MAP = {
  1: { // Chung
    cardBg: 'bg-[#f5e3c6]',
    border: 'border-[#E0A243]',
    iconBg: 'bg-[#E0A243]',
    title: 'text-[#804D00]',
    content: 'text-[#804D00]',
    subText: 'text-[#995C00]',
    iconColor: 'text-[#E0A243]',
  },
  2: { // Sự kiện
    cardBg: 'bg-[#F5D5DA]',
    border: 'border-[#D72D36]',
    iconBg: 'bg-[#D72D36]',
    title: 'text-[#991B1B]',
    content: 'text-[#991B1B]',
    subText: 'text-[#B91C1C]',
    iconColor: 'text-[#D72D36]',
  },
  3: { // Tài chính
    cardBg: 'bg-[#C8F6E7]',
    border: 'border-[#10B981]',
    iconBg: 'bg-[#10B981]',
    title: 'text-[#065F46]',
    content: 'text-[#065F46]',
    subText: 'text-[#047857]',
    iconColor: 'text-[#10B981]',
  },
  4: { // Thành viên
    cardBg: 'bg-[#EFF6FF]',
    border: 'border-[#3B82F6]',
    iconBg: 'bg-[#3B82F6]',
    title: 'text-[#1E40AF]',
    content: 'text-[#1E40AF]',
    subText: 'text-[#1D4ED8]',
    iconColor: 'text-[#3B82F6]',
  },
  5: { // Khẩn cấp
    cardBg: 'bg-[#FFF7ED]',
    border: 'border-[#F97316]',
    iconBg: 'bg-[#F97316]',
    title: 'text-[#9A3412]',
    content: 'text-[#9A3412]',
    subText: 'text-[#C2410C]',
    iconColor: 'text-[#F97316]',
  }
}

export const NOTIFICATION_ICON_MAP = {
  1: NotificationsIcon,
  2: DateIcon,
  3: FinancialIcon,
  4: MembersIcon,
  5: ShieldCheckIcon
}

export const NOTIFICATION_BG_MAP = {
  1: 'bg-[#995C00]',
  2: 'bg-[#B91C1C]',
  3: 'bg-[#047857]',
  4: 'bg-[#1D4ED8]',
  5: 'bg-[#C2410C]'
}
