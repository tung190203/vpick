import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const miniTournamentEndpoint = API_ENDPOINT.MINI_TOURNAMENT;

export const storeMiniTournament = async (data) => {
  return axiosInstance.post(`${miniTournamentEndpoint}/store`, data)
    .then((response) => response.data.data);
}

export const getMiniTournamentById = async (id) => {
  return axiosInstance.get(`${miniTournamentEndpoint}/${id}`)
    .then((response) => response.data.data);
}

export const updateMiniTournament = async (id, data) => {
  return axiosInstance.post(`${miniTournamentEndpoint}/update/${id}`, data)
    .then((response) => response.data.data);
}

export const deleteMiniTournament = async (id) => {
    return axiosInstance.post(`${miniTournamentEndpoint}/delete/` + id)
        .then((response) => response.data.data);
}
