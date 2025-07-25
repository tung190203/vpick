import { createRouter, createWebHistory } from 'vue-router'
import { route } from './router'
import {LOCAL_STORAGE_KEY} from "@/constants/index.js";

const router = createRouter({
  history: createWebHistory(),
  routes: route
});

router.beforeEach((to, from, next) => {
  const loginToken = localStorage.getItem(LOCAL_STORAGE_KEY.LOGIN_TOKEN);
  const authPages = ["login", "register",'verify-email', 'verify', 'forgot-password', 'reset-password', 'login-success'];
  if (!loginToken && !authPages.includes(to.name)) {
    next({ name: "login", query: { redirect: to.fullPath } });
  } else if (loginToken && authPages.includes(to.name)) {
    next({ name: "dashboard" });
  } else {
    next();
  }
});


export default router;