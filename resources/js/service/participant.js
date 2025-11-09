import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const participantEndpoint = API_ENDPOINT.PARTICIPANT;

export const inviteFriends = async (tournamentId) => {
  return axiosInstance.post(`${participantEndpoint}/invite-friend/${tournamentId}`)
    .then((response) => response.data.data);
}

export const sendInvitation = async (tournamentId, userIds) => {
  return axiosInstance.post(`${participantEndpoint}/invite-user/${tournamentId}`, {
    user_ids: userIds,
  }).then((response) => response.data.data)
};