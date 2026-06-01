import { api } from "@/api/client"
import type { Vehicle, VehiclePayload, VehicleUser } from "@/types/vehicle"

type ApiCollection<T> = T[] | { member?: T[]; "hydra:member"?: T[] }

function collectionItems<T>(data: ApiCollection<T>) {
  if (Array.isArray(data)) {
    return data
  }

  return data.member ?? data["hydra:member"] ?? []
}

export async function getVehicles() {
  const response = await api.get<ApiCollection<Vehicle>>("/vehicles")

  return collectionItems(response.data)
}

export async function getVehicle(id: string | number) {
  const response = await api.get<Vehicle>(`/vehicles/${id}`)

  return response.data
}

export async function createVehicle(payload: VehiclePayload) {
  const response = await api.post<Vehicle>("/vehicles", payload)

  return response.data
}

export async function updateVehicle(
  id: string | number,
  payload: VehiclePayload,
) {
  const response = await api.patch<Vehicle>(`/vehicles/${id}`, payload, {
    headers: { "Content-Type": "application/merge-patch+json" },
  })

  return response.data
}

export async function deleteVehicle(id: string | number) {
  await api.delete(`/vehicles/${id}`)
}

export async function getUsers() {
  const response = await api.get<ApiCollection<VehicleUser>>("/users")

  return collectionItems(response.data)
}
