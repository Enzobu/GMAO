import { api } from "@/api/client"

export async function resetPassword(token: string, password: string) {
  const response = await api.post<{ message: string }>(
    `/reset-password/reset/${encodeURIComponent(token)}`,
    { password },
  )

  return response.data
}
