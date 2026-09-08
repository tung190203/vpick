import axiosInstance from "@/utils/httpRequest.js";

const ENDPOINT = "/admin/push-notifications";

export const listCampaigns = async (params = {}) => {
    return axiosInstance
        .get(ENDPOINT, { params })
        .then((res) => res.data);
};

export const getCampaignDetail = async (id) => {
    return axiosInstance
        .get(`${ENDPOINT}/${id}`)
        .then((res) => res.data);
};
