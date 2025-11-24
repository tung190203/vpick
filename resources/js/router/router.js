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
import MiniTournamentDetail from '@/components/pages/MiniTournamentDetail.vue'
import TermsPage from '@/components/pages/TermsPage.vue'
import CreateMiniTournamentPage from '@/components/pages/CreateMiniTournamentPage.vue'
import CreateTournamentPage from '@/components/pages/CreateTournamentPage.vue'
import ForbiddenPage from '@/components/pages/ForbiddenPage.vue'
import AdminDashboard from '@/components/pages/admin/AdminDashboard.vue'
import RefereeDashboard from '@/components/pages/referee/RefereeDashboard.vue'
import OnboardingPage from "@/components/pages/Onboarding.vue"
import CompleteRegistrationPage from '@/components/pages/CompleteRegistrationPage.vue'
import VerifyChangePasswordPage from '@/components/pages/VerifyChangePasswordPage.vue'
import CompleteProfilePage from '@/components/pages/CompleteProfilePage.vue'
import UpdateProfilePage from '@/components/pages/UpdateProfilePage.vue'
import NotFoundPage from '@/components/pages/NotFoundPage.vue'
import TournamentBracketPage from '@/components/pages/TournamentBracketPage.vue'
import { ROLE } from '@/constants/index.js'

export const route = [
  {
    path: '/onboarding',
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'onboarding',
        component: OnboardingPage
      }
    ]
  },  
  {
    path: '/complete-registration',
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'complete-registration',
        component: CompleteRegistrationPage
      }
    ]
  },  
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
        path: '/profile/:id',
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
        meta: {
          role: [ROLE.PLAYER]
        },
        children: [
          {
            path: 'create',
            name: 'create-tournament',
            component: CreateTournamentPage
          },
          {
            path: ':id/edit',
            name: 'edit-tournament',
            component: CreateTournamentPage
          },
          {
            path: '',
            name: 'tournament',
            component: TournamentPage
          },
          {
            path: ':id',
            name: 'tournament-detail',
            component: TournamentDetail,
            props: true
          },
          {
            path: ':id/branket',
            name: 'tournament-branket',
            component: TournamentBracketPage,
          }
        ]
      },      
      {
        path: '/mini-tournament',
        meta: {
          role: [ROLE.PLAYER]
        },
        children: [
          {
            path: 'create',
            name: 'create-mini-tournament',
            component: CreateMiniTournamentPage
          },
          {
            path: ':id/edit',
            name: 'edit-mini-tournament',
            component: CreateMiniTournamentPage
          },
          {
            path: ':id',
            name: 'mini-tournament-detail',
            component: MiniTournamentDetail,
            props: true
          }
        ]
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
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'verify',
        component: VerifyPage
      }
    ]
  },
  {
    path: '/forgot-password',
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'forgot-password',
        component: ForgotPasswordPage
      }
    ]
  },  
  {
    path: '/verify-change-password',
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'verify-change-password',
        component: VerifyChangePasswordPage
      }
    ]
  },  
  {
    path: '/reset-password',
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'reset-password',
        component: ResetPasswordPage
      }
    ]
  },
  {
    path: '/complete-profile',
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'complete-profile',
        component: CompleteProfilePage
      }
    ]
  },
  {
    path: '/update-profile',
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'update-profile',
        component: UpdateProfilePage
      }
    ]
  },
  {
    path: '/terms',
    name: 'terms',
    component: TermsPage
  },
  {
    path: '/not-found',
    name: 'not-found',
    component: NotFoundPage
  },
  {
    path: '/forbidden',
    name: 'forbidden',
    component: ForbiddenPage,
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/not-found'
  }
]