import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { Trash2 } from "lucide-react"

import { emptyCollectionPage } from "@/api/api-collection"
import { deletePart, getPartsPage, updatePartQuantity } from "@/api/parts"
import { getPartTypes } from "@/api/configuration"
import { getVehicles } from "@/api/vehicles"
import {
  CARD_LINK_CLASS,
  FILTER_GRID_WIDE_CLASS,
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
import { ListPagePlaceholder } from "@/components/loading-placeholders"
import { PaginatedListSection } from "@/components/paginated-list-section"
import {
  itemsPerPageSize,
  type ItemsPerPageValue,
} from "@/components/list-page-pagination"
import { LabelText } from "@/components/page-primitives"
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
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { NativeSelect } from "@/components/ui/native-select"
import { useLocalStorageState } from "@/hooks/use-local-storage-state"
import { useAuthStore } from "@/stores/auth-store"
import type { ConfigurationItem } from "@/types/configuration"
import type { Part } from "@/types/part"
import type { Vehicle } from "@/types/vehicle"
import {
  formatDateTime,
  partName,
  stockStatus,
  vehicleDisplayName,
} from "@/lib/part-utils"

type SortValue = "name" | "quantity-asc" | "quantity-desc" | "updated-desc"
type StockFilter = "all" | "ok" | "low" | "out"

const LOW_STOCK_BADGE_CLASS =
  "border-amber-500/30 bg-amber-500/10 text-amber-700 " +
  "dark:text-amber-300"
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
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState("")
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

  useEffect(() => {
    let ignore = false

    async function load() {
      try {
        const [partsData, vehiclesData, partTypesData] = await Promise.all([
          getPartsPage({
            page,
            itemsPerPage: itemsPerPageSize(itemsPerPage),
            search,
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
          setPartTypes(partTypesData)
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger le stock.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    load()

    return () => {
      ignore = true
    }
  }, [itemsPerPage, page, partTypeFilter, search, sort, stockFilter, vehicleFilter])

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
    setVehicleFilter("all")
    setPartTypeFilter("all")
    setStockFilter("all")
    setSort("quantity-asc")
  }

  if (isLoading) {
    return <ListPagePlaceholder filters={5} />
  }

  if (error) {
    return (
      <div className={DESTRUCTIVE_MESSAGE_CLASS}>
        {error}
      </div>
    )
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
        <div className="grid gap-4 xl:grid-cols-2">
          {parts.map((part) => {
            const status = stockStatus(part.quantity)

            return (
              <Card
                key={part.id}
                className={`${RESOURCE_CARD_CLASS} flex h-full flex-col`}
              >
                <Link
                  to={`/parts/${part.id}`}
                  className={CARD_LINK_CLASS}
                  aria-label={`Voir ${partName(part)}`}
                />
                <CardHeader>
                  <CardTitle className="flex flex-wrap items-center gap-2">
                    <span>{partName(part)}</span>
                    <Badge
                      variant={status.variant}
                      className={
                        status.value === "low"
                          ? LOW_STOCK_BADGE_CLASS
                          : undefined
                      }
                    >
                      {status.label}
                    </Badge>
                    {part.vehicles.length === 0 && (
                      <Badge variant="destructive">
                        Aucun véhicule compatible
                      </Badge>
                    )}
                    {!isAdmin && <ReadOnlyBadge />}
                  </CardTitle>
                </CardHeader>
                <CardContent className="flex-1 space-y-3">
                  <div className={RESOURCE_META_CLASS}>
                    <span>
                      <strong className="text-foreground">Quantité</strong>
                      {" "}
                      {part.quantity}
                    </span>
                    <span>
                      <strong className="text-foreground">Màj</strong>{" "}
                      {formatDateTime(part.updatedAt)}
                    </span>
                  </div>
                  {part.note && (
                    <p className="line-clamp-2 text-sm text-muted-foreground">
                      {part.note}
                    </p>
                  )}
                  <div className="flex flex-wrap gap-2">
                    {part.vehicles.length > 0 ? (
                      part.vehicles.map((vehicle) => (
                        <Badge key={vehicle.id} variant="outline">
                          {vehicleDisplayName(vehicle)}
                        </Badge>
                      ))
                    ) : (
                      <span className="text-sm text-destructive">
                        Cette pièce ne pourra être utilisée sur aucune
                        intervention.
                      </span>
                    )}
                  </div>
                </CardContent>
                {isAdmin && (
                  <CardFooter className="relative z-20 justify-end gap-2">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => setPartToStock(part)}
                    >
                      Ajouter stock
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                      <Link to={`/parts/${part.id}/edit`}>Modifier</Link>
                    </Button>
                    <Button
                      variant="destructive"
                      size="sm"
                      onClick={() => setPartToDelete(part)}
                    >
                      <Trash2 />
                      Supprimer
                    </Button>
                  </CardFooter>
                )}
              </Card>
            )
          })}
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
              "Aucune donnée ne sera supprimée définitivement."
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

      <Card>
        <CardContent className={FILTER_GRID_WIDE_CLASS}>
          <SearchField
            id="part-search"
            value={search}
            placeholder="Pièce, note, véhicule..."
            onChange={(value) => updateFilter(setSearch, value, setPage)}
          />

          <NativeSelect
            label="Véhicule"
            value={vehicleFilter}
            onChange={(event) => {
              updateFilter(setVehicleFilter, event.target.value, setPage)
            }}
            options={[
              { value: "all", label: "Tous" },
              ...vehicles.map((vehicle) => ({
                value: String(vehicle.id),
                label: vehicleDisplayName(vehicle),
              })),
            ]}
          />
          <NativeSelect
            label="Type"
            value={partTypeFilter}
            onChange={(event) => {
              updateFilter(setPartTypeFilter, event.target.value, setPage)
            }}
            options={[
              { value: "all", label: "Tous" },
              ...partTypes.map((type) => ({
                value: String(type.id),
                label: type.name,
              })),
            ]}
          />
          <NativeSelect
            label="Stock"
            value={stockFilter}
            onChange={(event) => {
              updateFilter(
                setStockFilter,
                event.target.value as StockFilter,
                setPage,
              )
            }}
            options={[
              { value: "all", label: "Tous" },
              { value: "ok", label: "OK" },
              { value: "low", label: "Stock faible" },
              { value: "out", label: "Rupture" },
            ]}
          />
          <NativeSelect
            label="Tri"
            value={sort}
            onChange={(event) => {
              updateFilter(setSort, event.target.value as SortValue, setPage)
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

      {renderPartsContent()}
    </div>
  )
}

function AddStockDialog({
  part,
  onOpenChange,
  onSaved,
}: Readonly<{
  part: Part | null
  onOpenChange: (open: boolean) => void
  onSaved: (part: Part) => void
}>) {
  const [quantity, setQuantity] = useState("1")
  const [isSaving, setIsSaving] = useState(false)

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!part) {
      return
    }

    const quantityToAdd = Number(quantity)

    if (!Number.isFinite(quantityToAdd) || quantityToAdd <= 0) {
      return
    }

    setIsSaving(true)

    try {
      const saved = await updatePartQuantity(
        part.id,
        part.quantity + quantityToAdd,
      )
      onSaved(saved)
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <Dialog
      open={part !== null}
      onOpenChange={(open) => !isSaving && onOpenChange(open)}
    >
      <DialogContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <DialogHeader>
            <DialogTitle>Ajouter du stock</DialogTitle>
            <DialogDescription>
              {part ? `Stock actuel ${partName(part)} : ${part.quantity}` : ""}
            </DialogDescription>
          </DialogHeader>
          <label className="grid gap-1.5 text-sm font-medium">
            <LabelText label="Nombre de pièces à ajouter" required />
            <Input
              type="number"
              min="1"
              step="1"
              value={quantity}
              onChange={(event) => setQuantity(event.target.value)}
              required
            />
          </label>
          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={isSaving}
            >
              Annuler
            </Button>
            <Button type="submit" disabled={isSaving}>
              {isSaving ? "Ajout..." : "Ajouter"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
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
