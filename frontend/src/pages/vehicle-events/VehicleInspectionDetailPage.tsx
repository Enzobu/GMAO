import { useEffect, useMemo, useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { Pencil, Trash2 } from "lucide-react"

import { deleteVehicleInspection, getVehicleInspection } from "@/api/vehicle-events"
import { getVehicle } from "@/api/vehicles"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { useAuthStore } from "@/stores/auth-store"
import type { Vehicle } from "@/types/vehicle"
import type { VehicleInspectionEvent } from "@/types/vehicle-events"
import { formatDate, formatDateTime, INSPECTION_RESULTS, inspectionResultVariant, optionLabel } from "@/lib/vehicle-events"
import { DetailItem, ErrorMessage, ReadOnlyBadge, VehicleEventHeader } from "./components"

export default function VehicleInspectionDetailPage() {
  const { vehicleId, inspectionId } = useParams()
  const navigate = useNavigate()
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false

  const [inspection, setInspection] = useState<VehicleInspectionEvent | null>(null)
  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false)
  const [isDeleting, setIsDeleting] = useState(false)

  useEffect(() => {
    let ignore = false

    async function loadInspection() {
      if (!inspectionId) {
        return
      }

      try {
        const [inspectionData, vehicleData] = await Promise.all([
          getVehicleInspection(inspectionId),
          vehicleId ? getVehicle(vehicleId) : Promise.resolve(null),
        ])

        if (!ignore) {
          setInspection(inspectionData)
          setVehicle(vehicleData)
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger le contrôle technique.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    loadInspection()

    return () => {
      ignore = true
    }
  }, [inspectionId, vehicleId])

  const canEdit = useMemo(
    () => isAdmin || vehicle?.user.id === user?.id,
    [isAdmin, vehicle?.user.id, user?.id]
  )

  async function confirmDelete() {
    if (!inspection) {
      return
    }

    setIsDeleting(true)

    try {
      await deleteVehicleInspection(inspection.id)
      navigate(`/vehicles/${vehicleId ?? inspection.vehicle.id}/inspections`)
    } finally {
      setIsDeleting(false)
    }
  }

  if (isLoading) {
    return <div className="text-sm text-muted-foreground">Chargement du contrôle...</div>
  }

  if (error || !inspection) {
    return <ErrorMessage>{error ?? "Contrôle technique introuvable."}</ErrorMessage>
  }

  const routeVehicleId = vehicleId ?? String(inspection.vehicle.id)

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={isDeleteDialogOpen}
        title="Supprimer le contrôle technique ?"
        description={`Le contrôle du ${formatDate(inspection.inspectionDate)} sera masqué de la plateforme.`}
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => {
          if (!isDeleting) {
            setIsDeleteDialogOpen(open)
          }
        }}
        onConfirm={confirmDelete}
      />

      <VehicleEventHeader
        title={`Contrôle du ${formatDate(inspection.inspectionDate)}`}
        backTo={`/vehicles/${routeVehicleId}/inspections`}
        actions={(
          <>
            {canEdit && (
              <Button asChild>
                <Link to={`/vehicles/${routeVehicleId}/inspections/${inspection.id}/edit`}>
                  <Pencil />
                  Modifier
                </Link>
              </Button>
            )}
            {isAdmin && (
              <Button variant="destructive" onClick={() => setIsDeleteDialogOpen(true)}>
                <Trash2 />
                Supprimer
              </Button>
            )}
          </>
        )}
      />

      <div className="flex flex-wrap gap-2">
        <Badge variant={inspectionResultVariant(inspection.result)}>
          {optionLabel(INSPECTION_RESULTS, inspection.result)}
        </Badge>
        <CounterVisitBadge required={inspection.counterVisitRequired} />
        {!canEdit && <ReadOnlyBadge />}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Détails</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
          <DetailItem boxed label="Date" value={formatDate(inspection.inspectionDate)} />
          <DetailItem boxed label="Valide jusqu’au" value={formatDate(inspection.validUntil)} />
          <DetailItem boxed label="Kilométrage" value={formatMileage(inspection.mileage)} />
          <DetailItem boxed label="Centre" value={inspection.center?.name ?? "—"} />
          <DetailItem boxed label="Contre-visite avant" value={formatDate(inspection.counterVisitDueAt)} />
          <DetailItem boxed label="Créé le" value={formatDateTime(inspection.createdAt)} />
          <DetailItem boxed label="Mise à jour" value={formatDateTime(inspection.updatedAt)} />
        </CardContent>
      </Card>

      {inspection.notes && (
        <Card>
          <CardHeader>
            <CardTitle>Notes</CardTitle>
          </CardHeader>
          <CardContent className="whitespace-pre-wrap text-sm text-muted-foreground">
            {inspection.notes}
          </CardContent>
        </Card>
      )}
    </div>
  )
}

function CounterVisitBadge({ required }: Readonly<{ required: boolean }>) {
  if (!required) {
    return <Badge variant="secondary">Sans contre-visite</Badge>
  }

  return (
    <Badge variant="outline" className="border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300">
      Contre-visite requise
    </Badge>
  )
}

function formatMileage(value?: number | null) {
  return value == null ? "—" : `${new Intl.NumberFormat("fr-FR").format(value)} km`
}
