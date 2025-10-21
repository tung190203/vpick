import axiosInstance from "@/utils/httpRequest.js";

export const getHomeData = async (params = {}) => {
  return axiosInstance.get('/home', { params }).then((response) => response.data.data);
}