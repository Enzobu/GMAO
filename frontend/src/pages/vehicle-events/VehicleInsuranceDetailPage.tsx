import { useEffect, useMemo, useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { Pencil, Trash2 } from "lucide-react"

import {
  deleteVehicleInsurance,
  getVehicleInsurance,
} from "@/api/vehicle-events"
import { getVehicle } from "@/api/vehicles"
import { DocumentsPanel } from "@/components/documents-panel"
import { DetailPagePlaceholder } from "@/components/loading-placeholders"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { useAuthStore } from "@/stores/auth-store"
import type { Vehicle } from "@/types/vehicle"
import type { VehicleInsuranceEvent } from "@/types/vehicle-events"
import {
  formatDate,
  formatDateTime,
  isInsuranceActive,
  optionLabel,
  PAYMENT_FREQUENCIES,
} from "@/lib/vehicle-events"
import {
  DetailItem,
  ErrorMessage,
  ReadOnlyBadge,
  VehicleEventHeader,
} from "./components"

const DETAILS_GRID_CLASS = "grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4"

export default function VehicleInsuranceDetailPage() {
  const { vehicleId, insuranceId } = useParams()
  const navigate = useNavigate()
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false

  const [insurance, setInsurance] = useState<VehicleInsuranceEvent | null>(null)
  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false)
  const [isDeleting, setIsDeleting] = useState(false)

  useEffect(() => {
    let ignore = false

    async function loadInsurance() {
      if (!insuranceId) {
        return
      }

      try {
        const [insuranceData, vehicleData] = await Promise.all([
          getVehicleInsurance(insuranceId),
          vehicleId ? getVehicle(vehicleId) : Promise.resolve(null),
        ])

        if (!ignore) {
          setInsurance(insuranceData)
          setVehicle(vehicleData)
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger l’assurance.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    loadInsurance()

    return () => {
      ignore = true
    }
  }, [insuranceId, vehicleId])

  const canEdit = useMemo(
    () => isAdmin || vehicle?.user.id === user?.id,
    [isAdmin, vehicle?.user.id, user?.id]
  )

  async function confirmDelete() {
    if (!insurance) {
      return
    }

    setIsDeleting(true)

    try {
      await deleteVehicleInsurance(insurance.id)
      navigate(`/vehicles/${vehicleId ?? insurance.vehicle.id}/insurances`)
    } finally {
      setIsDeleting(false)
    }
  }

  if (isLoading) {
    return <DetailPagePlaceholder />
  }

  if (error || !insurance) {
    return <ErrorMessage>{error ?? "Assurance introuvable."}</ErrorMessage>
  }

  const routeVehicleId = vehicleId ?? String(insurance.vehicle.id)

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={isDeleteDialogOpen}
        title="Supprimer l’assurance ?"
        description={
          `${insurance.providerName} sera masquée de la plateforme.`
        }
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
        title={insurance.providerName}
        backTo={`/vehicles/${routeVehicleId}/insurances`}
        actions={(
          <>
            {canEdit && (
              <Button asChild>
                <Link
                  to={
                    `/vehicles/${routeVehicleId}/insurances/`
                    + `${insurance.id}/edit`
                  }
                >
                  <Pencil />
                  Modifier
                </Link>
              </Button>
            )}
            {isAdmin && (
              <Button
                variant="destructive"
                onClick={() => setIsDeleteDialogOpen(true)}
              >
                <Trash2 />
                Supprimer
              </Button>
            )}
          </>
        )}
      />

      <div className="flex flex-wrap gap-2">
        <Badge variant={isInsuranceActive(insurance) ? "secondary" : "outline"}>
          {isInsuranceActive(insurance) ? "Active" : "Inactive"}
        </Badge>
        <Badge variant="outline">
          {optionLabel(PAYMENT_FREQUENCIES, insurance.paymentFrequency)}
        </Badge>
        {!canEdit && <ReadOnlyBadge />}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Détails</CardTitle>
        </CardHeader>
        <CardContent className={DETAILS_GRID_CLASS}>
          <DetailItem
            boxed
            label="Police"
            value={insurance.policyNumber || "—"}
          />
          <DetailItem
            boxed
            label="Début"
            value={formatDate(insurance.startDate)}
          />
          <DetailItem boxed label="Fin" value={formatDate(insurance.endDate)} />
          <DetailItem
            boxed
            label="Paiement"
            value={optionLabel(PAYMENT_FREQUENCIES, insurance.paymentFrequency)}
          />
          <DetailItem
            boxed
            label="Créée le"
            value={formatDateTime(insurance.createdAt)}
          />
          <DetailItem
            boxed
            label="Mise à jour"
            value={formatDateTime(insurance.updatedAt)}
          />
        </CardContent>
      </Card>

      <DocumentsPanel
        parent={{ type: "vehicle_insurances", id: insurance.id }}
        canManage={canEdit}
        canDelete={isAdmin}
        emptyLabel="Aucun document disponible pour cette assurance."
      />
    </div>
  )
}
