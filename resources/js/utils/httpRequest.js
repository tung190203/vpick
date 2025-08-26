import axios from "axios";
import { LOCAL_STORAGE_KEY } from "@/constants/index.js";
import router from "@/router";

const axiosInstance = axios.create({
  baseURL: import.meta.env.VITE_BASE_URL,
});

// ========== DEBUG HELPER ==========
const log = (...args) => console.log("[AXIOS DEBUG]", ...args);

// ========== REQUEST INTERCEPTOR ==========
axiosInstance.interceptors.request.use(
  function (config) {
    const token = localStorage.getItem(LOCAL_STORAGE_KEY.LOGIN_TOKEN);
    log("Request:", config.url, "| AccessToken =", token);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  function (error) {
    log("Request error:", error);
    return Promise.reject(error);
  }
);

// ========== REFRESH TOKEN LOGIC ==========
let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
  log("Processing queue. Error:", error, "Token:", token);
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
      log("401 detected. RefreshToken =", refreshToken);

      if (!refreshToken) {
        log("No refresh token → clearing storage + redirecting to login");
        localStorage.clear();
        router.push({ name: "login" });
        return Promise.reject(error);
      }

      if (isRefreshing) {
        log("Already refreshing → queueing this request");
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject });
        })
          .then(token => {
            log("Retrying queued request with new token", token);
            originalRequest.headers.Authorization = "Bearer " + token;
            return axiosInstance(originalRequest);
          })
          .catch(err => {
            log("Queued request failed", err);
            return Promise.reject(err);
          });
      }

      isRefreshing = true;
      log("Calling refresh API...");

      try {
        const res = await axios.post(
          `${import.meta.env.VITE_BASE_URL}/auth/refresh-token`,
          {},
          {
            headers: {
              Authorization: `Bearer ${refreshToken}`,
            },
          }
        );

        log("Refresh API response:", res.data);

        const newAccessToken = res.data.access_token;
        if (!newAccessToken) {
          log("❌ No access_token in refresh response!");
          throw new Error("No access_token in refresh response");
        }

        localStorage.setItem(LOCAL_STORAGE_KEY.LOGIN_TOKEN, newAccessToken);
        axiosInstance.defaults.headers.common.Authorization =
          "Bearer " + newAccessToken;

        log("✅ New access token saved:", newAccessToken);

        processQueue(null, newAccessToken);

        return axiosInstance(originalRequest);
      } catch (err) {
        log("❌ Refresh token failed:", err.response?.status, err.response?.data || err.message);
        processQueue(err, null);

        log("Clearing storage and redirecting to login");
        localStorage.clear();
        router.push({ name: "login" });
        return Promise.reject(err);
      } finally {
        isRefreshing = false;
      }
    }

    log("Response error (not 401):", error.response?.status, error.response?.data);
    return Promise.reject(error);
  }
);

// ========== EXPORT HELPERS ==========
export const get = async url => axiosInstance.get(url);
export const post = async (url, data) => axiosInstance.post(url, data);
export const put = async (url, data) => axiosInstance.put(url, data);

export default axiosInstance;
