import LoginPage from '@/components/pages/auth/login/LoginPage.vue'
import LoginSuccessPage from '@/components/pages/auth/login/LoginSuccessPage.vue'
import RegisterPage from '@/components/pages/auth/register/RegisterPage.vue'
import DashboardPage from '@/components/pages/dashboard/DashboardPage.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import VerifyEmailPage from '@/components/pages/auth/verify/VerifyEmailPage.vue'
import VerifyPage from '@/components/pages/auth/verify/VerifyPage.vue'
import ProfilePage from '@/components/pages/profile/ProfilePage.vue'
import ForgotPasswordPage from '@/components/pages/auth/password/ForgotPasswordPage.vue'
import ResetPasswordPage from '@/components/pages/auth/password/ResetPasswordPage.vue'
import Leaderboard from '@/components/pages/leader-board/Leaderboard.vue'
import ClubPage from '@/components/pages/club/ClubPage.vue'
import TournamentPage from '@/components/pages/tournament/TournamentPage.vue'
import TournamentDetail from '@/components/pages/tournament/TournamentDetail.vue'
import MiniTournamentDetail from '@/components/pages/mini-tournament/MiniTournamentDetail.vue'
import PrivacyPolicyPage from '@/components/pages/legal/PrivacyPolicyPage.vue'
import CreateMiniTournamentPage from '@/components/pages/mini-tournament/CreateMiniTournamentPage.vue'
import CreateTournamentPage from '@/components/pages/tournament/CreateTournamentPage.vue'
import ForbiddenPage from '@/components/pages/common/error/ForbiddenPage.vue'
import AdminDashboard from '@/components/pages/admin/AdminDashboard.vue'
import RefereeDashboard from '@/components/pages/referee/RefereeDashboard.vue'
import OnboardingPage from "@/components/pages/onboarding/Onboarding.vue"
import CompleteRegistrationPage from '@/components/pages/auth/register/CompleteRegistrationPage.vue'
import VerifyChangePasswordPage from '@/components/pages/auth/verify/VerifyChangePasswordPage.vue'
import CompleteProfilePage from '@/components/pages/profile/CompleteProfilePage.vue'
import UpdateProfilePage from '@/components/pages/profile/UpdateProfilePage.vue'
import NotFoundPage from '@/components/pages/common/error/NotFoundPage.vue'
import TournamentBracketPage from '@/components/pages/tournament/TournamentBracketPage.vue'
import GroupSortView from '@/components/pages/tournament/GroupSortView.vue'
import MatchVerifyPage from '@/components/pages/mini-tournament/MatchVerifyPage.vue'
import NotificationsPage from '@/components/pages/notifications/NotificationsPage.vue'
import SettingsPage from '@/components/pages/profile/SettingsPage.vue'
import MapPage from '@/components/pages/map/MapPage.vue'
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
        path: '/match/:id/verify',
        name: 'match-verify',
        component: MatchVerifyPage,
        meta: {
          role: [ROLE.REFEREE, ROLE.ADMIN, ROLE.PLAYER]
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
        path: '/notifications',
        name: 'notifications',
        component: NotificationsPage,
        meta: {
          role: [ROLE.REFEREE, ROLE.ADMIN, ROLE.PLAYER]
        }
      },
      {
        path: '/settings',
        name: 'settings',
        component: SettingsPage,
        meta: {
          role: [ROLE.PLAYER, ROLE.ADMIN, ROLE.REFEREE]
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
            path: ':id/bracket',
            name: 'tournament-bracket',
            component: TournamentBracketPage,
          },
          {
            path: ':id/groups/sort',
            name: 'tournament-groups-sort',
            component: GroupSortView
          }
        ]
      },
      {
        path: '/tournament-detail',
        meta: {
          role: [ROLE.PLAYER]
        },
        children: [
          {
            path: ':id',
            name: 'tournament-detail',
            component: TournamentDetail,
            props: true
          },
          {
            path: ':id/bracket',
            name: 'tournament-bracket',
            component: TournamentBracketPage,
          },
          {
            path: ':id/groups/sort',
            name: 'tournament-groups-sort',
            component: GroupSortView
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
        path: '/mini-tournament-detail',
        meta: {
          role: [ROLE.PLAYER]
        },
        children: [
          {
            path: ':id',
            name: 'mini-tournament-detail',
            component: MiniTournamentDetail,
            props: true
          }
        ]
      },
      {
        path: '/map',
        name: 'map',
        component: MapPage,
        meta: {
          role: [ROLE.PLAYER, ROLE.ADMIN, ROLE.REFEREE]
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
    path: '/privacy-policy',
    name: 'privacy-policy',
    component: PrivacyPolicyPage
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
