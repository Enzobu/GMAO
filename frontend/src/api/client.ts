import axios from "axios"
import { useAuthStore } from "@/stores/auth-store"

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? "http://localhost:8000/api",
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
})

api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const requestUrl = error.config?.url ?? ""
    const isAuthRequest = requestUrl.includes("/login") || requestUrl.includes("/reset-password")
    const isAlreadyOnLogin = window.location.pathname === "/login"

    if (error.response?.status === 401 && !isAuthRequest) {
      useAuthStore.getState().logout()

      if (!isAlreadyOnLogin) {
        window.location.href = "/login"
      }
    }

    return Promise.reject(error)
  },
)
