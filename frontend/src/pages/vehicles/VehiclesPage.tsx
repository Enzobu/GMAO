import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { Trash2 } from "lucide-react"

import { emptyCollectionPage } from "@/api/api-collection"
import { deleteVehicle, getVehiclesPage } from "@/api/vehicles"
import {
  CARD_LINK_CLASS,
  FILTER_GRID_CLASS,
  RESOURCE_CARD_CLASS,
  RESOURCE_META_CLASS,
} from "@/components/list-page-classes"
import {
  EmptyListCard,
  ListPageHeader,
  ReadOnlyBadge,
  ResetFiltersButton,
  SearchField,
} from "@/components/list-page-primitives"
import {
  itemsPerPageSize,
  type ItemsPerPageValue,
} from "@/components/list-page-pagination"
import { ListPaginationControls } from "@/components/list-pagination-controls"
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
import { NativeSelect } from "@/components/ui/native-select"
import { useLocalStorageState } from "@/hooks/use-local-storage-state"
import { useAuthStore } from "@/stores/auth-store"
import type { Vehicle } from "@/types/vehicle"
import { capitalizeFirstLetter } from "@/lib/text-format"
import {
  VEHICLE_COLORS,
  VEHICLE_FUEL_TYPES,
  VEHICLE_STATUSES,
  VEHICLE_TRANSMISSIONS,
  VEHICLE_TYPES,
  vehicleBadgeVariant,
  vehicleOption,
} from "@/lib/vehicle-labels"

type SortValue = "name" | "registration" | "year-desc" | "mileage-desc"
type EditabilityFilter = "all" | "editable" | "readonly"

const ALERT_CLASS = [
  "rounded-lg border border-destructive/30 bg-destructive/10 p-4",
  "text-sm text-destructive",
].join(" ")

export default function VehiclesPage() {
  const currentUser = useAuthStore((state) => state.user)
  const isAdmin = currentUser?.roles.includes("ROLE_ADMIN") ?? false
  const [vehicles, setVehicles] = useState<Vehicle[]>([])
  const [vehiclesPage, setVehiclesPage] = useState(
    emptyCollectionPage<Vehicle>(6),
  )
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState("")
  const [typeFilter, setTypeFilter] = useState("all")
  const [statusFilter, setStatusFilter] = useState("all")
  const [editabilityFilter, setEditabilityFilter] =
    useState<EditabilityFilter>("all")
  const [sort, setSort] = useState<SortValue>("name")
  const [itemsPerPage, setItemsPerPage] =
    useLocalStorageState<ItemsPerPageValue>("vehicles.itemsPerPage", "6")
  const [page, setPage] = useState(1)
  const [vehicleToDelete, setVehicleToDelete] = useState<Vehicle | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  const hasActiveFilters =
    search ||
    typeFilter !== "all" ||
    statusFilter !== "all" ||
    editabilityFilter !== "all" ||
    sort !== "name"

  useEffect(() => {
    let ignore = false

    async function loadVehicles() {
      try {
        const data = await getVehiclesPage({
          page,
          itemsPerPage: itemsPerPageSize(itemsPerPage),
          search,
          type: typeFilter,
          status: statusFilter,
          editability: editabilityFilter,
          sort,
        })

        if (!ignore) {
          setVehicles(data.items)
          setVehiclesPage(data)
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger les véhicules.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    loadVehicles()

    return () => {
      ignore = true
    }
  }, [editabilityFilter, itemsPerPage, page, search, sort, statusFilter, typeFilter])

  async function confirmDelete() {
    if (!vehicleToDelete) {
      return
    }

    setIsDeleting(true)

    try {
      await deleteVehicle(vehicleToDelete.id)
      setVehicles((current) => current.filter(
        (item) => item.id !== vehicleToDelete.id,
      ))
      setVehiclesPage((current) => ({
        ...current,
        totalItems: Math.max(0, current.totalItems - 1),
      }))
      setVehicleToDelete(null)
    } finally {
      setIsDeleting(false)
    }
  }

  function resetFilters() {
    setSearch("")
    setTypeFilter("all")
    setStatusFilter("all")
    setEditabilityFilter("all")
    setSort("name")
  }

  if (isLoading) {
    return (
      <div className="text-sm text-muted-foreground">
        Chargement des véhicules...
      </div>
    )
  }

  if (error) {
    return <div className={ALERT_CLASS}>{error}</div>
  }

  const deleteDialogDescription = vehicleToDelete
    ? [
        `${displayVehicleName(vehicleToDelete)} sera masqué de la plateforme.`,
        "Aucune donnée ne sera supprimée définitivement.",
      ].join(" ")
    : ""

  function renderVehiclesContent() {
    if (vehiclesPage.totalItems === 0 && !hasActiveFilters) {
      return <EmptyListCard>Aucun véhicule pour le moment.</EmptyListCard>
    }

    if (vehicles.length === 0) {
      return (
        <EmptyListCard>
          Aucun véhicule ne correspond à ces critères.
        </EmptyListCard>
      )
    }

    return (
      <>
        <ListPaginationControls
          itemLabel="véhicule(s)"
          pagination={vehiclesPage}
          itemsPerPage={itemsPerPage}
          onItemsPerPageChange={(value) => {
            setItemsPerPage(value)
            setPage(1)
          }}
          onPageChange={setPage}
        />

        <div className="grid gap-4 xl:grid-cols-2">
          {vehicles.map((vehicle) => {
            const canEdit = canEditVehicle(vehicle, currentUser?.id, isAdmin)

            return (
              <Card key={vehicle.id} className={RESOURCE_CARD_CLASS}>
                <Link
                  to={`/vehicles/${vehicle.id}`}
                  className={CARD_LINK_CLASS}
                  aria-label={`Voir ${displayVehicleName(vehicle)}`}
                />
                <CardHeader>
                  <CardTitle className="flex flex-wrap items-center gap-2">
                    <span>{displayVehicleName(vehicle)}</span>
                    <VehicleBadge
                      collection={VEHICLE_TYPES}
                      value={vehicle.type}
                    />
                    <VehicleBadge
                      collection={VEHICLE_STATUSES}
                      value={vehicle.status}
                    />
                    {!canEdit && <ReadOnlyBadge />}
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div className={RESOURCE_META_CLASS}>
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

                  <div className="flex flex-wrap gap-2">
                    <VehicleBadge
                      collection={VEHICLE_FUEL_TYPES}
                      value={vehicle.fuelType}
                    />
                    <VehicleBadge
                      collection={VEHICLE_TRANSMISSIONS}
                      value={vehicle.transmission}
                    />
                    <VehicleBadge
                      collection={VEHICLE_COLORS}
                      value={vehicle.color}
                    />
                  </div>
                </CardContent>
                <CardFooter className="relative z-20 justify-end gap-2">
                  {canEdit && (
                    <Button variant="outline" size="sm" asChild>
                      <Link to={`/vehicles/${vehicle.id}/edit`}>Modifier</Link>
                    </Button>
                  )}
                  {isAdmin && (
                    <Button
                      variant="destructive"
                      size="sm"
                      onClick={() => setVehicleToDelete(vehicle)}
                    >
                      <Trash2 />
                      Supprimer
                    </Button>
                  )}
                </CardFooter>
              </Card>
            )
          })}
        </div>

        <ListPaginationControls
          itemLabel="véhicule(s)"
          pagination={vehiclesPage}
          itemsPerPage={itemsPerPage}
          onItemsPerPageChange={(value) => {
            setItemsPerPage(value)
            setPage(1)
          }}
          onPageChange={setPage}
        />
      </>
    )
  }

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={vehicleToDelete !== null}
        title="Archiver le véhicule ?"
        description={deleteDialogDescription}
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => {
          if (!open && !isDeleting) {
            setVehicleToDelete(null)
          }
        }}
        onConfirm={confirmDelete}
      />

      <ListPageHeader
        title="Véhicules"
        countLabel={`${vehiclesPage.totalItems} véhicule(s)`}
        addTo="/vehicles/new"
        addLabel="Ajouter un véhicule"
      />

      <Card>
        <CardContent className={FILTER_GRID_CLASS}>
          <SearchField
            id="vehicle-search"
            value={search}
            placeholder="Nom, immat., marque..."
            onChange={(value) => updateFilter(setSearch, value, setPage)}
          />

          <NativeSelect
            label="Type"
            value={typeFilter}
            onChange={(event) => {
              updateFilter(setTypeFilter, event.target.value, setPage)
            }}
            options={[{ value: "all", label: "Tous" }, ...VEHICLE_TYPES]}
          />

          <NativeSelect
            label="Statut"
            value={statusFilter}
            onChange={(event) => {
              updateFilter(setStatusFilter, event.target.value, setPage)
            }}
            options={[{ value: "all", label: "Tous" }, ...VEHICLE_STATUSES]}
          />

          <NativeSelect
            label="Droit"
            value={editabilityFilter}
            onChange={(event) => {
              updateFilter(
                setEditabilityFilter,
                event.target.value as EditabilityFilter,
                setPage,
              )
            }}
            options={[
              { value: "all", label: "Tous" },
              { value: "editable", label: "Modifiables" },
              { value: "readonly", label: "Lecture seule" },
            ]}
          />

          <NativeSelect
            label="Tri"
            value={sort}
            onChange={(event) => {
              updateFilter(setSort, event.target.value as SortValue, setPage)
            }}
            options={[
              { value: "name", label: "Nom A-Z" },
              { value: "registration", label: "Immat." },
              { value: "year-desc", label: "Année récente" },
              { value: "mileage-desc", label: "Km décroissant" },
            ]}
          />

          <ResetFiltersButton
            disabled={!hasActiveFilters}
            onReset={resetFilters}
          />
        </CardContent>
      </Card>

      {renderVehiclesContent()}
    </div>
  )
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

function displayVehicleName(vehicle: Vehicle) {
  return (
    capitalizeFirstLetter(vehicle.name) ||
    [capitalizeFirstLetter(vehicle.brand), capitalizeFirstLetter(vehicle.model)]
      .join(" ")
      .trim()
  )
}

function canEditVehicle(
  vehicle: Vehicle,
  currentUserId: number | undefined,
  isAdmin: boolean,
) {
  return isAdmin || vehicle.user.id === currentUserId
}

function formatNumber(value: number) {
  return new Intl.NumberFormat("fr-FR").format(value)
}

function updateFilter<T>(
  setter: (value: T) => void,
  value: T,
  setPage: (value: number) => void,
) {
  setter(value)
  setPage(1)
}
