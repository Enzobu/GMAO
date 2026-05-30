import { useEffect, useMemo, useState } from "react"
import { Link } from "react-router-dom"
import { Plus, Search, Trash2, X } from "lucide-react"

import { deletePart, getParts, updatePartQuantity } from "@/api/parts"
import { getPartTypes } from "@/api/configuration"
import { getVehicles } from "@/api/vehicles"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { NativeSelect } from "@/components/ui/native-select"
import { PaginationControls } from "@/components/ui/pagination-controls"
import { useLocalStorageState } from "@/hooks/use-local-storage-state"
import { useAuthStore } from "@/stores/auth-store"
import type { ConfigurationItem } from "@/types/configuration"
import type { Part } from "@/types/part"
import type { Vehicle } from "@/types/vehicle"
import { formatDateTime, partName, stockStatus, vehicleDisplayName } from "@/lib/part-utils"

type SortValue = "name" | "quantity-asc" | "quantity-desc" | "updated-desc"
type StockFilter = "all" | "ok" | "low" | "out"
type ItemsPerPageValue = "6" | "12" | "24" | "all"

const ITEMS_PER_PAGE_OPTIONS = [
  { value: "6", label: "6" },
  { value: "12", label: "12" },
  { value: "24", label: "24" },
  { value: "all", label: "Tous" },
] as const

export default function PartsPage() {
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false
  const [parts, setParts] = useState<Part[]>([])
  const [vehicles, setVehicles] = useState<Vehicle[]>([])
  const [partTypes, setPartTypes] = useState<ConfigurationItem[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState("")
  const [vehicleFilter, setVehicleFilter] = useState("all")
  const [partTypeFilter, setPartTypeFilter] = useState("all")
  const [stockFilter, setStockFilter] = useState<StockFilter>("all")
  const [sort, setSort] = useState<SortValue>("quantity-asc")
  const [itemsPerPage, setItemsPerPage] = useLocalStorageState<ItemsPerPageValue>("parts.itemsPerPage", "6")
  const [page, setPage] = useState(1)
  const [partToDelete, setPartToDelete] = useState<Part | null>(null)
  const [partToStock, setPartToStock] = useState<Part | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  useEffect(() => {
    let ignore = false

    async function load() {
      try {
        const [partsData, vehiclesData, partTypesData] = await Promise.all([
          getParts(),
          getVehicles(),
          getPartTypes(),
        ])

        if (!ignore) {
          setParts(partsData)
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
  }, [])

  const filteredParts = useMemo(() => {
    const normalizedSearch = normalize(search)

    return parts
      .filter((part) => {
        const searchable = normalize([
          partName(part),
          part.note,
          ...part.vehicles.map(vehicleDisplayName),
          ...part.vehicles.map((vehicle) => vehicle.registration),
        ].filter(Boolean).join(" "))

        if (normalizedSearch && !searchable.includes(normalizedSearch)) {
          return false
        }

        if (vehicleFilter !== "all" && !part.vehicles.some((vehicle) => String(vehicle.id) === vehicleFilter)) {
          return false
        }

        if (partTypeFilter !== "all" && String(part.partType.id) !== partTypeFilter) {
          return false
        }

        if (stockFilter !== "all" && stockStatus(part.quantity).value !== stockFilter) {
          return false
        }

        return true
      })
      .sort((first, second) => compareParts(first, second, sort))
  }, [parts, search, vehicleFilter, partTypeFilter, stockFilter, sort])

  const pageSize = itemsPerPage === "all" ? filteredParts.length || 1 : Number(itemsPerPage)
  const pageCount = Math.max(1, Math.ceil(filteredParts.length / pageSize))
  const currentPage = Math.min(page, pageCount)
  const pageStart = (currentPage - 1) * pageSize
  const pageEnd = pageStart + pageSize
  const paginatedParts = itemsPerPage === "all" ? filteredParts : filteredParts.slice(pageStart, pageEnd)
  const visibleStart = filteredParts.length === 0 ? 0 : pageStart + 1
  const visibleEnd = itemsPerPage === "all" ? filteredParts.length : Math.min(pageEnd, filteredParts.length)
  const hasActiveFilters = search || vehicleFilter !== "all" || partTypeFilter !== "all" || stockFilter !== "all" || sort !== "quantity-asc"

  useEffect(() => {
    setPage(1)
  }, [search, vehicleFilter, partTypeFilter, stockFilter, sort, itemsPerPage])

  async function confirmDelete() {
    if (!partToDelete) {
      return
    }

    setIsDeleting(true)

    try {
      await deletePart(partToDelete.id)
      setParts((current) => current.filter((part) => part.id !== partToDelete.id))
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
    return <div className="text-sm text-muted-foreground">Chargement du stock...</div>
  }

  if (error) {
    return <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{error}</div>
  }

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={partToDelete !== null}
        title="Supprimer le stock ?"
        description={partToDelete ? `${partName(partToDelete)} sera masqué de la plateforme. Aucune donnée ne sera supprimée définitivement.` : ""}
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
        part={partToStock}
        onOpenChange={(open) => !open && setPartToStock(null)}
        onSaved={(saved) => {
          setParts((current) => current.map((part) => part.id === saved.id ? saved : part))
          setPartToStock(null)
        }}
      />

      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Stock</h1>
          <p className="text-sm text-muted-foreground">{filteredParts.length} sur {parts.length} ligne(s) de stock</p>
        </div>

        {isAdmin && (
          <Button asChild>
            <Link to="/parts/new">
              <Plus />
              Ajouter un stock
            </Link>
          </Button>
        )}
      </div>

      <Card>
        <CardContent className="grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
          <label className="grid min-w-0 gap-1.5 text-sm font-medium sm:col-span-2 lg:col-span-3 xl:col-span-1" htmlFor="part-search">
            <span>Recherche</span>
            <div className="relative min-w-0">
              <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Pièce, note, véhicule..." className="pl-8" id="part-search" />
            </div>
          </label>

          <NativeSelect label="Véhicule" value={vehicleFilter} onChange={(event) => setVehicleFilter(event.target.value)} options={[{ value: "all", label: "Tous" }, ...vehicles.map((vehicle) => ({ value: String(vehicle.id), label: vehicleDisplayName(vehicle) }))]} />
          <NativeSelect label="Type" value={partTypeFilter} onChange={(event) => setPartTypeFilter(event.target.value)} options={[{ value: "all", label: "Tous" }, ...partTypes.map((type) => ({ value: String(type.id), label: type.name }))]} />
          <NativeSelect label="Stock" value={stockFilter} onChange={(event) => setStockFilter(event.target.value as StockFilter)} options={[{ value: "all", label: "Tous" }, { value: "ok", label: "OK" }, { value: "low", label: "Stock faible" }, { value: "out", label: "Rupture" }]} />
          <NativeSelect label="Tri" value={sort} onChange={(event) => setSort(event.target.value as SortValue)} options={[{ value: "quantity-asc", label: "Qté croissante" }, { value: "quantity-desc", label: "Qté décroissante" }, { value: "name", label: "Nom A-Z" }, { value: "updated-desc", label: "Mise à jour" }]} />

          <div className="flex items-end">
            <Button variant="outline" className="w-full" onClick={resetFilters} disabled={!hasActiveFilters}>
              <X />
              Réinitialiser
            </Button>
          </div>
        </CardContent>
      </Card>

      {parts.length === 0 ? (
        <Card><CardContent className="py-8 text-center text-sm text-muted-foreground">Aucun stock enregistré.</CardContent></Card>
      ) : filteredParts.length === 0 ? (
        <Card><CardContent className="py-8 text-center text-sm text-muted-foreground">Aucune pièce ne correspond aux critères.</CardContent></Card>
      ) : (
        <>
          <PaginationControls currentPage={currentPage} pageCount={pageCount} totalItems={filteredParts.length} visibleStart={visibleStart} visibleEnd={visibleEnd} itemsPerPage={itemsPerPage} itemsPerPageOptions={ITEMS_PER_PAGE_OPTIONS} onItemsPerPageChange={(value) => setItemsPerPage(value as ItemsPerPageValue)} onPreviousPage={() => setPage((current) => Math.max(1, current - 1))} onNextPage={() => setPage((current) => Math.min(pageCount, current + 1))} itemLabel="ligne(s)" />

          <div className="grid gap-4 xl:grid-cols-2">
            {paginatedParts.map((part) => {
              const status = stockStatus(part.quantity)

              return (
                <Card key={part.id} className="relative flex h-full flex-col border border-foreground/10 ring-0 transition-colors hover:border-primary/35 hover:bg-muted/30">
                  <Link to={`/parts/${part.id}`} className="absolute inset-0 z-10 rounded-xl focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-ring/50" aria-label={`Voir ${partName(part)}`} />
                  <CardHeader>
                    <CardTitle className="flex flex-wrap items-center gap-2">
                      <span>{partName(part)}</span>
                      <Badge variant={status.variant} className={status.value === "low" ? "border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300" : undefined}>{status.label}</Badge>
                      {part.vehicles.length === 0 && <Badge variant="destructive">Aucun véhicule compatible</Badge>}
                      {!isAdmin && <Badge variant="outline" className="border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300">Lecture seule</Badge>}
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="flex-1 space-y-3">
                    <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                      <span><strong className="text-foreground">Quantité</strong> {part.quantity}</span>
                      <span><strong className="text-foreground">Màj</strong> {formatDateTime(part.updatedAt)}</span>
                    </div>
                    {part.note && <p className="line-clamp-2 text-sm text-muted-foreground">{part.note}</p>}
                    <div className="flex flex-wrap gap-2">
                      {part.vehicles.length > 0 ? part.vehicles.map((vehicle) => <Badge key={vehicle.id} variant="outline">{vehicleDisplayName(vehicle)}</Badge>) : <span className="text-sm text-destructive">Cette pièce ne pourra être utilisée sur aucune intervention.</span>}
                    </div>
                  </CardContent>
                  {isAdmin && (
                    <CardFooter className="relative z-20 justify-end gap-2">
                      <Button variant="outline" size="sm" onClick={() => setPartToStock(part)}>Ajouter stock</Button>
                      <Button variant="outline" size="sm" asChild><Link to={`/parts/${part.id}/edit`}>Modifier</Link></Button>
                      <Button variant="destructive" size="sm" onClick={() => setPartToDelete(part)}><Trash2 />Supprimer</Button>
                    </CardFooter>
                  )}
                </Card>
              )
            })}
          </div>

          {pageCount > 1 && <PaginationControls currentPage={currentPage} pageCount={pageCount} totalItems={filteredParts.length} visibleStart={visibleStart} visibleEnd={visibleEnd} itemsPerPage={itemsPerPage} itemsPerPageOptions={ITEMS_PER_PAGE_OPTIONS} onItemsPerPageChange={(value) => setItemsPerPage(value as ItemsPerPageValue)} onPreviousPage={() => setPage((current) => Math.max(1, current - 1))} onNextPage={() => setPage((current) => Math.min(pageCount, current + 1))} itemLabel="ligne(s)" />}
        </>
      )}
    </div>
  )
}

function AddStockDialog({ part, onOpenChange, onSaved }: Readonly<{ part: Part | null; onOpenChange: (open: boolean) => void; onSaved: (part: Part) => void }>) {
  const [quantity, setQuantity] = useState("1")
  const [isSaving, setIsSaving] = useState(false)

  useEffect(() => {
    if (part) {
      setQuantity("1")
    }
  }, [part])

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
      const saved = await updatePartQuantity(part.id, part.quantity + quantityToAdd)
      onSaved(saved)
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <Dialog open={part !== null} onOpenChange={(open) => !isSaving && onOpenChange(open)}>
      <DialogContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <DialogHeader>
            <DialogTitle>Ajouter du stock</DialogTitle>
            <DialogDescription>{part ? `Stock actuel ${partName(part)} : ${part.quantity}` : ""}</DialogDescription>
          </DialogHeader>
          <label className="grid gap-1.5 text-sm font-medium">
            <span>Nombre de pièces à ajouter</span>
            <Input type="number" min="1" step="1" value={quantity} onChange={(event) => setQuantity(event.target.value)} required />
          </label>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={isSaving}>Annuler</Button>
            <Button type="submit" disabled={isSaving}>{isSaving ? "Ajout..." : "Ajouter"}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

function compareParts(first: Part, second: Part, sort: SortValue) {
  if (sort === "quantity-asc") return first.quantity - second.quantity
  if (sort === "quantity-desc") return second.quantity - first.quantity
  if (sort === "updated-desc") return String(second.updatedAt ?? "").localeCompare(String(first.updatedAt ?? ""))
  return partName(first).localeCompare(partName(second), "fr")
}

function normalize(value: string) {
  return value.toLowerCase().normalize("NFD").replaceAll(/[\u0300-\u036f]/g, "")
}
