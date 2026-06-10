import { api } from "@/api/client"

export async function requestPasswordReset(email: string) {
  const response = await api.post<{ message: string }>(
    "/reset-password/request",
    { email },
  )

  return response.data
}

export async function resetPassword(token: string, password: string) {
  const response = await api.post<{ message: string }>(
    `/reset-password/reset/${encodeURIComponent(token)}`,
    { password },
  )

  return response.data
}
