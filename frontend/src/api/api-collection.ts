export type ApiCollection<T> =
  | T[]
  | {
      member?: T[]
      "hydra:member"?: T[]
      totalItems?: number
      "hydra:totalItems"?: number
      view?: ApiCollectionView
      "hydra:view"?: HydraCollectionView
    }
  | null
  | undefined

type ApiCollectionView = Readonly<{
  first?: string
  last?: string
  next?: string
  previous?: string
}>

type HydraCollectionView = Readonly<{
  "hydra:first"?: string
  "hydra:last"?: string
  "hydra:next"?: string
  "hydra:previous"?: string
}>

export type CollectionPage<T> = Readonly<{
  items: T[]
  totalItems: number
  page: number
  pageSize: number
  pageCount: number
  visibleStart: number
  visibleEnd: number
}>

export type CollectionParams = Readonly<{
  page: number
  itemsPerPage: number
  [key: string]: string | number | boolean | undefined
}>

export const COLLECTION_REQUEST_HEADERS = {
  Accept: "application/ld+json",
} as const

export function collectionItems<T>(data: ApiCollection<T>) {
  if (Array.isArray(data)) {
    return data
  }

  return data?.member ?? data?.["hydra:member"] ?? []
}

export function collectionPage<T>(
  data: ApiCollection<T>,
  page: number,
  pageSize: number,
): CollectionPage<T> {
  const items = collectionItems(data)
  const totalItems = collectionTotalItems(data, items.length)
  const pageCount = Math.max(1, Math.ceil(totalItems / pageSize))
  const currentPage = Math.min(page, pageCount)
  const visibleStart = totalItems === 0 ? 0 : (currentPage - 1) * pageSize + 1
  const visibleEnd = Math.min(visibleStart + items.length - 1, totalItems)

  return {
    items,
    totalItems,
    page: currentPage,
    pageSize,
    pageCount,
    visibleStart,
    visibleEnd: totalItems === 0 ? 0 : visibleEnd,
  }
}

export function emptyCollectionPage<T>(pageSize: number): CollectionPage<T> {
  return {
    items: [],
    totalItems: 0,
    page: 1,
    pageSize,
    pageCount: 1,
    visibleStart: 0,
    visibleEnd: 0,
  }
}

export function collectionParams(params: CollectionParams) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => (
      value !== undefined && value !== "all" && value !== ""
    )),
  )
}

export function collectionNextPage<T>(data: ApiCollection<T>) {
  if (!data || Array.isArray(data)) {
    return null
  }

  const next = data.view?.next ?? data["hydra:view"]?.["hydra:next"]

  if (!next) {
    return null
  }

  const url = new URL(next, globalThis.location.origin)
  const page = Number(url.searchParams.get("page"))

  return Number.isFinite(page) && page > 0 ? page : null
}

function collectionTotalItems<T>(data: ApiCollection<T>, fallback: number) {
  if (!data || Array.isArray(data)) {
    return fallback
  }

  return data.totalItems ?? data["hydra:totalItems"] ?? fallback
}
