import { create } from "zustand"
import type { User } from "@/types/auth"

interface AuthState {
  token: string | null
  user: User | null
  isAuthenticated: boolean

  setToken: (token: string | null) => void
  setUser: (user: User | null) => void
  logout: () => void
}

export const useAuthStore = create<AuthState>((set) => ({
  token: localStorage.getItem("token"),
  user: null,
  isAuthenticated: !!localStorage.getItem("token"),

  setToken: (token) => {
    if (token) {
      localStorage.setItem("token", token)
    } else {
      localStorage.removeItem("token")
    }

    set({
      token,
      isAuthenticated: !!token,
    })
  },

  setUser: (user) => set({ user }),

  logout: () => {
    localStorage.removeItem("token")

    set({
      token: null,
      user: null,
      isAuthenticated: false,
    })
  },
}))
