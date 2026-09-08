import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";
import axios from "axios";

const tournamentTypeEndpoint = API_ENDPOINT.TOURNAMENT_TYPE;

export const createTournamentType = async (tournamentData) => {
  return axiosInstance.post(`${tournamentTypeEndpoint}/store`, tournamentData)
    .then((response) => response.data.data);
}

export const updateTournamentType = async (tournamentTypeId, data) => {
  // Gửi PUT request - sử dụng axios.put (Laravel tự nhận PUT qua route match)
  return axiosInstance.put(`${tournamentTypeEndpoint}/${tournamentTypeId}`, data)
    .then((response) => response.data.data);
}

export const deleteTournamentType = async (tournamentTypeId) => {
  return axiosInstance.delete(`${tournamentTypeEndpoint}/${tournamentTypeId}`)
    .then((response) => response.data.data)
}

export const getBracketByTournamentTypeId = async (tournamentTypeId) => {
  return axiosInstance.get(`${tournamentTypeEndpoint}/${tournamentTypeId}/bracket`)
    .then((response) => response.data.data);
}


export const getRanks = async (tournamentId) => {
  return axiosInstance.get(`${tournamentTypeEndpoint}/${tournamentId}/rank`)
    .then((response) => response.data.data);
}

export const reGenerateMatches = async (tournamentTypeId) => {
  return axiosInstance.post(`${tournamentTypeEndpoint}/${tournamentTypeId}/regenerate-matches`)
    .then((response) => response.data.data);
}

export const groupWithTeamsForSort = async(tournamentId) => {
  return axiosInstance.get(`${tournamentTypeEndpoint}/${tournamentId}/groups-with-teams`).then((response) => response.data.data);
}

export const assignTeamsAndGenerate = async(tournamentTypeId, data) => {
  return axiosInstance.post(`${tournamentTypeEndpoint}/${tournamentTypeId}/assign-teams-and-generate`, data).then((response) => response.data.data);
}

export const autoGenerateTeamAndMatches = async(tournamentTypeId) => {
  return axiosInstance.post(`${tournamentTypeEndpoint}/${tournamentTypeId}/auto-generate-matches`).then((response) => response.data.data);
}

// ✅ Cross-group comparison ranking (xét đội Nhì/Ba khi bảng không đều)
export const getCrossGroupComparison = async (tournamentTypeId) => {
  return axiosInstance.get(`${tournamentTypeEndpoint}/${tournamentTypeId}/cross-group-comparison`).then((response) => response.data.data);
}

// API 2 dùng raw axios (không qua response interceptor) để 404 không redirect sang not-found.
// Trong UI: user chỉ click vào candidate đã biết hợp lệ; 404 chỉ xảy ra khi stale data.
export const getCrossGroupComparisonTeamMatches = async (tournamentTypeId, teamId) => {
  const token = localStorage.getItem('login-token') || localStorage.getItem('LOGIN_TOKEN') || '';
  try {
    const response = await axios.get(
      `${import.meta.env.VITE_BASE_URL}${tournamentTypeEndpoint}/${tournamentTypeId}/cross-group-comparison/${teamId}/matches`,
      {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      }
    );
    return response.data.data;
  } catch (error) {
    // Trả error có cấu trúc giống axiosInstance để FE xử lý đồng nhất
    if (error.response) {
      const e = new Error(error.response.data?.message || 'Team không phải candidate');
      e.response = error.response;
      throw e;
    }
    throw error;
  }
}
