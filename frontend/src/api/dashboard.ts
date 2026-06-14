import { api } from "@/api/client"
import type { DashboardData } from "@/types/dashboard"

export async function getDashboard(year: number) {
  const response = await api.get<DashboardData>("/dashboard", {
    params: { year },
  })

  return response.data
}
