import type { ReactNode } from "react"

import type { CollectionPage } from "@/api/api-collection"
import type { ItemsPerPageValue } from "@/components/list-page-pagination"
import { ListPaginationControls } from "@/components/list-pagination-controls"

export function PaginatedListSection({
  children,
  itemLabel,
  pagination,
  itemsPerPage,
  onItemsPerPageChange,
  onPageChange,
}: Readonly<{
  children: ReactNode
  itemLabel: string
  pagination: Pick<
    CollectionPage<unknown>,
    "page" | "pageCount" | "totalItems" | "visibleStart" | "visibleEnd"
  >
  itemsPerPage: ItemsPerPageValue
  onItemsPerPageChange: (value: ItemsPerPageValue) => void
  onPageChange: (page: number) => void
}>) {
  const controls = (
    <ListPaginationControls
      itemLabel={itemLabel}
      pagination={pagination}
      itemsPerPage={itemsPerPage}
      onItemsPerPageChange={onItemsPerPageChange}
      onPageChange={onPageChange}
    />
  )

  return (
    <>
      {controls}
      {children}
      {controls}
    </>
  )
}
