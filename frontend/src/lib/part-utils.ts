import type { Part } from "@/types/part"
import type { Vehicle } from "@/types/vehicle"

export function partName(part: Part) {
  return part.partType?.name ?? `Pièce #${part.id}`
}

export function vehicleDisplayName(vehicle: Vehicle) {
  return vehicle.name || `${vehicle.brand} ${vehicle.model}`.trim() || `Véhicule #${vehicle.id}`
}

export function stockStatus(quantity: number) {
  if (quantity === 0) {
    return { value: "out", label: "Rupture", variant: "destructive" as const }
  }

  if (quantity === 1) {
    return { value: "low", label: "Stock faible", variant: "outline" as const }
  }

  return { value: "ok", label: "OK", variant: "secondary" as const }
}

export function formatDateTime(value?: string | null) {
  if (!value) {
    return "—"
  }

  return new Intl.DateTimeFormat("fr-FR", {
    dateStyle: "short",
    timeStyle: "short",
  }).format(new Date(value))
}
