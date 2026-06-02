import { capitalizeFirstLetter } from "@/lib/text-format"
import type { Vehicle } from "@/types/vehicle"

export function vehicleDescription(vehicle: Vehicle | null) {
  if (!vehicle) {
    return "Véhicule"
  }

  return `${capitalizeFirstLetter(vehicle.brand)} ${capitalizeFirstLetter(
    vehicle.model,
  )} - ${vehicle.registration}`
}

export function canEditVehicle(
  vehicle: Vehicle | null,
  userId?: number,
  isAdmin = false,
) {
  return isAdmin || vehicle?.user.id === userId
}
