import GroupUserIcon from '@/assets/images/groups.svg';
import BarChart from '@/assets/images/bar_chart.svg';
import MoneyTization from '@/assets/images/monetization_on.svg';
import MessageIcon from "@/assets/images/message.svg";
import CalendarIcon from "@/assets/images/calendar.svg";
import FundIcon from "@/assets/images/fund.svg";
import NotificationsIcon from "@/assets/images/notifications.svg";

export const CLUB_STATS = [
    {
        icon: GroupUserIcon,
        value: '42',
        label: 'Thành viên'
    },
    {
        icon: BarChart,
        value: '2.5 - 3.5',
        label: 'Trình độ'
    },
    {
        icon: MoneyTization,
        value: '50K',
        label: 'Vãng lai/Buổi'
    }
]

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