import axiosInstance from '@/utils/httpRequest.js'
import { API_ENDPOINT } from '@/constants/index.js'

export const getPromotionRecipients = async (promotableType, promotableId) => {
  const { data } = await axiosInstance.get(`${API_ENDPOINT.PROMOTION}/recipients`, {
    params: { promotable_type: promotableType, promotable_id: promotableId }
  })
  return data
}

export const sendPromotion = async (promotableType, promotableId, recipientIds = null) => {
  const payload = { promotable_type: promotableType, promotable_id: promotableId }
  if (recipientIds && recipientIds.length > 0) {
    payload.recipient_ids = recipientIds
  }
  const { data } = await axiosInstance.post(`${API_ENDPOINT.PROMOTION}/send`, payload)
  return data
}
