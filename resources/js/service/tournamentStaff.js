import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const tournamentStaffEndpoint = API_ENDPOINT.TOURNAMENT_STAFF;

export const addTournamentStaff = async (tournamentId, staffId) => {
  return axiosInstance.post(`${tournamentStaffEndpoint}/add/${tournamentId}`, {
    staff_id: staffId,
  }).then((response) => response.data.data)
}