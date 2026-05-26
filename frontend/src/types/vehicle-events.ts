import type { Vehicle } from "@/types/vehicle"

export interface InspectionCenter {
  id: number
  name: string
}

export interface VehicleInsuranceEvent {
  id: number
  vehicle: Vehicle
  providerName: string
  policyNumber?: string | null
  startDate?: string | null
  endDate?: string | null
  paymentFrequency: "monthly" | "yearly"
  active?: boolean
  isActive?: boolean
  createdAt?: string | null
  updatedAt?: string | null
  isDeleted?: boolean
}

export interface VehicleInsurancePayload {
  vehicle: string
  providerName: string
  policyNumber?: string | null
  startDate?: string | null
  endDate?: string | null
  paymentFrequency: "monthly" | "yearly"
}

export interface VehicleInspectionEvent {
  id: number
  vehicle: Vehicle
  inspectionDate: string
  validUntil?: string | null
  mileage?: number | null
  result: "pass" | "counter_visit" | "fail"
  counterVisitRequired: boolean
  counterVisitDueAt?: string | null
  notes?: string | null
  center?: InspectionCenter | null
  createdAt?: string | null
  updatedAt?: string | null
  isDeleted?: boolean
}

export interface VehicleInspectionPayload {
  vehicle: string
  inspectionDate: string
  validUntil?: string | null
  mileage?: number | null
  result: "pass" | "counter_visit" | "fail"
  counterVisitRequired: boolean
  counterVisitDueAt?: string | null
  notes?: string | null
  center?: string | null
}
