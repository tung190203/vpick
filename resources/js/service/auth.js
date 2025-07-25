import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const endpoint = API_ENDPOINT.AUTH;

export const login = async (data) => {
  return axiosInstance.post(`${endpoint}/login`, data).then((response) => response.data);
};

export const register = async (data) => {
  return axiosInstance.post(`${endpoint}/register`, data).then((response) => response.data);
};

export const forgotPassword = async (data) => {
  return axiosInstance.post(`${endpoint}/forgot-password`, data).then((response) => response.data);
}

export const resetPassword = async (data) => {
  return axiosInstance.post(`${endpoint}/reset-password`, data).then((response) => response.data);
};