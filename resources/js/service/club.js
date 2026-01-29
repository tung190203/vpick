import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

export const getAllClubs = async () => {
    return axiosInstance.get(`${API_ENDPOINT.CLUB}`).then((response) => response.data.data.clubs);
}

export const joinRequest = async (clubId) => {
    return axiosInstance.post(`${API_ENDPOINT.CLUB}/${clubId}/join-requests`).then((response) => response.data);
}

export const myClubs = async () => {
    return axiosInstance.get(`${API_ENDPOINT.CLUB}/my-clubs`).then((response) => response.data.data);
}

export const clubDetail = async (clubId) => {
    return axiosInstance.get(`${API_ENDPOINT.CLUB}/${clubId}`).then((response) => response.data.data);
}

export const getMembers = async (clubId, params = {}) => {
  const queryParams = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      queryParams.append(key, value)
    }
  })

  const queryString = queryParams.toString()
  const url = `${API_ENDPOINT.CLUB}/${clubId}/members${queryString ? `?${queryString}` : ''}`

  const { data } = await axiosInstance.get(url)
  return data.data
}