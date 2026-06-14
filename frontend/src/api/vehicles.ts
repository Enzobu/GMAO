import { api } from "@/api/client"
import {
  collectionItems,
  collectionNextPage,
  collectionPage,
  collectionParams,
  COLLECTION_REQUEST_HEADERS,
  type ApiCollection,
  type CollectionParams,
} from "@/api/api-collection"
import type {
  Vehicle,
  VehicleInspection,
  VehicleInsurance,
  VehicleMaintenance,
  VehiclePayload,
  VehicleUser,
} from "@/types/vehicle"

export async function getVehicles() {
  const response = await api.get<ApiCollection<Vehicle>>("/vehicles", {
    headers: COLLECTION_REQUEST_HEADERS,
    params: { itemsPerPage: 36 },
  })
  const items = [...collectionItems(response.data)]
  let nextPage = collectionNextPage(response.data)

  while (nextPage !== null) {
    const nextResponse = await api.get<ApiCollection<Vehicle>>("/vehicles", {
      headers: COLLECTION_REQUEST_HEADERS,
      params: { page: nextPage, itemsPerPage: 36 },
    })

    items.push(...collectionItems(nextResponse.data))
    nextPage = collectionNextPage(nextResponse.data)
  }

  return items
}

export async function getVehiclesPage(params: CollectionParams) {
  const response = await api.get<ApiCollection<Vehicle>>("/vehicles", {
    headers: COLLECTION_REQUEST_HEADERS,
    params: collectionParams(params),
  })

  return collectionPage(response.data, params.page, params.itemsPerPage)
}

export async function getVehicle(id: string | number) {
  const response = await api.get<VehicleResponse>(`/vehicles/${id}`)

  return normalizeVehicle(response.data)
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
  const response = await api.get<ApiCollection<VehicleUser>>("/users", {
    headers: COLLECTION_REQUEST_HEADERS,
    params: { itemsPerPage: 36 },
  })
  const items = [...collectionItems(response.data)]
  let nextPage = collectionNextPage(response.data)

  while (nextPage !== null) {
    const nextResponse = await api.get<ApiCollection<VehicleUser>>("/users", {
      headers: COLLECTION_REQUEST_HEADERS,
      params: { page: nextPage, itemsPerPage: 36 },
    })

    items.push(...collectionItems(nextResponse.data))
    nextPage = collectionNextPage(nextResponse.data)
  }

  return items
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

type VehicleResponse = Omit<
  Vehicle,
  "maintenances" | "vehicleInspections" | "vehicleInsurances"
> & {
  maintenances?: ApiCollection<VehicleMaintenance>
  vehicleInspections?: ApiCollection<VehicleInspection>
  vehicleInsurances?: ApiCollection<VehicleInsurance>
}

function normalizeVehicle(vehicle: VehicleResponse): Vehicle {
  return {
    ...vehicle,
    maintenances: collectionItems(vehicle.maintenances),
    vehicleInspections: collectionItems(vehicle.vehicleInspections),
    vehicleInsurances: collectionItems(vehicle.vehicleInsurances),
  }
}
