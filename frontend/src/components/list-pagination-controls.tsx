import type { CollectionPage } from "@/api/api-collection"
import {
  ITEMS_PER_PAGE_OPTIONS,
  itemsPerPageValue,
  type ItemsPerPageValue,
} from "@/components/list-page-pagination"
import { PaginationControls } from "@/components/ui/pagination-controls"

export function ListPaginationControls({
  itemLabel,
  pagination,
  itemsPerPage,
  onItemsPerPageChange,
  onPageChange,
}: Readonly<{
  itemLabel: string
  pagination: Pick<
    CollectionPage<unknown>,
    "page" | "pageCount" | "totalItems" | "visibleStart" | "visibleEnd"
  >
  itemsPerPage: ItemsPerPageValue
  onItemsPerPageChange: (value: ItemsPerPageValue) => void
  onPageChange: (page: number) => void
}>) {
  return (
    <PaginationControls
      currentPage={pagination.page}
      pageCount={pagination.pageCount}
      totalItems={pagination.totalItems}
      visibleStart={pagination.visibleStart}
      visibleEnd={pagination.visibleEnd}
      itemsPerPage={itemsPerPageValue(itemsPerPage)}
      itemsPerPageOptions={ITEMS_PER_PAGE_OPTIONS}
      onItemsPerPageChange={(value) => {
        onItemsPerPageChange(value as ItemsPerPageValue)
      }}
      onPreviousPage={() => onPageChange(Math.max(1, pagination.page - 1))}
      onNextPage={() => {
        onPageChange(Math.min(pagination.pageCount, pagination.page + 1))
      }}
      itemLabel={itemLabel}
    />
  )
}
