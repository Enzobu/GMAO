import { useEffect, useState } from "react"

import { emptyCollectionPage } from "@/api/api-collection"
import { deletePart, getPartsPage } from "@/api/parts"
import { getPartTypes } from "@/api/configuration"
import { getVehicles } from "@/api/vehicles"
import { FILTER_GRID_WIDE_CLASS } from "@/components/list-page-classes"
import {
  EmptyListCard,
  ListPageHeader,
  ResetFiltersButton,
  SearchField,
} from "@/components/list-page-primitives"
import { ListPagePlaceholder } from "@/components/loading-placeholders"
import { PaginatedListSection } from "@/components/paginated-list-section"
import {
  itemsPerPageSize,
  type ItemsPerPageValue,
} from "@/components/list-page-pagination"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { FilterSelect } from "@/components/ui/filter-select"
import { useLocalStorageState } from "@/hooks/use-local-storage-state"
import { StockTableRow } from "./StockTableRow"
import { AddStockDialog } from "./AddStockDialog"
import { useAuthStore } from "@/stores/auth-store"
import type { ConfigurationItem } from "@/types/configuration"
import type { Part } from "@/types/part"
import type { Vehicle } from "@/types/vehicle"
import {
  partName,
  vehicleDisplayName,
} from "@/lib/part-utils"

type SortValue = "name" | "quantity-asc" | "quantity-desc" | "updated-desc"
type StockFilter = "all" | "ok" | "low" | "out"

const DESTRUCTIVE_MESSAGE_CLASS =
  "rounded-lg border border-destructive/30 bg-destructive/10 p-4 " +
  "text-sm text-destructive"

export default function PartsPage() {
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false
  const [parts, setParts] = useState<Part[]>([])
  const [partsPage, setPartsPage] = useState(emptyCollectionPage<Part>(6))
  const [vehicles, setVehicles] = useState<Vehicle[]>([])
  const [partTypes, setPartTypes] = useState<ConfigurationItem[]>([])
  const [finishedRequest, setFinishedRequest] = useState<string | null>(null)
  const [loadError, setLoadError] = useState<{ request: string; message: string } | null>(null)
  const [search, setSearch] = useState("")
  const [debouncedSearch, setDebouncedSearch] = useState("")
  const [view, setView] = useState<"vehicle" | "all">("vehicle")
  const [vehicleFilter, setVehicleFilter] = useState("all")
  const [partTypeFilter, setPartTypeFilter] = useState("all")
  const [stockFilter, setStockFilter] = useState<StockFilter>("all")
  const [sort, setSort] = useState<SortValue>("quantity-asc")
  const [itemsPerPage, setItemsPerPage] =
    useLocalStorageState<ItemsPerPageValue>("parts.itemsPerPage", "6")
  const [page, setPage] = useState(1)
  const [partToDelete, setPartToDelete] = useState<Part | null>(null)
  const [partToStock, setPartToStock] = useState<Part | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  const requestKey = JSON.stringify([
    debouncedSearch,
    itemsPerPage,
    page,
    partTypeFilter,
    sort,
    stockFilter,
    vehicleFilter,
    view,
  ])
  const isLoading = finishedRequest !== requestKey
  const error = loadError?.request === requestKey ? loadError.message : null

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedSearch(search), 250)
    return () => clearTimeout(timer)
  }, [search])

  useEffect(() => {
    let ignore = false

    async function load() {
      try {
        const [partsData, vehiclesData, partTypesData] = await Promise.all([
          getPartsPage({
            page,
            itemsPerPage: itemsPerPageSize(itemsPerPage),
            search: debouncedSearch,
            vehicle: vehicleFilter,
            partType: partTypeFilter,
            stock: stockFilter,
            sort,
          }),
          getVehicles(),
          getPartTypes(),
        ])

        if (!ignore) {
          setParts(partsData.items)
          setPartsPage(partsData)
          setVehicles(vehiclesData)
          if (view === "vehicle" && vehicleFilter === "all" && vehiclesData[0]) {
            setVehicleFilter(String(vehiclesData[0].id))
          }
          setPartTypes(partTypesData)
          setLoadError(null)
        }
      } catch {
        if (!ignore) {
          setLoadError({ request: requestKey, message: "Impossible de charger le stock." })
        }
      } finally {
        if (!ignore) {
          setFinishedRequest(requestKey)
        }
      }
    }

    load()

    return () => {
      ignore = true
    }
  }, [debouncedSearch, itemsPerPage, page, partTypeFilter, requestKey, sort, stockFilter, vehicleFilter, view])

  const hasActiveFilters =
    search ||
    vehicleFilter !== "all" ||
    partTypeFilter !== "all" ||
    stockFilter !== "all" ||
    sort !== "quantity-asc"

  async function confirmDelete() {
    if (!partToDelete) {
      return
    }

    setIsDeleting(true)

    try {
      await deletePart(partToDelete.id)
      setParts((current) =>
        current.filter((part) => part.id !== partToDelete.id),
      )
      setPartsPage((current) => ({
        ...current,
        totalItems: Math.max(0, current.totalItems - 1),
      }))
      setPartToDelete(null)
    } finally {
      setIsDeleting(false)
    }
  }

  function resetFilters() {
    setSearch("")
    setVehicleFilter(
      view === "vehicle" && vehicles[0] ? String(vehicles[0].id) : "all",
    )
    setPartTypeFilter("all")
    setStockFilter("all")
    setSort("quantity-asc")
  }


  function renderPartsContent() {
    if (partsPage.totalItems === 0 && !hasActiveFilters) {
      return <EmptyListCard>Aucun stock enregistré.</EmptyListCard>
    }

    if (parts.length === 0) {
      return (
        <EmptyListCard>
          Aucune pièce ne correspond aux critères.
        </EmptyListCard>
      )
    }

    return (
      <PaginatedListSection
        itemLabel="ligne(s)"
        pagination={partsPage}
        itemsPerPage={itemsPerPage}
        onItemsPerPageChange={(value) => {
          setItemsPerPage(value)
          setPage(1)
        }}
        onPageChange={setPage}
      >
        <div className="overflow-x-auto rounded-lg border">
          <table className="w-full text-sm">
            <thead className="bg-muted/50 text-left">
              <tr className="border-b">
                <th className="p-3 font-medium">Pièce</th>
                <th className="p-3 font-medium">Véhicules</th>
                <th className="p-3 text-right font-medium">Quantité</th>
                <th className="p-3 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              {parts.map((part) => (
                <StockTableRow
                  key={part.id}
                  part={part}
                  isAdmin={isAdmin}
                  onAdd={() => setPartToStock(part)}
                  onDelete={() => setPartToDelete(part)}
                />
              ))}
            </tbody>
          </table>
        </div>
      </PaginatedListSection>
    )
  }

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={partToDelete !== null}
        title="Supprimer le stock ?"
        description={
          partToDelete
            ? `${partName(partToDelete)} sera masqué de la plateforme. ` +
              "Un stock sans historique sera supprimé définitivement."
            : ""
        }
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => {
          if (!open && !isDeleting) {
            setPartToDelete(null)
          }
        }}
        onConfirm={confirmDelete}
      />

      <AddStockDialog
        key={partToStock?.id ?? "stock"}
        part={partToStock}
        onOpenChange={(open) => !open && setPartToStock(null)}
        onSaved={(saved) => {
          setParts((current) =>
            current.map((part) => (part.id === saved.id ? saved : part)),
          )
          setPartToStock(null)
        }}
      />

      <ListPageHeader
        title="Stock"
        countLabel={`${partsPage.totalItems} ligne(s) de stock`}
        addTo={isAdmin ? "/parts/new" : undefined}
        addLabel="Ajouter un stock"
      />

      <div className="flex gap-2">
        <Button
          type="button"
          variant={view === "vehicle" ? "default" : "outline"}
          onClick={() => {
            setView("vehicle")
            setVehicleFilter(vehicles[0] ? String(vehicles[0].id) : "all")
            setPage(1)
          }}
        >
          Par véhicule
        </Button>
        <Button
          type="button"
          variant={view === "all" ? "default" : "outline"}
          onClick={() => {
            setView("all")
            setVehicleFilter("all")
            setPage(1)
          }}
        >
          Vue globale
        </Button>
      </div>

      <Card>
        <CardContent className={FILTER_GRID_WIDE_CLASS}>
          <SearchField
            id="part-search"
            value={search}
            placeholder="Pièce, note, véhicule..."
            onChange={(value) => updateFilter(setSearch, value, setPage)}
          />

          <FilterSelect
            label="Véhicule"
            value={vehicleFilter}
            onValueChange={(value) => {
              updateFilter(setVehicleFilter, value, setPage)
            }}
            options={[
              ...(view === "all" ? [{ value: "all", label: "Tous" }] : []),
              ...vehicles.map((vehicle) => ({
                value: String(vehicle.id),
                label: vehicleDisplayName(vehicle),
              })),
            ]}
          />
          <FilterSelect
            label="Type"
            value={partTypeFilter}
            onValueChange={(value) => {
              updateFilter(setPartTypeFilter, value, setPage)
            }}
            options={[
              { value: "all", label: "Tous" },
              ...partTypes.map((type) => ({
                value: String(type.id),
                label: type.name,
              })),
            ]}
          />
          <FilterSelect
            label="Stock"
            value={stockFilter}
            onValueChange={(value) => {
              updateFilter(setStockFilter, value as StockFilter, setPage)
            }}
            options={[
              { value: "all", label: "Tous" },
              { value: "ok", label: "OK" },
              { value: "low", label: "Stock faible" },
              { value: "out", label: "Rupture" },
            ]}
          />
          <FilterSelect
            label="Tri"
            value={sort}
            onValueChange={(value) => {
              updateFilter(setSort, value as SortValue, setPage)
            }}
            options={[
              { value: "quantity-asc", label: "Qté croissante" },
              { value: "quantity-desc", label: "Qté décroissante" },
              { value: "name", label: "Nom A-Z" },
              { value: "updated-desc", label: "Mise à jour" },
            ]}
          />

          <ResetFiltersButton
            disabled={!hasActiveFilters}
            onReset={resetFilters}
          />
        </CardContent>
      </Card>

      {error && <div className={DESTRUCTIVE_MESSAGE_CLASS}>{error}</div>}
      {isLoading ? <ListPagePlaceholder filters={0} /> : renderPartsContent()}
    </div>
  )
}

function updateFilter<T>(
  setter: (value: T) => void,
  value: T,
  setPage: (value: number) => void,
) {
  setter(value)
  setPage(1)
}
