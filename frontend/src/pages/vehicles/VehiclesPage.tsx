import { useEffect, useMemo, useState } from "react"
import { Link } from "react-router-dom"
import { Plus, Search, Trash2, X } from "lucide-react"

import { deleteVehicle, getVehicles } from "@/api/vehicles"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { Input } from "@/components/ui/input"
import { NativeSelect } from "@/components/ui/native-select"
import { PaginationControls } from "@/components/ui/pagination-controls"
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
type ItemsPerPageValue = "6" | "12" | "24" | "all"

const ITEMS_PER_PAGE_OPTIONS = [
  { value: "6", label: "6" },
  { value: "12", label: "12" },
  { value: "24", label: "24" },
  { value: "all", label: "Tous" },
] as const

export default function VehiclesPage() {
  const currentUser = useAuthStore((state) => state.user)
  const isAdmin = currentUser?.roles.includes("ROLE_ADMIN") ?? false
  const [vehicles, setVehicles] = useState<Vehicle[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState("")
  const [typeFilter, setTypeFilter] = useState("all")
  const [statusFilter, setStatusFilter] = useState("all")
  const [editabilityFilter, setEditabilityFilter] = useState<EditabilityFilter>("all")
  const [sort, setSort] = useState<SortValue>("name")
  const [itemsPerPage, setItemsPerPage] = useLocalStorageState<ItemsPerPageValue>("vehicles.itemsPerPage", "6")
  const [page, setPage] = useState(1)
  const [vehicleToDelete, setVehicleToDelete] = useState<Vehicle | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  const filteredVehicles = useMemo(() => {
    const normalizedSearch = normalize(search)

    return vehicles
      .filter((vehicle) => {
        const canEdit = canEditVehicle(vehicle, currentUser?.id, isAdmin)
        const searchable = normalize([
          vehicle.name,
          vehicle.registration,
          vehicle.brand,
          vehicle.model,
          vehicle.year,
          vehicle.lastMileage,
          vehicle.user.email,
          vehicle.user.firstname,
          vehicle.user.lastname,
        ].filter(Boolean).join(" "))

        if (normalizedSearch && !searchable.includes(normalizedSearch)) {
          return false
        }

        if (typeFilter !== "all" && vehicle.type !== typeFilter) {
          return false
        }

        if (statusFilter !== "all" && vehicle.status !== statusFilter) {
          return false
        }

        if (editabilityFilter === "editable" && !canEdit) {
          return false
        }

        if (editabilityFilter === "readonly" && canEdit) {
          return false
        }

        return true
      })
      .sort((first, second) => compareVehicles(first, second, sort))
  }, [vehicles, search, typeFilter, statusFilter, editabilityFilter, sort, currentUser?.id, isAdmin])

  const pageSize = itemsPerPage === "all" ? filteredVehicles.length || 1 : Number(itemsPerPage)
  const pageCount = Math.max(1, Math.ceil(filteredVehicles.length / pageSize))
  const currentPage = Math.min(page, pageCount)
  const pageStart = (currentPage - 1) * pageSize
  const pageEnd = pageStart + pageSize
  const paginatedVehicles = itemsPerPage === "all" ? filteredVehicles : filteredVehicles.slice(pageStart, pageEnd)
  const visibleStart = filteredVehicles.length === 0 ? 0 : pageStart + 1
  const visibleEnd = itemsPerPage === "all" ? filteredVehicles.length : Math.min(pageEnd, filteredVehicles.length)
  const hasActiveFilters = search || typeFilter !== "all" || statusFilter !== "all" || editabilityFilter !== "all" || sort !== "name"

  useEffect(() => {
    setPage(1)
  }, [search, typeFilter, statusFilter, editabilityFilter, sort, itemsPerPage])

  useEffect(() => {
    if (page > pageCount) {
      setPage(pageCount)
    }
  }, [page, pageCount])

  useEffect(() => {
    let ignore = false

    async function loadVehicles() {
      try {
        const data = await getVehicles()

        if (!ignore) {
          setVehicles(data)
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
  }, [])

  async function confirmDelete() {
    if (!vehicleToDelete) {
      return
    }

    setIsDeleting(true)

    try {
      await deleteVehicle(vehicleToDelete.id)
      setVehicles((current) => current.filter((item) => item.id !== vehicleToDelete.id))
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

  function previousPage() {
    setPage((current) => Math.max(1, current - 1))
  }

  function nextPage() {
    setPage((current) => Math.min(pageCount, current + 1))
  }

  if (isLoading) {
    return <div className="text-sm text-muted-foreground">Chargement des véhicules...</div>
  }

  if (error) {
    return <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{error}</div>
  }

  function renderVehiclesContent() {
    if (vehicles.length === 0) {
      return (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">Aucun véhicule pour le moment.</CardContent>
        </Card>
      )
    }

    if (filteredVehicles.length === 0) {
      return (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">
            Aucun véhicule ne correspond à ces critères.
          </CardContent>
        </Card>
      )
    }

    return (
      <>
        <PaginationControls
          currentPage={currentPage}
          pageCount={pageCount}
          totalItems={filteredVehicles.length}
          visibleStart={visibleStart}
          visibleEnd={visibleEnd}
          itemsPerPage={itemsPerPage}
          itemsPerPageOptions={ITEMS_PER_PAGE_OPTIONS}
          onItemsPerPageChange={(value) => setItemsPerPage(value as ItemsPerPageValue)}
          onPreviousPage={previousPage}
          onNextPage={nextPage}
          itemLabel="véhicule(s)"
        />

        <div className="grid gap-4 xl:grid-cols-2">
          {paginatedVehicles.map((vehicle) => {
            const canEdit = canEditVehicle(vehicle, currentUser?.id, isAdmin)

            return (
              <Card
                key={vehicle.id}
                className="relative border border-foreground/10 ring-0 transition-colors hover:border-primary/35 hover:bg-muted/30"
              >
                <Link
                  to={`/vehicles/${vehicle.id}`}
                  className="absolute inset-0 z-10 rounded-xl focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
                  aria-label={`Voir ${displayVehicleName(vehicle)}`}
                />
                <CardHeader>
                  <CardTitle className="flex flex-wrap items-center gap-2">
                    <span>{displayVehicleName(vehicle)}</span>
                    <VehicleBadge collection={VEHICLE_TYPES} value={vehicle.type} />
                    <VehicleBadge collection={VEHICLE_STATUSES} value={vehicle.status} />
                    {!canEdit && (
                      <Badge
                        variant="outline"
                        className="border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300"
                      >
                        Lecture seule
                      </Badge>
                    )}
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                    <span><strong className="text-foreground">Immat.</strong> {vehicle.registration.toUpperCase()}</span>
                    {vehicle.year && <span><strong className="text-foreground">Année</strong> {vehicle.year}</span>}
                    {vehicle.lastMileage !== null && vehicle.lastMileage !== undefined && (
                      <span><strong className="text-foreground">Km</strong> {formatNumber(vehicle.lastMileage)}</span>
                    )}
                  </div>

                  <div className="flex flex-wrap gap-2">
                    <VehicleBadge collection={VEHICLE_FUEL_TYPES} value={vehicle.fuelType} />
                    <VehicleBadge collection={VEHICLE_TRANSMISSIONS} value={vehicle.transmission} />
                    <VehicleBadge collection={VEHICLE_COLORS} value={vehicle.color} />
                  </div>
                </CardContent>
                <CardFooter className="relative z-20 justify-end gap-2">
                  {canEdit && (
                    <Button variant="outline" size="sm" asChild>
                      <Link to={`/vehicles/${vehicle.id}/edit`}>Modifier</Link>
                    </Button>
                  )}
                  {isAdmin && (
                    <Button variant="destructive" size="sm" onClick={() => setVehicleToDelete(vehicle)}>
                      <Trash2 />
                      Supprimer
                    </Button>
                  )}
                </CardFooter>
              </Card>
            )
          })}
        </div>

        {pageCount > 1 && (
          <PaginationControls
            currentPage={currentPage}
            pageCount={pageCount}
            totalItems={filteredVehicles.length}
            visibleStart={visibleStart}
            visibleEnd={visibleEnd}
            itemsPerPage={itemsPerPage}
            itemsPerPageOptions={ITEMS_PER_PAGE_OPTIONS}
            onItemsPerPageChange={(value) => setItemsPerPage(value as ItemsPerPageValue)}
            onPreviousPage={previousPage}
            onNextPage={nextPage}
            itemLabel="véhicule(s)"
          />
        )}
      </>
    )
  }

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={vehicleToDelete !== null}
        title="Archiver le véhicule ?"
        description={vehicleToDelete ? `${displayVehicleName(vehicleToDelete)} sera masqué de la plateforme. Aucune donnée ne sera supprimée définitivement.` : ""}
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => {
          if (!open && !isDeleting) {
            setVehicleToDelete(null)
          }
        }}
        onConfirm={confirmDelete}
      />

      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Véhicules</h1>
          <p className="text-sm text-muted-foreground">
            {filteredVehicles.length} sur {vehicles.length} véhicule(s)
          </p>
        </div>

        <Button asChild>
          <Link to="/vehicles/new">
            <Plus />
            Ajouter un véhicule
          </Link>
        </Button>
      </div>

      <Card>
        <CardContent className="grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
          <label className="grid min-w-0 gap-1.5 text-sm font-medium sm:col-span-2 lg:col-span-3 xl:col-span-1" htmlFor="vehicle-search">
            <span>Recherche</span>
            <div className="relative min-w-0">
              <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                id="vehicle-search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Nom, immat., marque..."
                className="pl-8"
              />
            </div>
          </label>

          <NativeSelect
            label="Type"
            value={typeFilter}
            onChange={(event) => setTypeFilter(event.target.value)}
            options={[{ value: "all", label: "Tous" }, ...VEHICLE_TYPES]}
          />

          <NativeSelect
            label="Statut"
            value={statusFilter}
            onChange={(event) => setStatusFilter(event.target.value)}
            options={[{ value: "all", label: "Tous" }, ...VEHICLE_STATUSES]}
          />

          <NativeSelect
            label="Droit"
            value={editabilityFilter}
            onChange={(event) => setEditabilityFilter(event.target.value as EditabilityFilter)}
            options={[
              { value: "all", label: "Tous" },
              { value: "editable", label: "Modifiables" },
              { value: "readonly", label: "Lecture seule" },
            ]}
          />

          <NativeSelect
            label="Tri"
            value={sort}
            onChange={(event) => setSort(event.target.value as SortValue)}
            options={[
              { value: "name", label: "Nom A-Z" },
              { value: "registration", label: "Immat." },
              { value: "year-desc", label: "Année récente" },
              { value: "mileage-desc", label: "Km décroissant" },
            ]}
          />

          <div className="flex items-end">
            <Button variant="outline" className="w-full" onClick={resetFilters} disabled={!hasActiveFilters}>
              <X />
              Réinitialiser
            </Button>
          </div>
        </CardContent>
      </Card>

      {renderVehiclesContent()}
    </div>
  )
}

function VehicleBadge({ collection, value }: Readonly<{ collection: readonly { value: string; label: string; variant: string }[]; value?: string | null }>) {
  const option = vehicleOption(collection, value)

  if (!option) {
    return null
  }

  return <Badge variant={vehicleBadgeVariant(option.variant)}>{option.label}</Badge>
}

function displayVehicleName(vehicle: Vehicle) {
  return capitalizeFirstLetter(vehicle.name) || `${capitalizeFirstLetter(vehicle.brand)} ${capitalizeFirstLetter(vehicle.model)}`.trim()
}

function canEditVehicle(vehicle: Vehicle, currentUserId: number | undefined, isAdmin: boolean) {
  return isAdmin || vehicle.user.id === currentUserId
}

function compareVehicles(first: Vehicle, second: Vehicle, sort: SortValue) {
  if (sort === "registration") {
    return first.registration.localeCompare(second.registration, "fr")
  }

  if (sort === "year-desc") {
    return (second.year ?? 0) - (first.year ?? 0)
  }

  if (sort === "mileage-desc") {
    return (second.lastMileage ?? 0) - (first.lastMileage ?? 0)
  }

  return displayVehicleName(first).localeCompare(displayVehicleName(second), "fr")
}

function normalize(value: string) {
  return value
    .toLowerCase()
    .normalize("NFD")
    .replaceAll(/[\u0300-\u036f]/g, "")
}

function formatNumber(value: number) {
  return new Intl.NumberFormat("fr-FR").format(value)
}
