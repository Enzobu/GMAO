import { api } from "@/api/client"
import type { Profile, UpdateProfilePayload } from "@/types/profile"

export async function getProfile() {
  const response = await api.get<Profile>("/profile")

  return response.data
}

export async function updateProfile(payload: UpdateProfilePayload) {
  const response = await api.patch<Profile>("/profile", payload)

  return response.data
}

export async function requestProfilePasswordReset() {
  const response = await api.post<{ message: string }>(
    "/profile/password-reset-request",
  )

  return response.data
}
