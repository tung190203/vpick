import axios from "axios";
import {LOCAL_STORAGE_KEY} from "@/constants/index.js";
import router from "@/router";

const axiosInstance = axios.create({
  baseURL: import.meta.env.VITE_BASE_URL,
});

// Add access token to every request
axiosInstance.interceptors.request.use(
  function (config) {
    const token = localStorage.getItem(LOCAL_STORAGE_KEY.LOGIN_TOKEN);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  function (error) {
    return Promise.reject(error);
  }
);

// Add refresh token logic
let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
  failedQueue.forEach(prom => {
    if (error) {
      prom.reject(error);
    } else {
      prom.resolve(token);
    }
  });
  failedQueue = [];
};

axiosInstance.interceptors.response.use(
  response => response,
  async error => {
    const originalRequest = error.config;

    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      const refreshToken = localStorage.getItem(LOCAL_STORAGE_KEY.REFRESH_TOKEN);
      if (!refreshToken) {
        localStorage.clear();
        router.push({ name: "login" });
        return Promise.reject(error);
      }

      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject });
        })
          .then(token => {
            originalRequest.headers.Authorization = "Bearer " + token;
            return axiosInstance(originalRequest);
          })
          .catch(err => {
            return Promise.reject(err);
          });
      }

      isRefreshing = true;

      try {
        const res = await axios.post(`${import.meta.env.VITE_BASE_URL}/auth/refresh-token`, {
          refresh_token: refreshToken,
        });

        const newAccessToken = res.data.access_token;
        localStorage.setItem(LOCAL_STORAGE_KEY.LOGIN_TOKEN, newAccessToken);

        axiosInstance.defaults.headers.common.Authorization = "Bearer " + newAccessToken;

        processQueue(null, newAccessToken);

        return axiosInstance(originalRequest);
      } catch (err) {
        processQueue(err, null);
        localStorage.clear();
        router.push({ name: "login" });
        return Promise.reject(err);
      } finally {
        isRefreshing = false;
      }
    }

    return Promise.reject(error);
  }
);

export const get = async (url) => axiosInstance.get(url);
export const post = async (url, data) => axiosInstance.post(url, data);
export const put = async (url, data) => axiosInstance.put(url, data);

export default axiosInstance;
