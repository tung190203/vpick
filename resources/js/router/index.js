import { createRouter, createWebHistory } from 'vue-router'
import { route } from './router'
import { LOCAL_STORAGE_KEY, LOCAL_STORAGE_USER, ROLE } from "@/constants/index.js"

const router = createRouter({
  history: createWebHistory(),
  routes: route,
  scrollBehavior(to, from, savedPosition) {
    return { top: 0 }
  }
});

router.beforeEach((to, from, next) => {
  const loginToken = localStorage.getItem(LOCAL_STORAGE_KEY.LOGIN_TOKEN);
  const savedUser = localStorage.getItem(LOCAL_STORAGE_USER.USER);
  const user = savedUser ? JSON.parse(savedUser) : null;
  const userRole = user?.role;
  const hasSeenOnboarding = localStorage.getItem(LOCAL_STORAGE_KEY.ONBOARDING) === "true";

  const publicPages = [
    "login", "register", "verify-email", "verify",
    "forgot-password", "reset-password", "login-success", "terms", "onboarding", 'complete-registration', 'verify-change-password', 'reset-password'
  ];

  if (!loginToken) {
    if (!hasSeenOnboarding && to.name !== "onboarding") {
      return next({ name: "onboarding" }); // show onboarding trước
    }

    if (!publicPages.includes(to.name)) {
      return next({ name: "login", query: { redirect: to.fullPath } });
    }
  }

  if (loginToken && publicPages.includes(to.name)) {
    switch (userRole) {
      case ROLE.ADMIN:
        return next({ name: "admin.dashboard" });
      case ROLE.REFEREE:
        return next({ name: "referee.dashboard" });
      case ROLE.PLAYER:
        return next({ name: "dashboard" });
      default:
        return next({ name: "dashboard" });
    }
  }

  if (loginToken && to.meta?.role) {
    const allowedRoles = Array.isArray(to.meta.role)
      ? to.meta.role
      : [to.meta.role];
    if (!allowedRoles.includes(userRole)) {
      return next({ name: "forbidden" });
    }
  }

  next();
});

export default router;