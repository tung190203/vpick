import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

export const getAllClubs = async () => {
    return axiosInstance.get(`${API_ENDPOINT.CLUB}`).then((response) => response.data.data.clubs);
}

export const joinRequest = async (clubId) => {
    return axiosInstance.post(`${API_ENDPOINT.CLUB}/${clubId}/join-requests`).then((response) => response.data);
}

export const joiningRequests = async (clubId) => {
    return axiosInstance.get(`${API_ENDPOINT.CLUB}/${clubId}/join-requests`).then((response) => response.data);
}

export const approveJoinRequest = async (clubId, requestId) => {
    return axiosInstance.post(`${API_ENDPOINT.CLUB}/${clubId}/join-requests/${requestId}/approve`).then((response) => response.data);
}

export const rejectJoinRequest = async (clubId, requestId) => {
    return axiosInstance.post(`${API_ENDPOINT.CLUB}/${clubId}/join-requests/${requestId}/reject`).then((response) => response.data);
}

export const myClubs = async () => {
    return axiosInstance.get(`${API_ENDPOINT.CLUB}/my-clubs`).then((response) => response.data.data);
}

export const clubDetail = async (clubId) => {
    return axiosInstance.get(`${API_ENDPOINT.CLUB}/${clubId}`).then((response) => response.data.data);
}

export const updateClub = async (clubId, data) => {
    return axiosInstance.put(`${API_ENDPOINT.CLUB}/${clubId}`, data).then((response) => response.data);
}

export const createClub = async (data) => {
    return axiosInstance.post(`${API_ENDPOINT.CLUB}`, data).then((response) => response.data);
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
  return data
}

export const removeMember = async (clubId, memberId) => {
    return axiosInstance.delete(`${API_ENDPOINT.CLUB}/${clubId}/members/${memberId}`).then((response) => response.data);
}

export const updateMemberRole = async (clubId, memberId, data) => {
    return axiosInstance.put(`${API_ENDPOINT.CLUB}/${clubId}/members/${memberId}`, data).then((response) => response.data);
}

export const cancelJoinRequest = async (clubId) => {
    return axiosInstance.delete(`${API_ENDPOINT.CLUB}/${clubId}/join-requests`).then((response) => response.data);
}

export const leaveClub = async (clubId) => {
    return axiosInstance.post(`${API_ENDPOINT.CLUB}/${clubId}/leave`).then((response) => response.data);
}

export const clubNotification = async (clubId, params = {}) => {
    const queryParams = new URLSearchParams()
    Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      queryParams.append(key, value)
    }
  })

  const queryString = queryParams.toString()
  const url = `${API_ENDPOINT.CLUB}/${clubId}/notifications${queryString ? `?${queryString}` : ''}`
  const { data } = await axiosInstance.get(url)
  return data
}

export const getClubActivities = async (clubId, params = {}) => {
    const queryParams = new URLSearchParams()
    Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      queryParams.append(key, value)
    }
  })

  const queryString = queryParams.toString()
  const url = `${API_ENDPOINT.CLUB}/${clubId}/activities${queryString ? `?${queryString}` : ''}`
  const { data } = await axiosInstance.get(url)
  return data
}

export const getClubActivityDetail = async(clubId, activityId) => {
  return axiosInstance.get(`${API_ENDPOINT.CLUB}/${clubId}/activities/${activityId}`).then((response) => response.data);
}

export const getClubLeaderBoard = async (clubId, params = {}) => {
    const queryParams = new URLSearchParams()
    Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      queryParams.append(key, value)
    }
  })

  const queryString = queryParams.toString()
  const url = `${API_ENDPOINT.CLUB}/${clubId}/leaderboard${queryString ? `?${queryString}` : ''}`
  const { data } = await axiosInstance.get(url)
  return data
}

export const getFund = async (clubId) => {
    return axiosInstance.get(`${API_ENDPOINT.CLUB}/${clubId}/fund`).then((response) => response.data);
}

export const searchLocation = async (params = {}) => {
    const queryParams = new URLSearchParams()
    Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      queryParams.append(key, value)
    }
  })

  const queryString = queryParams.toString()
  const url = `${API_ENDPOINT.CLUB}/search-location${queryString ? `?${queryString}` : ''}`
  const { data } = await axiosInstance.get(url)
  return data
}

export const locationDetail = async (params = {}) => {
    const queryParams = new URLSearchParams()
    Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      queryParams.append(key, value)
    }
  })

  const queryString = queryParams.toString()
  const url = `${API_ENDPOINT.CLUB}/location-detail${queryString ? `?${queryString}` : ''}`
  const { data } = await axiosInstance.get(url)
  return data
}
