export type ItemsPerPageValue = "6" | "12" | "24" | "all"

export type PaginationState = Readonly<{
  pageSize: number
  pageCount: number
  currentPage: number
  pageStart: number
  pageEnd: number
  visibleStart: number
  visibleEnd: number
}>

export const ITEMS_PER_PAGE_OPTIONS = [
  { value: "6", label: "6" },
  { value: "12", label: "12" },
  { value: "24", label: "24" },
  { value: "all", label: "Tous" },
] as const

export function getPaginationState(
  itemCount: number,
  itemsPerPage: ItemsPerPageValue,
  page: number,
): PaginationState {
  const pageSize = itemsPerPage === "all"
    ? itemCount || 1
    : Number(itemsPerPage)
  const pageCount = Math.max(1, Math.ceil(itemCount / pageSize))
  const currentPage = Math.min(page, pageCount)
  const pageStart = (currentPage - 1) * pageSize
  const pageEnd = pageStart + pageSize
  const visibleStart = itemCount === 0 ? 0 : pageStart + 1
  const visibleEnd = itemsPerPage === "all"
    ? itemCount
    : Math.min(pageEnd, itemCount)

  return {
    pageSize,
    pageCount,
    currentPage,
    pageStart,
    pageEnd,
    visibleStart,
    visibleEnd,
  }
}

export function getPaginatedItems<T>(
  items: T[],
  itemsPerPage: ItemsPerPageValue,
  pagination: PaginationState,
) {
  return itemsPerPage === "all"
    ? items
    : items.slice(pagination.pageStart, pagination.pageEnd)
}
