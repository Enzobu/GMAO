import type { ReactNode } from "react"

import { Badge } from "@/components/ui/badge"
import type { Vehicle } from "@/types/vehicle"

export { PageHeader as VehicleEventHeader } from "@/components/page-primitives"
export { MileageWarningDialog } from "@/components/mileage-warning-dialog"
export { FormActions } from "@/components/form-actions"

export function ReadOnlyBadge() {
  return (
    <Badge
      variant="outline"
      className="border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300"
    >
      Lecture seule
    </Badge>
  )
}

export function WarningMessage({ children }: Readonly<{ children: ReactNode }>) {
  return (
    <div className="rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-700 dark:text-amber-300">
      {children}
    </div>
  )
}

export { DetailItem, EmptyCard, ErrorMessage, Field } from "@/components/page-primitives"

export function vehicleDescription(vehicle: Vehicle | null) {
  if (!vehicle) {
    return "Véhicule"
  }

  return `${vehicle.brand} ${vehicle.model} - ${vehicle.registration}`
}

export function canEditVehicle(vehicle: Vehicle | null, userId?: number, isAdmin = false) {
  return isAdmin || vehicle?.user.id === userId
}
