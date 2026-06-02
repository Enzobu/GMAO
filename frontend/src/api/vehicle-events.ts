import { api } from "@/api/client"
import type {
  InspectionCenter,
  VehicleInspectionEvent,
  VehicleInspectionPayload,
  VehicleInsuranceEvent,
  VehicleInsurancePayload,
} from "@/types/vehicle-events"

type ApiCollection<T> = T[] | { member?: T[]; "hydra:member"?: T[] }

function collectionItems<T>(data: ApiCollection<T>) {
  if (Array.isArray(data)) return data
  return data.member ?? data["hydra:member"] ?? []
}

export async function getVehicleInsurances() {
  const response = await api.get<ApiCollection<VehicleInsuranceEvent>>(
    "/vehicle_insurances",
  )
  return collectionItems(response.data)
}

export async function getVehicleInsurance(id: string | number) {
  const response = await api.get<VehicleInsuranceEvent>(
    `/vehicle_insurances/${id}`,
  )
  return response.data
}

export async function createVehicleInsurance(payload: VehicleInsurancePayload) {
  const response = await api.post<VehicleInsuranceEvent>(
    "/vehicle_insurances",
    payload,
  )
  return response.data
}

export async function updateVehicleInsurance(
  id: string | number,
  payload: Partial<VehicleInsurancePayload>,
) {
  const response = await api.patch<VehicleInsuranceEvent>(
    `/vehicle_insurances/${id}`,
    payload,
    { headers: { "Content-Type": "application/merge-patch+json" } },
  )
  return response.data
}

export async function closeVehicleInsurance(
  id: string | number,
  endDate: string,
) {
  const response = await api.patch<VehicleInsuranceEvent>(
    `/vehicle_insurances/${id}`,
    { endDate },
    { headers: { "Content-Type": "application/merge-patch+json" } }
  )
  return response.data
}

export async function deleteVehicleInsurance(id: string | number) {
  await api.delete(`/vehicle_insurances/${id}`)
}

export async function getVehicleInspections() {
  const response = await api.get<ApiCollection<VehicleInspectionEvent>>(
    "/vehicle_inspections",
  )
  return collectionItems(response.data)
}

export async function getVehicleInspection(id: string | number) {
  const response = await api.get<VehicleInspectionEvent>(
    `/vehicle_inspections/${id}`,
  )
  return response.data
}

export async function createVehicleInspection(
  payload: VehicleInspectionPayload,
  forceMileage = false,
) {
  const response = await api.post<VehicleInspectionEvent>(
    "/vehicle_inspections",
    payload,
    { params: forceMileage ? { forceMileage: true } : undefined },
  )
  return response.data
}

export async function updateVehicleInspection(
  id: string | number,
  payload: VehicleInspectionPayload,
  forceMileage = false,
) {
  const response = await api.patch<VehicleInspectionEvent>(
    `/vehicle_inspections/${id}`,
    payload,
    {
      headers: { "Content-Type": "application/merge-patch+json" },
      params: forceMileage ? { forceMileage: true } : undefined,
    },
  )
  return response.data
}

export async function deleteVehicleInspection(id: string | number) {
  await api.delete(`/vehicle_inspections/${id}`)
}

export async function getInspectionCenters() {
  const response = await api.get<ApiCollection<InspectionCenter>>(
    "/inspection_centers",
  )
  return collectionItems(response.data)
}
