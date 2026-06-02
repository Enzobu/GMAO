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

export async function getVehicleHistoryArchive(
  id: string | number,
  fallbackFilename: string,
) {
  const response = await api.get<Blob>(`/vehicles/${id}/history/archive`, {
    responseType: "blob",
  })
  const filename = archiveFilename(
    response.headers["content-disposition"],
    fallbackFilename,
  )

  return { blob: response.data, filename }
}

export async function getUsers() {
  const response = await api.get<ApiCollection<VehicleUser>>("/users")

  return collectionItems(response.data)
}

function archiveFilename(
  contentDisposition: unknown,
  fallbackFilename: string,
) {
  if (typeof contentDisposition !== "string") {
    return fallbackFilename
  }

  const filename = /filename="?([^";]+)"?/i.exec(contentDisposition)?.[1]

  if (!filename) {
    return fallbackFilename
  }

  try {
    return decodeURIComponent(filename)
  } catch {
    return filename
  }
}
