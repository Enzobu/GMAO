import { api } from "@/api/client"
import type { ConfigurationItem, ConfigurationPayload } from "@/types/configuration"

type ApiCollection<T> = T[] | { member?: T[]; "hydra:member"?: T[] }

function collectionItems<T>(data: ApiCollection<T>) {
  if (Array.isArray(data)) {
    return data
  }

  return data.member ?? data["hydra:member"] ?? []
}

export async function getMaintenanceTypes() {
  const response = await api.get<ApiCollection<ConfigurationItem>>("/maintenance_types")

  return collectionItems(response.data)
}

export async function createMaintenanceType(payload: ConfigurationPayload) {
  const response = await api.post<ConfigurationItem>("/maintenance_types", payload)

  return response.data
}

export async function updateMaintenanceType(id: number, payload: ConfigurationPayload) {
  const response = await api.patch<ConfigurationItem>(`/maintenance_types/${id}`, payload, {
    headers: { "Content-Type": "application/merge-patch+json" },
  })

  return response.data
}

export async function deleteMaintenanceType(id: number) {
  await api.delete(`/maintenance_types/${id}`)
}

export async function getPartTypes() {
  const response = await api.get<ApiCollection<ConfigurationItem>>("/part_types")

  return collectionItems(response.data)
}

export async function createPartType(payload: ConfigurationPayload) {
  const response = await api.post<ConfigurationItem>("/part_types", payload)

  return response.data
}

export async function updatePartType(id: number, payload: ConfigurationPayload) {
  const response = await api.patch<ConfigurationItem>(`/part_types/${id}`, payload, {
    headers: { "Content-Type": "application/merge-patch+json" },
  })

  return response.data
}

export async function deletePartType(id: number) {
  await api.delete(`/part_types/${id}`)
}
