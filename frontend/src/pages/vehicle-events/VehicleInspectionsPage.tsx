import { useEffect, useMemo, useState } from "react"
import { Link, useParams } from "react-router-dom"
import { Plus, Trash2 } from "lucide-react"

import {
  deleteVehicleInspection,
  getVehicleInspectionsPage,
} from "@/api/vehicle-events"
import { emptyCollectionPage } from "@/api/api-collection"
import { getVehicle } from "@/api/vehicles"
import {
  itemsPerPageSize,
  type ItemsPerPageValue,
} from "@/components/list-page-pagination"
import { ListPaginationControls } from "@/components/list-pagination-controls"
import { ListPagePlaceholder } from "@/components/loading-placeholders"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { useLocalStorageState } from "@/hooks/use-local-storage-state"
import { useAuthStore } from "@/stores/auth-store"
import type { Vehicle } from "@/types/vehicle"
import type { VehicleInspectionEvent } from "@/types/vehicle-events"
import {
  formatDate,
  INSPECTION_RESULTS,
  inspectionResultVariant,
  optionLabel,
} from "@/lib/vehicle-events"
import {
  DetailItem,
  EmptyCard,
  ErrorMessage,
  ReadOnlyBadge,
  VehicleEventHeader,
} from "./components"

const EVENT_CARD_CLASS = [
  "relative border border-foreground/10 ring-0 transition-colors",
  "hover:border-primary/35 hover:bg-muted/30",
].join(" ")

const COUNTER_VISIT_BADGE_CLASS = [
  "border-amber-500/30 bg-amber-500/10 text-amber-700",
  "dark:text-amber-300",
].join(" ")

export default function VehicleInspectionsPage() {
  const { vehicleId } = useParams()
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false

  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [inspections, setInspections] = useState<VehicleInspectionEvent[]>([])
  const [inspectionsPage, setInspectionsPage] = useState(
    emptyCollectionPage<VehicleInspectionEvent>(12),
  )
  const [itemsPerPage, setItemsPerPage] =
    useLocalStorageState<ItemsPerPageValue>(
      "vehicleInspections.itemsPerPage",
      "12",
    )
  const [page, setPage] = useState(1)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [inspectionToDelete, setInspectionToDelete] =
    useState<VehicleInspectionEvent | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  useEffect(() => {
    let ignore = false

    async function loadInspections() {
      if (!vehicleId) {
        return
      }

      try {
        const [vehicleData, inspectionData] = await Promise.all([
          getVehicle(vehicleId),
          getVehicleInspectionsPage({
            page,
            itemsPerPage: itemsPerPageSize(itemsPerPage),
            vehicleId,
          }),
        ])

        if (ignore) {
          return
        }

        setVehicle(vehicleData)
        setInspections(inspectionData.items)
        setInspectionsPage(inspectionData)
      } catch {
        if (!ignore) {
          setError("Impossible de charger les contrôles techniques.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    loadInspections()

    return () => {
      ignore = true
    }
  }, [itemsPerPage, page, vehicleId])

  const canEdit = useMemo(
    () => isAdmin || vehicle?.user.id === user?.id,
    [isAdmin, vehicle?.user.id, user?.id]
  )

  async function confirmDelete() {
    if (!inspectionToDelete) {
      return
    }

    setIsDeleting(true)

    try {
      await deleteVehicleInspection(inspectionToDelete.id)
      setInspections((current) => (
        current.filter((inspection) => inspection.id !== inspectionToDelete.id)
      ))
      setInspectionsPage((current) => ({
        ...current,
        totalItems: Math.max(0, current.totalItems - 1),
      }))
      setInspectionToDelete(null)
    } finally {
      setIsDeleting(false)
    }
  }

  if (isLoading) {
    return <ListPagePlaceholder filters={0} items={4} />
  }

  if (error || !vehicle) {
    return <ErrorMessage>{error ?? "Véhicule introuvable."}</ErrorMessage>
  }

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={inspectionToDelete !== null}
        title="Supprimer le contrôle technique ?"
        description={deleteDescription(inspectionToDelete)}
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => {
          if (!open && !isDeleting) {
            setInspectionToDelete(null)
          }
        }}
        onConfirm={confirmDelete}
      />

      <VehicleEventHeader
        title="Contrôles techniques du véhicule"
        description={`${inspectionsPage.totalItems} contrôle(s) technique(s)`}
        backTo={`/vehicles/${vehicle.id}`}
        backLabel="Retour au véhicule"
        actions={canEdit && (
          <Button asChild>
            <Link to={`/vehicles/${vehicle.id}/inspections/new`}>
              <Plus />
              Ajouter
            </Link>
          </Button>
        )}
      />

      <ListPaginationControls
        itemLabel="contrôle(s) technique(s)"
        pagination={inspectionsPage}
        itemsPerPage={itemsPerPage}
        onItemsPerPageChange={(value) => {
          setItemsPerPage(value)
          setPage(1)
        }}
        onPageChange={setPage}
      />

      {inspections.length === 0 ? (
        <EmptyCard>
          Aucun contrôle technique trouvé pour ce véhicule.
        </EmptyCard>
      ) : (
        <div className="space-y-3">
          {inspections.map((inspection) => (
            <InspectionCard
              key={inspection.id}
              inspection={inspection}
              vehicleId={vehicle.id}
              canEdit={canEdit}
              isAdmin={isAdmin}
              onDelete={setInspectionToDelete}
            />
          ))}
        </div>
      )}

      <ListPaginationControls
        itemLabel="contrôle(s) technique(s)"
        pagination={inspectionsPage}
        itemsPerPage={itemsPerPage}
        onItemsPerPageChange={(value) => {
          setItemsPerPage(value)
          setPage(1)
        }}
        onPageChange={setPage}
      />
    </div>
  )
}

function InspectionCard({
  inspection,
  vehicleId,
  canEdit,
  isAdmin,
  onDelete,
}: Readonly<{
  inspection: VehicleInspectionEvent
  vehicleId: number
  canEdit: boolean
  isAdmin: boolean
  onDelete: (inspection: VehicleInspectionEvent) => void
}>) {
  return (
    <Card className={EVENT_CARD_CLASS}>
      <Link
        to={`/vehicles/${vehicleId}/inspections/${inspection.id}`}
        className="absolute inset-0 z-10 rounded-xl"
      />

      <CardHeader>
        <CardTitle className="flex flex-wrap items-center gap-2">
          <span>Contrôle du {formatDate(inspection.inspectionDate)}</span>
          <Badge variant={inspectionResultVariant(inspection.result)}>
            {optionLabel(INSPECTION_RESULTS, inspection.result)}
          </Badge>
          <CounterVisitBadge required={inspection.counterVisitRequired} />
          {!canEdit && <ReadOnlyBadge />}
        </CardTitle>
      </CardHeader>

      <CardContent className="grid gap-3 text-sm md:grid-cols-4">
        <DetailItem
          label="Valide jusqu’au"
          value={formatDate(inspection.validUntil)}
        />
        <DetailItem
          label="Kilométrage"
          value={formatMileage(inspection.mileage)}
        />
        <DetailItem
          label="Contre-visite avant"
          value={formatDate(inspection.counterVisitDueAt)}
        />
        <DetailItem label="Centre" value={inspection.center?.name ?? "—"} />
      </CardContent>

      {(canEdit || isAdmin) && (
        <CardFooter className="relative z-20 justify-end gap-2">
          {canEdit && (
            <Button variant="outline" size="sm" asChild>
              <Link
                to={
                  `/vehicles/${vehicleId}/inspections/`
                  + `${inspection.id}/edit`
                }
              >
                Modifier
              </Link>
            </Button>
          )}
          {isAdmin && (
            <Button
              variant="destructive"
              size="sm"
              onClick={() => onDelete(inspection)}
            >
              <Trash2 />
              Supprimer
            </Button>
          )}
        </CardFooter>
      )}
    </Card>
  )
}

function CounterVisitBadge({ required }: Readonly<{ required: boolean }>) {
  if (!required) {
    return <Badge variant="secondary">Sans contre-visite</Badge>
  }

  return (
    <Badge
      variant="outline"
      className={COUNTER_VISIT_BADGE_CLASS}
    >
      Contre-visite requise
    </Badge>
  )
}

function deleteDescription(inspection: VehicleInspectionEvent | null) {
  return inspection
    ? `Le contrôle du ${formatDate(inspection.inspectionDate)} sera masqué `
      + "de la plateforme."
    : ""
}

function formatMileage(value?: number | null) {
  return value == null
    ? "—"
    : `${new Intl.NumberFormat("fr-FR").format(value)} km`
}
