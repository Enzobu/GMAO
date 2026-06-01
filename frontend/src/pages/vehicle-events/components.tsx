import type { ReactNode } from "react"

import { Badge } from "@/components/ui/badge"
import { capitalizeFirstLetter } from "@/lib/text-format"
import type { Vehicle } from "@/types/vehicle"

const READ_ONLY_BADGE_CLASS = [
  "border-amber-500/30 bg-amber-500/10 text-amber-700",
  "dark:text-amber-300",
].join(" ")

const WARNING_MESSAGE_CLASS = [
  "rounded-lg border border-amber-500/30 bg-amber-500/10 p-4",
  "text-sm text-amber-700 dark:text-amber-300",
].join(" ")

export { PageHeader as VehicleEventHeader } from "@/components/page-primitives"
export { MileageWarningDialog } from "@/components/mileage-warning-dialog"
export { FormActions } from "@/components/form-actions"

export function ReadOnlyBadge() {
  return (
    <Badge
      variant="outline"
      className={READ_ONLY_BADGE_CLASS}
    >
      Lecture seule
    </Badge>
  )
}

export function WarningMessage({
  children,
}: Readonly<{ children: ReactNode }>) {
  return (
    <div className={WARNING_MESSAGE_CLASS}>
      {children}
    </div>
  )
}

export {
  DetailItem,
  EmptyCard,
  ErrorMessage,
  Field,
} from "@/components/page-primitives"

export function vehicleDescription(vehicle: Vehicle | null) {
  if (!vehicle) {
    return "Véhicule"
  }

  return `${capitalizeFirstLetter(vehicle.brand)} ${capitalizeFirstLetter(
    vehicle.model
  )} - ${vehicle.registration}`
}

export function canEditVehicle(
  vehicle: Vehicle | null,
  userId?: number,
  isAdmin = false
) {
  return isAdmin || vehicle?.user.id === userId
}
