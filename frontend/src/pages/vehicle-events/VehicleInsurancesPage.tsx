import { useEffect, useMemo, useState } from "react"
import { Link, useParams } from "react-router-dom"
import { Plus, Trash2 } from "lucide-react"

import { deleteVehicleInsurance, getVehicleInsurances } from "@/api/vehicle-events"
import { getVehicle } from "@/api/vehicles"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { useAuthStore } from "@/stores/auth-store"
import type { Vehicle } from "@/types/vehicle"
import type { VehicleInsuranceEvent } from "@/types/vehicle-events"
import { formatDate, formatDateTime, isInsuranceActive, optionLabel, PAYMENT_FREQUENCIES } from "@/lib/vehicle-events"
import { DetailItem, EmptyCard, ErrorMessage, ReadOnlyBadge, VehicleEventHeader } from "./components"

export default function VehicleInsurancesPage() {
  const { vehicleId } = useParams()
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false

  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [insurances, setInsurances] = useState<VehicleInsuranceEvent[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [insuranceToDelete, setInsuranceToDelete] = useState<VehicleInsuranceEvent | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  useEffect(() => {
    let ignore = false

    async function loadInsurances() {
      if (!vehicleId) {
        return
      }

      try {
        const [vehicleData, insuranceData] = await Promise.all([
          getVehicle(vehicleId),
          getVehicleInsurances(),
        ])

        if (ignore) {
          return
        }

        setVehicle(vehicleData)
        setInsurances(filterVehicleInsurances(insuranceData, vehicleId))
      } catch {
        if (!ignore) {
          setError("Impossible de charger les assurances.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    loadInsurances()

    return () => {
      ignore = true
    }
  }, [vehicleId])

  const canEdit = useMemo(
    () => isAdmin || vehicle?.user.id === user?.id,
    [isAdmin, vehicle?.user.id, user?.id]
  )

  async function confirmDelete() {
    if (!insuranceToDelete) {
      return
    }

    setIsDeleting(true)

    try {
      await deleteVehicleInsurance(insuranceToDelete.id)
      setInsurances((current) => current.filter((insurance) => insurance.id !== insuranceToDelete.id))
      setInsuranceToDelete(null)
    } finally {
      setIsDeleting(false)
    }
  }

  if (isLoading) {
    return <div className="text-sm text-muted-foreground">Chargement des assurances...</div>
  }

  if (error || !vehicle) {
    return <ErrorMessage>{error ?? "Véhicule introuvable."}</ErrorMessage>
  }

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={insuranceToDelete !== null}
        title="Supprimer l’assurance ?"
        description={deleteDescription(insuranceToDelete)}
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => {
          if (!open && !isDeleting) {
            setInsuranceToDelete(null)
          }
        }}
        onConfirm={confirmDelete}
      />

      <VehicleEventHeader
        title="Assurances du véhicule"
        description={`${insurances.length} assurance(s) enregistrée(s)`}
        backTo={`/vehicles/${vehicle.id}`}
        backLabel="Retour au véhicule"
        actions={canEdit && (
          <Button asChild>
            <Link to={`/vehicles/${vehicle.id}/insurances/new`}>
              <Plus />
              Ajouter
            </Link>
          </Button>
        )}
      />

      {insurances.length === 0 ? (
        <EmptyCard>Aucune assurance trouvée pour ce véhicule.</EmptyCard>
      ) : (
        <div className="space-y-3">
          {insurances.map((insurance) => (
            <InsuranceCard
              key={insurance.id}
              insurance={insurance}
              vehicleId={vehicle.id}
              canEdit={canEdit}
              isAdmin={isAdmin}
              onDelete={setInsuranceToDelete}
            />
          ))}
        </div>
      )}
    </div>
  )
}

function InsuranceCard({
  insurance,
  vehicleId,
  canEdit,
  isAdmin,
  onDelete,
}: {
  insurance: VehicleInsuranceEvent
  vehicleId: number
  canEdit: boolean
  isAdmin: boolean
  onDelete: (insurance: VehicleInsuranceEvent) => void
}) {
  return (
    <Card className="relative border border-foreground/10 ring-0 transition-colors hover:border-primary/35 hover:bg-muted/30">
      <Link to={`/vehicles/${vehicleId}/insurances/${insurance.id}`} className="absolute inset-0 z-10 rounded-xl" />

      <CardHeader>
        <CardTitle className="flex flex-wrap items-center gap-2">
          <span>{insurance.providerName}</span>
          <Badge variant={isInsuranceActive(insurance) ? "secondary" : "outline"}>
            {isInsuranceActive(insurance) ? "Active" : "Inactive"}
          </Badge>
          <Badge variant="outline">{optionLabel(PAYMENT_FREQUENCIES, insurance.paymentFrequency)}</Badge>
          {!canEdit && <ReadOnlyBadge />}
        </CardTitle>
      </CardHeader>

      <CardContent className="grid gap-3 text-sm md:grid-cols-4">
        <DetailItem label="Police" value={insurance.policyNumber || "—"} />
        <DetailItem label="Début" value={formatDate(insurance.startDate)} />
        <DetailItem label="Fin" value={formatDate(insurance.endDate)} />
        <DetailItem label="Mise à jour" value={formatDateTime(insurance.updatedAt)} />
      </CardContent>

      {(canEdit || isAdmin) && (
        <CardFooter className="relative z-20 justify-end gap-2">
          {canEdit && (
            <Button variant="outline" size="sm" asChild>
              <Link to={`/vehicles/${vehicleId}/insurances/${insurance.id}/edit`}>Modifier</Link>
            </Button>
          )}
          {isAdmin && (
            <Button variant="destructive" size="sm" onClick={() => onDelete(insurance)}>
              <Trash2 />
              Supprimer
            </Button>
          )}
        </CardFooter>
      )}
    </Card>
  )
}

function filterVehicleInsurances(items: VehicleInsuranceEvent[], vehicleId: string) {
  return items
    .filter((item) => item.vehicle.id === Number(vehicleId))
    .sort((a, b) => String(b.startDate ?? "").localeCompare(String(a.startDate ?? "")))
}

function deleteDescription(insurance: VehicleInsuranceEvent | null) {
  return insurance ? `${insurance.providerName} sera masquée de la plateforme.` : ""
}
