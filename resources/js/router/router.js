import LoginPage from '@/components/pages/LoginPage.vue'
import LoginSuccessPage from '@/components/pages/LoginSuccessPage.vue'
import RegisterPage from '@/components/pages/RegisterPage.vue'
import DashboardPage from '@/components/pages/DashboardPage.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import VerifyEmailPage from '@/components/pages/VerifyEmailPage.vue'
import VerifyPage from '@/components/pages/VerifyPage.vue'
import ProfilePage from '@/components/pages/ProfilePage.vue'
import ForgotPasswordPage from '@/components/pages/ForgotPasswordPage.vue'
import ResetPasswordPage from '@/components/pages/ResetPasswordPage.vue'
import Leaderboard from '@/components/pages/Leaderboard.vue'
import ClubPage from '@/components/pages/ClubPage.vue'
import TournamentPage from '@/components/pages/TournamentPage.vue'
import TournamentDetail from '@/components/pages/TournamentDetail.vue'
import TermsPage from '@/components/pages/TermsPage.vue'
import CreateFriendlyMatchPage from '@/components/pages/CreateFriendlyMatchPage.vue'
import ForbiddenPage from '@/components/pages/ForbiddenPage.vue'
import AdminDashboard from '@/components/pages/admin/AdminDashboard.vue'
import RefereeDashboard from '@/components/pages/referee/RefereeDashboard.vue'
import { ROLE } from '@/constants/index.js'

export const route = [
  {
    path: '/login',
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'login',
        component: LoginPage
      }
    ]
  },
  {
    path: '/register',
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'register',
        component: RegisterPage
      }
    ]
  },
  {
    path: '/',
    component: AppLayout,
    children: [
      {
        path: '',
        name: 'dashboard',
        component: DashboardPage,
        meta: {
          role: [ROLE.PLAYER]
        }
      },
      {
        path: '/profile',
        name: 'profile',
        component: ProfilePage,
        meta: {
          role: [ROLE.PLAYER, ROLE.ADMIN, ROLE.REFEREE]
        }
      },
      {
        path: '/leaderboard',
        name: 'leaderboard',
        component: Leaderboard,
        meta: {
          role: [ROLE.PLAYER]
        }
      },
      {
        path: '/club',
        name: 'club',
        component: ClubPage,
        meta: {
          role: [ROLE.PLAYER]
        }
      },
      {
        path: '/tournament',
        name: 'tournament',
        component: TournamentPage,
        meta: {
          role: [ROLE.PLAYER]
        }
      },
      {
        path: '/tournament/:id',
        name: 'tournament-detail',
        component: TournamentDetail,
        props: true,
        meta: {
          role: [ROLE.PLAYER]
        }
      },
      {
        path: '/friendly-match/create',
        name: 'create-friendly-match',
        component: CreateFriendlyMatchPage,
        meta: {
          role: [ROLE.PLAYER]
        }
      },
      {
        path: '/admin/dashboard',
        name: 'admin.dashboard',
        component: AdminDashboard,
        meta: {
          role: [ROLE.ADMIN]
        }
      },
      {
        path: '/referee/dashboard',
        name: 'referee.dashboard',
        component: RefereeDashboard,
        meta: {
          role: [ROLE.REFEREE]
        }
      },
    ]
  },
  {
    path: '/login-success',
    name: 'login-success',
    component: LoginSuccessPage
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: VerifyEmailPage
  },
  {
    path: '/verify',
    name: 'verify',
    component: VerifyPage
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: ForgotPasswordPage
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: ResetPasswordPage
  },
  {
    path: '/terms',
    name: 'terms',
    component: TermsPage,
    meta: {
      role: [ROLE.PLAYER, ROLE.ADMIN, ROLE.REFEREE]
    }
  },
  {
    path: '/forbidden',
    name: 'forbidden',
    component: ForbiddenPage,
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]