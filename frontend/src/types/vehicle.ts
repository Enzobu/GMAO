export interface VehicleUser {
  id: number
  email: string
  firstname?: string | null
  lastname?: string | null
  roles?: string[]
}

export interface VehicleInsurance {
  id: number
  providerName: string
  policyNumber?: string | null
  startDate?: string | null
  endDate?: string | null
  paymentFrequency?: string | null
  active?: boolean
  isActive?: boolean
  isDeleted?: boolean
}

export interface VehicleInspection {
  id: number
  inspectionDate?: string | null
  validUntil?: string | null
  mileage?: number | null
  result?: string | null
  counterVisitRequired?: boolean
  counterVisitDueAt?: string | null
  notes?: string | null
  center?: { id: number; name: string } | null
  isDeleted?: boolean
}

export interface VehicleMaintenance {
  id: number
  mileage?: number | null
  performedAt?: string | null
  plannedAt?: string | null
  status?: string | null
  isExternal?: boolean | null
  notes?: string | null
  nextDueMileage?: number | null
  nextDueAt?: string | null
  maintenanceType?: { id: number; name: string } | null
  isDeleted?: boolean
}

export interface Vehicle {
  id: number
  name: string
  registration: string
  brand: string
  model: string
  type?: string | null
  year?: number | null
  vin?: string | null
  engine?: string | null
  fuelType?: string | null
  transmission?: string | null
  lastMileage?: number | null
  color?: string | null
  purchaseDate?: string | null
  purchasePrice?: string | null
  status: string
  user: VehicleUser
  vehicleInsurances?: VehicleInsurance[]
  vehicleInspections?: VehicleInspection[]
  maintenances?: VehicleMaintenance[]
  isDeleted?: boolean
}

export interface VehiclePayload {
  name: string
  registration: string
  brand: string
  model: string
  type?: string | null
  year?: number | null
  vin?: string | null
  engine?: string | null
  fuelType?: string | null
  transmission?: string | null
  lastMileage?: number | null
  color?: string | null
  purchaseDate?: string | null
  purchasePrice?: string | null
  status: string
  user?: string
}
