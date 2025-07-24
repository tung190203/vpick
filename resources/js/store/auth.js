import {defineStore} from "pinia";
import {LOCAL_STORAGE_KEY, LOCAL_STORAGE_USER} from "@/constants/index.js";
import * as AuthService from "@/service/auth.js";
import {computed, reactive, watch} from "vue";

export const useUserStore = defineStore("user", () => {
  const user = reactive({
    id: null,
    full_name: "",
    email: "",
    role: "",
  })

  const getUser = computed(() => user)

  const loadUserFromLocalStorage = () => {
    const storedUser = localStorage.getItem(LOCAL_STORAGE_USER.USER);
    if (storedUser) {
      Object.assign(user, JSON.parse(storedUser));
    }
  };

  watch(user, (newUser) => {
    localStorage.setItem(LOCAL_STORAGE_USER.USER, JSON.stringify(newUser));
  }, {deep: true});

  loadUserFromLocalStorage();

  const registerUser = async (data) => {
    await AuthService.register(data);
  }
  const loginUser = async (data) => {
    const response = await AuthService.login(data);
    console.log(response.token); 
    fillUserData(response.user);
    localStorage.setItem(LOCAL_STORAGE_KEY.LOGIN_TOKEN, response.token.access_token);
  }

  const fillUserData = (userData) => {
    Object.assign(user, userData);
  };

  const clearUserData = () => {
    Object.keys(user).forEach(key => user[key] = "");
    user.id = null;
  };

  const logoutUser = async () => {
    await AuthService.logout()
    clearUserData()
    localStorage.removeItem(LOCAL_STORAGE_KEY.LOGIN_TOKEN)
  }

  return {
    getUser,
    registerUser,
    loginUser,
    logoutUser,
  }  
});