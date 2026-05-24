import type { ConfigurationItem } from "@/types/configuration"
import type { Vehicle } from "@/types/vehicle"

export interface Part {
  id: number
  partType: ConfigurationItem
  quantity: number
  vehicles: Vehicle[]
  note?: string | null
  createdAt?: string | null
  updatedAt?: string | null
  isDeleted?: boolean
}

export interface PartPayload {
  partType: string
  quantity: number
  vehicles?: string[]
  note?: string | null
}
