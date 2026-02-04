import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const competitionLocationEndpoint = API_ENDPOINT.COMPETITION_LOCATION;

export const getAllCompetitionLocations = async (keyword, page = 1) => {
  const payload = { page };
  if (keyword && keyword.trim() !== '') {
    payload.keyword = keyword.trim();
  }
  return axiosInstance.post(`${competitionLocationEndpoint}/index`, payload)
    .then((response) => response.data);
}