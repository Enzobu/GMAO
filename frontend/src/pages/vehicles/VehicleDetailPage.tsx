import { useEffect, useMemo, useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { ArrowLeft, Download, Pencil, Trash2 } from "lucide-react"

import {
  deleteVehicle,
  getVehicle,
  getVehicleHistoryArchive,
} from "@/api/vehicles"
import { DetailMetric } from "@/components/detail-metric"
import { DocumentsPanel } from "@/components/documents-panel"
import { DetailPagePlaceholder } from "@/components/loading-placeholders"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { useAuthStore } from "@/stores/auth-store"
import type {
  Vehicle,
  VehicleInsurance,
  VehicleInspection,
  VehicleMaintenance,
} from "@/types/vehicle"
import {
  VEHICLE_COLORS,
  VEHICLE_FUEL_TYPES,
  VEHICLE_STATUSES,
  VEHICLE_TRANSMISSIONS,
  VEHICLE_TYPES,
  vehicleBadgeVariant,
  vehicleOption,
} from "@/lib/vehicle-labels"
import {
  INSPECTION_RESULTS,
  isInsuranceActive,
  optionLabel,
  PAYMENT_FREQUENCIES,
} from "@/lib/vehicle-events"
import { capitalizeFirstLetter, displayValue } from "@/lib/text-format"

const ALERT_CLASS = [
  "rounded-lg border border-destructive/30 bg-destructive/10 p-4",
  "text-sm text-destructive",
].join(" ")

const VEHICLE_HEADER_CLASS = [
  "flex flex-col gap-4 lg:flex-row lg:items-start",
  "lg:justify-between",
].join(" ")

const VEHICLE_META_CLASS = [
  "flex flex-wrap gap-x-4 gap-y-1 text-sm",
  "text-muted-foreground",
].join(" ")

const WARNING_CLASS = [
  "mb-4 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3",
  "text-sm text-amber-700 dark:text-amber-300",
].join(" ")

export default function VehicleDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const currentUser = useAuthStore((state) => state.user)
  const isAdmin = currentUser?.roles.includes("ROLE_ADMIN") ?? false
  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [archiveError, setArchiveError] = useState<string | null>(null)
  const [isArchiveDownloading, setIsArchiveDownloading] = useState(false)
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false)
  const [isDeleting, setIsDeleting] = useState(false)

  useEffect(() => {
    let ignore = false

    async function loadVehicle() {
      if (!id) {
        return
      }

      try {
        const data = await getVehicle(id)

        if (!ignore) {
          setVehicle(data)
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger le véhicule.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    loadVehicle()

    return () => {
      ignore = true
    }
  }, [id])

  const latestInsurance = useMemo(
    () => latestByDate(vehicle?.vehicleInsurances, "startDate"),
    [vehicle],
  )
  const latestInspection = useMemo(
    () => latestByDate(vehicle?.vehicleInspections, "inspectionDate"),
    [vehicle],
  )
  const latestMaintenance = useMemo(
    () =>
      latestByDate(
        vehicle?.maintenances?.filter((maintenance) => maintenance.finishedAt),
        "finishedAt",
      ),
    [vehicle],
  )

  if (isLoading) {
    return <DetailPagePlaceholder cards={4} />
  }

  if (error || !vehicle) {
    return (
      <div className={ALERT_CLASS}>{error ?? "Véhicule introuvable."}</div>
    )
  }

  const canEdit = isAdmin || vehicle.user.id === currentUser?.id
  const deleteDialogDescription = [
    `${displayVehicleName(vehicle)} sera masqué de la plateforme.`,
    "Aucune donnée ne sera supprimée définitivement.",
  ].join(" ")

  async function confirmDelete() {
    if (!vehicle) {
      return
    }

    setIsDeleting(true)

    try {
      await deleteVehicle(vehicle.id)
      navigate("/vehicles")
    } finally {
      setIsDeleting(false)
    }
  }

  async function downloadHistoryArchive() {
    if (!vehicle) {
      return
    }

    setArchiveError(null)
    setIsArchiveDownloading(true)

    try {
      const { blob, filename } = await getVehicleHistoryArchive(
        vehicle.id,
        vehicleHistoryArchiveFilename(vehicle),
      )
      const url = URL.createObjectURL(blob)
      const link = globalThis.document.createElement("a")

      link.href = url
      link.download = filename
      link.click()
      URL.revokeObjectURL(url)
    } catch {
      setArchiveError("Impossible de télécharger l’historique complet.")
    } finally {
      setIsArchiveDownloading(false)
    }
  }

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={isDeleteDialogOpen}
        title="Archiver le véhicule ?"
        description={deleteDialogDescription}
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => {
          if (!isDeleting) {
            setIsDeleteDialogOpen(open)
          }
        }}
        onConfirm={confirmDelete}
      />

      <div className={VEHICLE_HEADER_CLASS}>
        <div className="space-y-2">
          <h1 className="text-2xl font-semibold tracking-tight">
            {displayVehicleName(vehicle)}
          </h1>
          <div className="flex flex-wrap items-center gap-2">
            <VehicleBadge collection={VEHICLE_TYPES} value={vehicle.type} />
            <VehicleBadge
              collection={VEHICLE_STATUSES}
              value={vehicle.status}
            />
          </div>
          <div className={VEHICLE_META_CLASS}>
            <span>
              <strong className="text-foreground">Immat.</strong>{" "}
              {vehicle.registration.toUpperCase()}
            </span>
            {vehicle.year && (
              <span>
                <strong className="text-foreground">Année</strong>{" "}
                {vehicle.year}
              </span>
            )}
            {vehicle.lastMileage !== null &&
              vehicle.lastMileage !== undefined && (
                <span>
                  <strong className="text-foreground">Km</strong>{" "}
                  {formatNumber(vehicle.lastMileage)}
                </span>
              )}
          </div>
        </div>

        <div className="flex flex-col gap-2 sm:flex-row">
          <Button variant="outline" asChild>
            <Link to="/vehicles">
              <ArrowLeft />
              Retour
            </Link>
          </Button>
          <Button
            type="button"
            variant="outline"
            onClick={downloadHistoryArchive}
            disabled={isArchiveDownloading}
          >
            <Download />
            {isArchiveDownloading
              ? "Téléchargement..."
              : "Télécharger l’historique"}
          </Button>
          <Button asChild disabled={!canEdit}>
            <Link
              to={
                canEdit
                  ? `/vehicles/${vehicle.id}/edit`
                  : `/vehicles/${vehicle.id}`
              }
            >
              <Pencil />
              Modifier
            </Link>
          </Button>
          {isAdmin && (
            <Button
              variant="destructive"
              onClick={() => setIsDeleteDialogOpen(true)}
            >
              <Trash2 />
              Supprimer
            </Button>
          )}
        </div>
      </div>

      {archiveError && <div className={ALERT_CLASS}>{archiveError}</div>}

      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Informations générales</h2>
        <div className="grid gap-4 xl:grid-cols-2">
          <InfoCard
            title="Identité"
            rows={[
              ["Nom", displayValue(vehicle.name)],
              ["Marque", displayValue(vehicle.brand)],
              ["Modèle", displayValue(vehicle.model)],
              ["Immatriculation", vehicle.registration.toUpperCase()],
              ["Type", labelFor(VEHICLE_TYPES, vehicle.type)],
              ["Statut", labelFor(VEHICLE_STATUSES, vehicle.status)],
            ]}
          />
          <InfoCard
            title="Caractéristiques"
            rows={[
              ["Année", vehicle.year ?? "—"],
              ["VIN", vehicle.vin || "—"],
              ["Moteur", vehicle.engine || "—"],
              ["Carburant", labelFor(VEHICLE_FUEL_TYPES, vehicle.fuelType)],
              [
                "Transmission",
                labelFor(VEHICLE_TRANSMISSIONS, vehicle.transmission),
              ],
              ["Couleur", labelFor(VEHICLE_COLORS, vehicle.color)],
            ]}
          />
        </div>
        <Card>
          <CardHeader>
            <CardTitle>Achat et suivi</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-3 md:grid-cols-4">
            <DetailMetric
              label="Date d’achat"
              value={formatDate(vehicle.purchaseDate)}
            />
            <DetailMetric
              label="Prix d’achat"
              value={formatPrice(vehicle.purchasePrice)}
            />
            <DetailMetric
              label="Dernier kilométrage"
              value={
                vehicle.lastMileage !== null &&
                vehicle.lastMileage !== undefined
                  ? `${formatNumber(vehicle.lastMileage)} km`
                  : "—"
              }
            />
            <DetailMetric label="Propriétaire" value={userLabel(vehicle.user)} />
          </CardContent>
        </Card>
      </section>

      <section className="space-y-3">
        <h2 className="text-lg font-semibold">
          Assurance & Contrôle technique
        </h2>
        <div className="grid gap-4 xl:grid-cols-2">
          <InsuranceCard
            vehicleId={vehicle.id}
            insurance={latestInsurance}
            hasActiveInsurance={hasActiveInsurance(vehicle.vehicleInsurances)}
          />
          <InspectionCard
            vehicleId={vehicle.id}
            inspection={latestInspection}
          />
        </div>
      </section>

      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Interventions</h2>
        <MaintenanceCard
          vehicleId={vehicle.id}
          maintenance={latestMaintenance}
          canEdit={canEdit}
        />
      </section>

      <DocumentsPanel
        parent={{ type: "vehicles", id: vehicle.id }}
        canManage={canEdit}
        canDelete={isAdmin}
        emptyLabel="Aucun document disponible pour ce véhicule."
      />
    </div>
  )
}

function InfoCard({
  title,
  rows,
}: Readonly<{ title: string; rows: [string, string | number][] }>) {
  return (
    <Card className="h-full">
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent className="flex h-full flex-col">
        <dl className="grid gap-2 text-sm sm:grid-cols-[10rem_1fr]">
          {rows.map(([label, value]) => (
            <div key={label} className="contents">
              <dt className="text-muted-foreground">{label}</dt>
              <dd>{value}</dd>
            </div>
          ))}
        </dl>
      </CardContent>
    </Card>
  )
}

function InsuranceCard({
  vehicleId,
  insurance,
  hasActiveInsurance,
}: Readonly<{
  vehicleId: number
  insurance?: VehicleInsurance
  hasActiveInsurance: boolean
}>) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center justify-between gap-2">
          Assurance
          <Badge variant="outline">Dernière</Badge>
        </CardTitle>
      </CardHeader>
      <CardContent>
        {!hasActiveInsurance && (
          <div className={WARNING_CLASS}>
            Ce véhicule n’a aucune assurance active.
          </div>
        )}
        <div className="flex-1">
          {insurance ? (
            <div className="grid gap-3 text-sm md:grid-cols-2">
              <Detail label="Assureur" value={insurance.providerName} />
              <Detail label="Police" value={insurance.policyNumber || "—"} />
              <Detail
                label="Statut"
                value={isInsuranceActive(insurance) ? "Active" : "Inactive"}
              />
              <Detail label="Début" value={formatDate(insurance.startDate)} />
              <Detail label="Fin" value={formatDate(insurance.endDate)} />
              <Detail
                label="Paiement"
                value={optionLabel(
                  PAYMENT_FREQUENCIES,
                  insurance.paymentFrequency,
                )}
              />
            </div>
          ) : (
            <EmptyText>Aucune assurance renseignée.</EmptyText>
          )}
        </div>
        <Button variant="outline" className="mt-4 w-fit" asChild>
          <Link to={`/vehicles/${vehicleId}/insurances`}>
            Voir les assurances
          </Link>
        </Button>
      </CardContent>
    </Card>
  )
}

function hasActiveInsurance(insurances: VehicleInsurance[] | undefined) {
  return (
    insurances?.some(
      (insurance) => !isArchived(insurance) && isInsuranceActive(insurance),
    ) ?? false
  )
}

function InspectionCard({
  vehicleId,
  inspection,
}: Readonly<{ vehicleId: number; inspection?: VehicleInspection }>) {
  return (
    <Card className="h-full">
      <CardHeader>
        <CardTitle className="flex items-center justify-between gap-2">
          Contrôle technique
          <Badge variant="outline">Dernier</Badge>
        </CardTitle>
      </CardHeader>
      <CardContent className="flex h-full flex-col">
        <div className="flex-1">
          {inspection ? (
            <div className="grid gap-3 text-sm md:grid-cols-2">
              <Detail
                label="Résultat"
                value={optionLabel(INSPECTION_RESULTS, inspection.result)}
              />
              <Detail
                label="Date"
                value={formatDate(inspection.inspectionDate)}
              />
              <Detail
                label="Valide jusqu’au"
                value={formatDate(inspection.validUntil)}
              />
              <Detail
                label="Kilométrage"
                value={
                  inspection.mileage !== null &&
                  inspection.mileage !== undefined
                    ? `${formatNumber(inspection.mileage)} km`
                    : "—"
                }
              />
              <Detail
                label="Contre-visite"
                value={
                  inspection.counterVisitRequired ? "Requise" : "Non requise"
                }
              />
              <Detail
                label="Centre"
                value={inspection.center?.name ?? "Non renseigné"}
              />
            </div>
          ) : (
            <EmptyText>Aucun contrôle technique renseigné.</EmptyText>
          )}
        </div>
        <Button variant="outline" className="mt-4 w-fit" asChild>
          <Link to={`/vehicles/${vehicleId}/inspections`}>
            Voir les contrôles
          </Link>
        </Button>
      </CardContent>
    </Card>
  )
}

function MaintenanceCard({
  vehicleId,
  maintenance,
  canEdit,
}: Readonly<{
  vehicleId: number
  maintenance?: VehicleMaintenance
  canEdit: boolean
}>) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center justify-between gap-2">
          Dernière intervention
          <Badge variant="outline">Dernière réalisée</Badge>
        </CardTitle>
      </CardHeader>
      <CardContent>
        {maintenance ? (
          <div className="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-4">
            <Detail
              label="Type"
              value={maintenance.maintenanceType?.name ?? "—"}
            />
            <Detail label="Statut" value={maintenance.status ?? "—"} />
            <Detail label="Début" value={formatDate(maintenance.startedAt)} />
            <Detail label="Fin" value={formatDate(maintenance.finishedAt)} />
            <Detail
              label="Kilométrage"
              value={
                maintenance.mileage !== null &&
                maintenance.mileage !== undefined
                  ? `${formatNumber(maintenance.mileage)} km`
                  : "—"
              }
            />
            <Detail
              label="Mode"
              value={maintenance.isExternal ? "Externe" : "Interne"}
            />
            <Detail
              label="Prochaine échéance km"
              value={
                maintenance.nextDueMileage !== null &&
                maintenance.nextDueMileage !== undefined
                  ? `${formatNumber(maintenance.nextDueMileage)} km`
                  : "—"
              }
            />
            <Detail
              label="Prochaine échéance date"
              value={formatDate(maintenance.nextDueAt)}
            />
          </div>
        ) : (
          <EmptyText>Aucune intervention réalisée.</EmptyText>
        )}
        <div className="mt-4 flex flex-wrap gap-2">
          <Button variant="outline" asChild>
            <Link to={`/vehicles/${vehicleId}/interventions`}>
              Voir les interventions
            </Link>
          </Button>
          {canEdit && (
            <Button asChild>
              <Link to={`/vehicles/${vehicleId}/interventions/new`}>
                Ajouter une intervention
              </Link>
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  )
}

function Detail({ label, value }: Readonly<{ label: string; value: string }>) {
  return (
    <div>
      <div className="text-xs text-muted-foreground">{label}</div>
      <div className="mt-1 font-medium">{value}</div>
    </div>
  )
}

function EmptyText({ children }: Readonly<{ children: string }>) {
  return <div className="text-sm text-muted-foreground">{children}</div>
}

function VehicleBadge({
  collection,
  value,
}: Readonly<{
  collection: readonly { value: string; label: string; variant: string }[]
  value?: string | null
}>) {
  const option = vehicleOption(collection, value)

  if (!option) {
    return null
  }

  return (
    <Badge variant={vehicleBadgeVariant(option.variant)}>{option.label}</Badge>
  )
}

type DateLikeValue = string | null | undefined

type DateLikeKey<T> = {
  [K in keyof T]: T[K] extends DateLikeValue ? K : never
}[keyof T]

function latestByDate<T extends { isDeleted?: boolean }>(
  items: T[] | undefined,
  field: DateLikeKey<T>,
) {
  if (!Array.isArray(items)) {
    return undefined
  }

  return items
    .filter((item) => !isArchived(item) && item[field])
    .toSorted((a, b) => {
      const aDate = a[field]
      const bDate = b[field]

      if (typeof aDate !== "string" || typeof bDate !== "string") {
        return 0
      }

      return bDate.localeCompare(aDate)
    })[0]
}

function isArchived(item: { isDeleted?: boolean; deleted?: boolean }) {
  return item.isDeleted === true || item.deleted === true
}

function labelFor(
  collection: readonly { value: string; label: string }[],
  value?: string | null,
) {
  return collection.find((item) => item.value === value)?.label ?? "—"
}

function displayVehicleName(vehicle: Vehicle) {
  return (
    capitalizeFirstLetter(vehicle.name) ||
    [capitalizeFirstLetter(vehicle.brand), capitalizeFirstLetter(vehicle.model)]
      .join(" ")
      .trim()
  )
}

function vehicleHistoryArchiveFilename(vehicle: Vehicle) {
  const registration = vehicle.registration.toUpperCase()

  return sanitizeArchiveFilename(
    `historique_${displayVehicleName(vehicle)}_${registration}`,
  ) + ".zip"
}

function sanitizeArchiveFilename(value: string) {
  let filename = value
    .trim()
    .replaceAll(/[\\/:*?"<>|]+/g, "_")
    .replaceAll(/\s+/g, "_")
    .replaceAll(/_+/g, "_")

  while (filename.startsWith("_")) {
    filename = filename.slice(1)
  }

  while (filename.endsWith("_")) {
    filename = filename.slice(0, -1)
  }

  return filename
}

function userLabel(user: Vehicle["user"]) {
  const name =
    `${capitalizeFirstLetter(user.firstname)} ${capitalizeFirstLetter(
      user.lastname,
    )}`.trim()

  return name || user.email
}

function formatDate(value?: string | null) {
  if (!value) {
    return "—"
  }

  return new Intl.DateTimeFormat("fr-FR").format(new Date(value))
}

function formatNumber(value: number) {
  return new Intl.NumberFormat("fr-FR").format(value)
}

function formatPrice(value?: string | null) {
  if (!value) {
    return "—"
  }

  const amount = Number(value)

  if (!Number.isFinite(amount)) {
    return `${value} €`
  }

  return `${new Intl.NumberFormat("fr-FR", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount)} €`
}
