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

export const CLUB_STATS = [
  {
    key: 'members',
    icon: GroupUserIcon,
    label: 'Thành viên'
  },
  {
    key: 'level',
    icon: BarChart,
    label: 'Trình độ'
  },
  {
    key: 'price',
    icon: MoneyTization,
    label: 'Vãng lai/Buổi'
  }
];

export const CLUB_MODULES = [
    {
        icon: FundIcon,
        label: 'Quỹ CLB'
    },
    {
        icon: CalendarIcon,
        label: 'Tạo lịch'
    },
    {
        icon: NotificationsIcon,
        label: 'Thông báo'
    },
    {
        icon: MessageIcon,
        label: 'Nhóm chat'
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