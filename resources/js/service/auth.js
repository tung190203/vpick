import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const authEndpoint = API_ENDPOINT.AUTH;
const userEndpoint = API_ENDPOINT.USER;

export const login = async (data) => {
  return axiosInstance.post(`${authEndpoint}/login`, data).then((response) => response.data.data);
};

export const register = async (data) => {
  return axiosInstance.post(`${authEndpoint}/register`, data).then((response) => response.data.data);
};

export const forgotPassword = async (data) => {
  return axiosInstance.post(`${authEndpoint}/forgot-password`, data).then((response) => response.data.data);
}

export const resetPassword = async (data) => {
  return axiosInstance.post(`${authEndpoint}/reset-password`, data).then((response) => response.data.data);
};

export const updateUser = async (data) => {
  return axiosInstance.post(`${userEndpoint}/update`, data).then((response) => response.data.data);
}