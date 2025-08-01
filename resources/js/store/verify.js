import {defineStore} from "pinia";
import * as VerifyService from "@/service/verify.js";
import { LOCAL_STORAGE_KEY } from "@/constants/index.js";
import { computed, reactive, watch } from "vue";

export const useVerifyStore = defineStore("verify", () => {
  const verify = reactive({
    id: null,
    user_id: null,
    status: "",
    vndupr_score: 0,
    verified_id: null,
    approver_id: null,
    certified_file: "",
    created_at: "",
    updated_at: "",
  });

  const getVerify = computed(() => verify);

  const loadVerifyFromLocalStorage = () => {
    const storedVerify = localStorage.getItem(LOCAL_STORAGE_KEY.VERIFY);
    if (storedVerify) {
      Object.assign(verify, JSON.parse(storedVerify));
    }
  }

  watch(verify, (newVerify) => {
    localStorage.setItem(LOCAL_STORAGE_KEY.VERIFY, JSON.stringify(newVerify));
  }, { deep: true });

  loadVerifyFromLocalStorage();

  const fillVerifyData = (verifyData) => {
    Object.assign(verify, verifyData);
    localStorage.setItem(LOCAL_STORAGE_KEY.VERIFY, JSON.stringify(verify));
  };

  const createVerification = async (data) => {
    const res = await VerifyService.createVerification(data);
    if (res) {
      fillVerifyData(res);
    }
    return res;
  };

  const showVerification = async () => {
    try {
      const res = await VerifyService.showVerification();
      if (res) {
        fillVerifyData(res);
      }
      return res;
    } catch (error) {
      console.error("Error showing verification:", error);
      throw error;
    }
  };

  return {
    getVerify,
    createVerification,
    showVerification
  };
});