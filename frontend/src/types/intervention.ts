import type { ConfigurationItem } from "@/types/configuration"
import type { Part } from "@/types/part"
import type { Vehicle } from "@/types/vehicle"

export type InterventionStatus =
  | "todo"
  | "in_progress"
  | "completed"
  | "cancelled"

export interface InterventionPart {
  id?: number
  part: Part | string
  quantity: number
  notes?: string | null
}

export interface Intervention {
  id: number
  vehicle: Vehicle
  maintenanceType: ConfigurationItem
  mileage?: number | null
  plannedAt?: string | null
  startedAt?: string | null
  finishedAt?: string | null
  status: InterventionStatus
  isExternal?: boolean | null
  notes?: string | null
  nextDueMileage?: number | null
  nextDueAt?: string | null
  maintenanceParts?: InterventionPart[]
  createdAt?: string | null
  updatedAt?: string | null
  isDeleted?: boolean
}

export interface InterventionPartPayload {
  id?: number
  part: string
  quantity: number
  notes?: string | null
}

export interface InterventionPayload {
  vehicle: string
  maintenanceType: string
  mileage?: number | null
  plannedAt?: string | null
  startedAt?: string | null
  finishedAt?: string | null
  status: InterventionStatus
  isExternal: boolean
  notes?: string | null
  nextDueMileage?: number | null
  nextDueAt?: string | null
  maintenanceParts?: InterventionPartPayload[]
}
