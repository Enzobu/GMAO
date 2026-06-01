import { useEffect, useMemo, useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { Pencil, Trash2 } from "lucide-react"

import { deleteIntervention, getIntervention } from "@/api/interventions"
import { getVehicle } from "@/api/vehicles"
import { DocumentsPanel } from "@/components/documents-panel"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { useAuthStore } from "@/stores/auth-store"
import type { Intervention } from "@/types/intervention"
import type { Vehicle } from "@/types/vehicle"
import {
  formatDateTime,
  formatNumber,
  vehicleDisplayName,
} from "@/lib/intervention-utils"
import {
  Detail,
  ErrorMessage,
  InterventionBadges,
  InterventionHeader,
} from "./components"

export default function InterventionDetailPage({
  vehicleScoped = false,
}: Readonly<{ vehicleScoped?: boolean }>) {
  const { vehicleId, interventionId, id } = useParams()
  const navigate = useNavigate()
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false
  const interventionRouteId = interventionId ?? id
  const [intervention, setIntervention] = useState<Intervention | null>(null)
  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [isDeleteOpen, setIsDeleteOpen] = useState(false)
  const [isDeleting, setIsDeleting] = useState(false)

  useEffect(() => {
    let ignore = false

    async function load() {
      if (!interventionRouteId) return
      try {
        const [interventionData, vehicleData] = await Promise.all([
          getIntervention(interventionRouteId),
          vehicleScoped && vehicleId
            ? getVehicle(vehicleId)
            : Promise.resolve(null),
        ])
        if (!ignore) {
          setIntervention(interventionData)
          setVehicle(vehicleData)
        }
      } catch {
        if (!ignore) setError("Impossible de charger l’intervention.")
      } finally {
        if (!ignore) setIsLoading(false)
      }
    }

    load()
    return () => {
      ignore = true
    }
  }, [interventionRouteId, vehicleId, vehicleScoped])

  const ownerId = vehicle?.user?.id ?? intervention?.vehicle?.user?.id
  const canEdit = useMemo(
    () => isAdmin || ownerId === user?.id,
    [isAdmin, ownerId, user?.id],
  )

  async function confirmDelete() {
    if (!intervention) return
    setIsDeleting(true)
    try {
      await deleteIntervention(intervention.id)
      navigate(
        vehicleScoped && vehicleId
          ? `/vehicles/${vehicleId}/interventions`
          : "/interventions",
      )
    } finally {
      setIsDeleting(false)
    }
  }

  if (isLoading) {
    return (
      <div className="text-sm text-muted-foreground">
        Chargement de l’intervention...
      </div>
    )
  }

  if (error || !intervention) {
    return (
      <ErrorMessage>{error ?? "Intervention introuvable."}</ErrorMessage>
    )
  }

  const routeVehicleId = vehicleId ?? String(intervention.vehicle.id)
  const backTo = vehicleScoped
    ? `/vehicles/${routeVehicleId}/interventions`
    : "/interventions"

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={isDeleteOpen}
        title="Supprimer l’intervention ?"
        description={
          "L’intervention sera masquée et les pièces seront restaurées "
            + "si elle était réalisée."
        }
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => !isDeleting && setIsDeleteOpen(open)}
        onConfirm={confirmDelete}
      />

      <InterventionHeader
        title={intervention.maintenanceType?.name ?? "Intervention"}
        description={`Véhicule : ${vehicleDisplayName(intervention.vehicle)}`}
        backTo={backTo}
        actions={vehicleScoped && (
          <>
            {canEdit && (
              <Button asChild>
                <Link
                  to={
                    `/vehicles/${routeVehicleId}/interventions/`
                      + `${intervention.id}/edit`
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
                onClick={() => setIsDeleteOpen(true)}
              >
                <Trash2 />
                Supprimer
              </Button>
            )}
          </>
        )}
      />

      <InterventionBadges
        intervention={intervention}
        readOnly={!isAdmin && (!vehicleScoped || !canEdit)}
      />

      <Card>
        <CardHeader>
          <CardTitle>Détails</CardTitle>
        </CardHeader>
        <CardContent
          className="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4"
        >
          <Detail
            boxed
            label="Kilométrage"
            value={`${formatNumber(intervention.mileage)} km`}
          />
          <Detail
            boxed
            label="Prévu"
            value={formatDateTime(intervention.plannedAt)}
          />
          <Detail
            boxed
            label="Début"
            value={formatDateTime(intervention.startedAt)}
          />
          <Detail
            boxed
            label="Fin"
            value={formatDateTime(intervention.finishedAt)}
          />
          <Detail
            boxed
            label="Mode"
            value={intervention.isExternal ? "Externe" : "Interne"}
          />
          <Detail
            boxed
            label="Prochaine échéance km"
            value={intervention.nextDueMileage == null
              ? "—"
              : `${formatNumber(intervention.nextDueMileage)} km`}
          />
          <Detail
            boxed
            label="Prochaine échéance date"
            value={formatDateTime(intervention.nextDueAt)}
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Pièces utilisées</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {intervention.maintenanceParts?.length ? (
            intervention.maintenanceParts.map((line, index) => (
              <div
                key={
                  `${partKey(line.part)}-${line.id ?? line.quantity}`
                    + `-${index}`
                }
                className={
                  "flex items-center justify-between rounded-lg border "
                    + "p-3 text-sm"
                }
              >
                <span>{partName(line.part)}</span>
                <span className="font-medium">x{line.quantity}</span>
              </div>
            ))
          ) : (
            <div className="text-sm text-muted-foreground">
              Aucune pièce renseignée.
            </div>
          )}
        </CardContent>
      </Card>

      {intervention.notes && (
        <Card>
          <CardHeader>
            <CardTitle>Notes</CardTitle>
          </CardHeader>
          <CardContent
            className="whitespace-pre-wrap text-sm text-muted-foreground"
          >
            {intervention.notes}
          </CardContent>
        </Card>
      )}

      <DocumentsPanel
        parent={{ type: "maintenances", id: intervention.id }}
        canManage={canEdit}
        canDelete={isAdmin}
        emptyLabel="Aucun document disponible pour cette intervention."
      />
    </div>
  )
}

function partName(
  part: Intervention["maintenanceParts"] extends (infer T)[] | undefined
    ? T extends { part: infer P } ? P : never
    : never,
) {
  if (typeof part === "string") {
    return `Pièce ${part.split("/").findLast(Boolean) ?? ""}`.trim()
  }

  return part.partType?.name ?? "Pièce"
}

function partKey(
  part: Intervention["maintenanceParts"] extends (infer T)[] | undefined
    ? T extends { part: infer P } ? P : never
    : never,
) {
  if (typeof part === "string") {
    return part
  }

  return String(part.id ?? "part")
}
