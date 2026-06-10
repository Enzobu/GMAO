export type ItemsPerPageValue = "6" | "12" | "24" | "36"

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
  { value: "36", label: "36" },
] as const

export function itemsPerPageSize(value: string) {
  return ITEMS_PER_PAGE_OPTIONS.some((option) => option.value === value)
    ? Number(value)
    : 12
}

export function itemsPerPageValue(value: string): ItemsPerPageValue {
  return ITEMS_PER_PAGE_OPTIONS.some((option) => option.value === value)
    ? value as ItemsPerPageValue
    : "12"
}

export function getPaginationState(
  itemCount: number,
  itemsPerPage: ItemsPerPageValue,
  page: number,
): PaginationState {
  const pageSize = Number(itemsPerPage)
  const pageCount = Math.max(1, Math.ceil(itemCount / pageSize))
  const currentPage = Math.min(page, pageCount)
  const pageStart = (currentPage - 1) * pageSize
  const pageEnd = pageStart + pageSize
  const visibleStart = itemCount === 0 ? 0 : pageStart + 1
  const visibleEnd = Math.min(pageEnd, itemCount)

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
  pagination: PaginationState,
) {
  return items.slice(pagination.pageStart, pagination.pageEnd)
}
