import type { ReactNode } from "react"
import { Link } from "react-router-dom"
import { Save } from "lucide-react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { DetailItem, EmptyCard, ErrorMessage, Field, PageHeader } from "@/components/page-primitives"
import type { Vehicle } from "@/types/vehicle"

export const VehicleEventHeader = PageHeader

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

export function WarningMessage({ children }: { children: ReactNode }) {
  return (
    <div className="rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-700 dark:text-amber-300">
      {children}
    </div>
  )
}

export { DetailItem, EmptyCard, ErrorMessage, Field }

export function FormActions({ cancelTo, canEdit, isSaving }: { cancelTo: string; canEdit: boolean; isSaving: boolean }) {
  return (
    <div className="flex justify-end gap-2">
      <Button variant="outline" asChild>
        <Link to={cancelTo}>Annuler</Link>
      </Button>
      <Button type="submit" disabled={!canEdit || isSaving}>
        <Save />
        {isSaving ? "Enregistrement..." : "Enregistrer"}
      </Button>
    </div>
  )
}

export function vehicleDescription(vehicle: Vehicle | null) {
  if (!vehicle) {
    return "Véhicule"
  }

  return `${vehicle.brand} ${vehicle.model} - ${vehicle.registration}`
}

export function canEditVehicle(vehicle: Vehicle | null, userId?: number, isAdmin = false) {
  return isAdmin || vehicle?.user.id === userId
}
