import { useEffect, useMemo, useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { ArrowLeft, Pencil, Trash2 } from "lucide-react"

import { deleteVehicle, getVehicle } from "@/api/vehicles"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { useAuthStore } from "@/stores/auth-store"
import type { Vehicle, VehicleInsurance, VehicleInspection, VehicleMaintenance } from "@/types/vehicle"
import {
  VEHICLE_COLORS,
  VEHICLE_FUEL_TYPES,
  VEHICLE_STATUSES,
  VEHICLE_TRANSMISSIONS,
  VEHICLE_TYPES,
  vehicleBadgeVariant,
  vehicleOption,
} from "@/lib/vehicle-labels"
import { INSPECTION_RESULTS, isInsuranceActive, optionLabel, PAYMENT_FREQUENCIES } from "@/lib/vehicle-events"

export default function VehicleDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const currentUser = useAuthStore((state) => state.user)
  const isAdmin = currentUser?.roles.includes("ROLE_ADMIN") ?? false
  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
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

  const latestInsurance = useMemo(() => latestByDate(vehicle?.vehicleInsurances, "startDate"), [vehicle])
  const latestInspection = useMemo(() => latestByDate(vehicle?.vehicleInspections, "inspectionDate"), [vehicle])
  const latestMaintenance = useMemo(() => latestByDate(vehicle?.maintenances?.filter((maintenance) => maintenance.finishedAt), "finishedAt"), [vehicle])

  if (isLoading) {
    return <div className="text-sm text-muted-foreground">Chargement du véhicule...</div>
  }

  if (error || !vehicle) {
    return <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{error ?? "Véhicule introuvable."}</div>
  }

  const canEdit = isAdmin || vehicle.user.id === currentUser?.id

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

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={isDeleteDialogOpen}
        title="Archiver le véhicule ?"
        description={`${displayVehicleName(vehicle)} sera masqué de la plateforme. Aucune donnée ne sera supprimée définitivement.`}
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => {
          if (!isDeleting) {
            setIsDeleteDialogOpen(open)
          }
        }}
        onConfirm={confirmDelete}
      />

      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div className="space-y-2">
          <h1 className="text-2xl font-semibold tracking-tight">{displayVehicleName(vehicle)}</h1>
          <div className="flex flex-wrap items-center gap-2">
            <VehicleBadge collection={VEHICLE_TYPES} value={vehicle.type} />
            <VehicleBadge collection={VEHICLE_STATUSES} value={vehicle.status} />
          </div>
          <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
            <span><strong className="text-foreground">Immat.</strong> {vehicle.registration.toUpperCase()}</span>
            {vehicle.year && <span><strong className="text-foreground">Année</strong> {vehicle.year}</span>}
            {vehicle.lastMileage !== null && vehicle.lastMileage !== undefined && (
              <span><strong className="text-foreground">Km</strong> {formatNumber(vehicle.lastMileage)}</span>
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
          <Button asChild disabled={!canEdit}>
            <Link to={canEdit ? `/vehicles/${vehicle.id}/edit` : `/vehicles/${vehicle.id}`}>
              <Pencil />
              Modifier
            </Link>
          </Button>
          {isAdmin && (
            <Button variant="destructive" onClick={() => setIsDeleteDialogOpen(true)}>
              <Trash2 />
              Supprimer
            </Button>
          )}
        </div>
      </div>

      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Informations générales</h2>
        <div className="grid gap-4 xl:grid-cols-2">
          <InfoCard title="Identité" rows={[
            ["Nom", capitalize(vehicle.name)],
            ["Marque", capitalize(vehicle.brand)],
            ["Modèle", capitalize(vehicle.model)],
            ["Immatriculation", vehicle.registration.toUpperCase()],
            ["Type", labelFor(VEHICLE_TYPES, vehicle.type)],
            ["Statut", labelFor(VEHICLE_STATUSES, vehicle.status)],
          ]} />
          <InfoCard title="Caractéristiques" rows={[
            ["Année", vehicle.year ?? "—"],
            ["VIN", vehicle.vin || "—"],
            ["Moteur", vehicle.engine || "—"],
            ["Carburant", labelFor(VEHICLE_FUEL_TYPES, vehicle.fuelType)],
            ["Transmission", labelFor(VEHICLE_TRANSMISSIONS, vehicle.transmission)],
            ["Couleur", labelFor(VEHICLE_COLORS, vehicle.color)],
          ]} />
        </div>
        <Card>
          <CardHeader>
            <CardTitle>Achat et suivi</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-3 md:grid-cols-4">
            <Metric label="Date d’achat" value={formatDate(vehicle.purchaseDate)} />
            <Metric label="Prix d’achat" value={vehicle.purchasePrice ? `${vehicle.purchasePrice} €` : "—"} />
            <Metric label="Dernier kilométrage" value={vehicle.lastMileage !== null && vehicle.lastMileage !== undefined ? `${formatNumber(vehicle.lastMileage)} km` : "—"} />
            <Metric label="Propriétaire" value={userLabel(vehicle.user)} />
          </CardContent>
        </Card>
      </section>

      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Assurance & Contrôle technique</h2>
        <div className="grid gap-4 xl:grid-cols-2">
          <InsuranceCard
            vehicleId={vehicle.id}
            insurance={latestInsurance}
            hasActiveInsurance={hasActiveInsurance(vehicle.vehicleInsurances)}
          />
          <InspectionCard vehicleId={vehicle.id} inspection={latestInspection} />
        </div>
      </section>

      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Interventions</h2>
        <MaintenanceCard vehicleId={vehicle.id} maintenance={latestMaintenance} canEdit={canEdit} />
      </section>
    </div>
  )
}

function InfoCard({ title, rows }: Readonly<{ title: string; rows: [string, string | number][] }>) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent>
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

function Metric({ label, value }: Readonly<{ label: string; value: string }>) {
  return (
    <div className="rounded-lg border p-3">
      <div className="text-xs text-muted-foreground">{label}</div>
      <div className="mt-1 font-medium">{value}</div>
    </div>
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
          <div className="mb-4 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-sm text-amber-700 dark:text-amber-300">
            Ce véhicule n’a aucune assurance active.
          </div>
        )}
        {insurance ? (
          <div className="grid gap-3 text-sm md:grid-cols-2">
            <Detail label="Assureur" value={insurance.providerName} />
            <Detail label="Police" value={insurance.policyNumber || "—"} />
            <Detail label="Statut" value={isInsuranceActive(insurance) ? "Active" : "Inactive"} />
            <Detail label="Début" value={formatDate(insurance.startDate)} />
            <Detail label="Fin" value={formatDate(insurance.endDate)} />
            <Detail label="Paiement" value={optionLabel(PAYMENT_FREQUENCIES, insurance.paymentFrequency)} />
          </div>
        ) : <EmptyText>Aucune assurance renseignée.</EmptyText>}
        <Button variant="outline" className="mt-4" asChild>
          <Link to={`/vehicles/${vehicleId}/insurances`}>Voir les assurances</Link>
        </Button>
      </CardContent>
    </Card>
  )
}

function hasActiveInsurance(insurances: VehicleInsurance[] | undefined) {
  return insurances?.some((insurance) => !insurance.isDeleted && isInsuranceActive(insurance)) ?? false
}

function InspectionCard({ vehicleId, inspection }: Readonly<{ vehicleId: number; inspection?: VehicleInspection }>) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center justify-between gap-2">
          Contrôle technique
          <Badge variant="outline">Dernier</Badge>
        </CardTitle>
      </CardHeader>
      <CardContent>
        {inspection ? (
          <div className="grid gap-3 text-sm md:grid-cols-2">
            <Detail label="Résultat" value={optionLabel(INSPECTION_RESULTS, inspection.result)} />
            <Detail label="Date" value={formatDate(inspection.inspectionDate)} />
            <Detail label="Valide jusqu’au" value={formatDate(inspection.validUntil)} />
            <Detail label="Kilométrage" value={inspection.mileage !== null && inspection.mileage !== undefined ? `${formatNumber(inspection.mileage)} km` : "—"} />
            <Detail label="Contre-visite" value={inspection.counterVisitRequired ? "Requise" : "Non requise"} />
            <Detail label="Centre" value={inspection.center?.name ?? "Non renseigné"} />
          </div>
        ) : <EmptyText>Aucun contrôle technique renseigné.</EmptyText>}
        <Button variant="outline" className="mt-4" asChild>
          <Link to={`/vehicles/${vehicleId}/inspections`}>Voir les contrôles</Link>
        </Button>
      </CardContent>
    </Card>
  )
}

function MaintenanceCard({ vehicleId, maintenance, canEdit }: Readonly<{ vehicleId: number; maintenance?: VehicleMaintenance; canEdit: boolean }>) {
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
            <Detail label="Type" value={maintenance.maintenanceType?.name ?? "—"} />
            <Detail label="Statut" value={maintenance.status ?? "—"} />
            <Detail label="Début" value={formatDate(maintenance.startedAt)} />
            <Detail label="Fin" value={formatDate(maintenance.finishedAt)} />
            <Detail label="Kilométrage" value={maintenance.mileage !== null && maintenance.mileage !== undefined ? `${formatNumber(maintenance.mileage)} km` : "—"} />
            <Detail label="Mode" value={maintenance.isExternal ? "Externe" : "Interne"} />
            <Detail label="Prochaine échéance km" value={maintenance.nextDueMileage !== null && maintenance.nextDueMileage !== undefined ? `${formatNumber(maintenance.nextDueMileage)} km` : "—"} />
            <Detail label="Prochaine échéance date" value={formatDate(maintenance.nextDueAt)} />
          </div>
        ) : <EmptyText>Aucune intervention réalisée.</EmptyText>}
        <div className="mt-4 flex flex-wrap gap-2">
          <Button variant="outline" asChild>
            <Link to={`/vehicles/${vehicleId}/interventions`}>Voir les interventions</Link>
          </Button>
          {canEdit && (
            <Button asChild>
              <Link to={`/vehicles/${vehicleId}/interventions/new`}>Ajouter une intervention</Link>
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

function VehicleBadge({ collection, value }: Readonly<{ collection: readonly { value: string; label: string; variant: string }[]; value?: string | null }>) {
  const option = vehicleOption(collection, value)

  if (!option) {
    return null
  }

  return <Badge variant={vehicleBadgeVariant(option.variant)}>{option.label}</Badge>
}

type DateLikeValue = string | null | undefined

type DateLikeKey<T> = {
  [K in keyof T]: T[K] extends DateLikeValue ? K : never
}[keyof T]

function latestByDate<T extends { isDeleted?: boolean }>(
  items: T[] | undefined,
  field: DateLikeKey<T>,
) {
  return items
    ?.filter((item) => !item.isDeleted && item[field])
    .toSorted((a, b) => {
      const aDate = a[field]
      const bDate = b[field]

      if (typeof aDate !== "string" || typeof bDate !== "string") {
        return 0
      }

      return bDate.localeCompare(aDate)
    })[0]
}

function labelFor(collection: readonly { value: string; label: string }[], value?: string | null) {
  return collection.find((item) => item.value === value)?.label ?? "—"
}

function displayVehicleName(vehicle: Vehicle) {
  return vehicle.name || `${vehicle.brand} ${vehicle.model}`.trim()
}

function userLabel(user: Vehicle["user"]) {
  const name = `${user.firstname ?? ""} ${user.lastname ?? ""}`.trim()

  return name || user.email
}

function capitalize(value: string) {
  return value ? value.charAt(0).toUpperCase() + value.slice(1) : "—"
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
