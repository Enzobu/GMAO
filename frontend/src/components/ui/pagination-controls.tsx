import { Button } from "@/components/ui/button"
import { NativeSelect } from "@/components/ui/native-select"

export type PaginationControlsProps = Readonly<{
  currentPage: number
  pageCount: number
  totalItems: number
  visibleStart: number
  visibleEnd: number
  itemsPerPage: string
  itemsPerPageOptions: readonly { value: string; label: string }[]
  onItemsPerPageChange: (value: string) => void
  onPreviousPage: () => void
  onNextPage: () => void
  itemLabel?: string
}>

function PaginationControls({
  currentPage,
  pageCount,
  totalItems,
  visibleStart,
  visibleEnd,
  itemsPerPage,
  itemsPerPageOptions,
  onItemsPerPageChange,
  onPreviousPage,
  onNextPage,
  itemLabel = "élément(s)",
}: PaginationControlsProps) {
  return (
    <div className="flex flex-col gap-3 text-sm text-muted-foreground lg:flex-row lg:items-center lg:justify-between">
      <span>
        Affichage {visibleStart}-{visibleEnd} sur {totalItems} {itemLabel}
      </span>

      <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
        <label className="flex items-center gap-2 whitespace-nowrap">
          <span>Par page</span>
          <NativeSelect
            value={itemsPerPage}
            onChange={(event) => onItemsPerPageChange(event.target.value)}
            options={itemsPerPageOptions}
            className="w-24"
          />
        </label>

        {itemsPerPage !== "all" && (
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" onClick={onPreviousPage} disabled={currentPage === 1}>
              Précédent
            </Button>
            <span className="whitespace-nowrap">Page {currentPage} / {pageCount}</span>
            <Button variant="outline" size="sm" onClick={onNextPage} disabled={currentPage === pageCount}>
              Suivant
            </Button>
          </div>
        )}
      </div>
    </div>
  )
}

export { PaginationControls }
