import { api } from "@/api/client"
import {
  collectionItems,
  collectionNextPage,
  collectionPage,
  collectionParams,
  COLLECTION_REQUEST_HEADERS,
  type ApiCollection,
  type CollectionParams,
} from "@/api/api-collection"
import type { Part, PartPayload } from "@/types/part"

export async function getParts() {
  const response = await api.get<ApiCollection<Part>>("/parts", {
    headers: COLLECTION_REQUEST_HEADERS,
    params: { itemsPerPage: 36 },
  })
  const items = [...collectionItems(response.data)]
  let nextPage = collectionNextPage(response.data)

  while (nextPage !== null) {
    const nextResponse = await api.get<ApiCollection<Part>>("/parts", {
      headers: COLLECTION_REQUEST_HEADERS,
      params: { page: nextPage, itemsPerPage: 36 },
    })

    items.push(...collectionItems(nextResponse.data))
    nextPage = collectionNextPage(nextResponse.data)
  }

  return items
}

export async function getPartsPage(params: CollectionParams) {
  const response = await api.get<ApiCollection<Part>>("/parts", {
    headers: COLLECTION_REQUEST_HEADERS,
    params: collectionParams(params),
  })

  return collectionPage(response.data, params.page, params.itemsPerPage)
}

export async function getPart(id: string | number) {
  const response = await api.get<Part>(`/parts/${id}`)

  return response.data
}

export async function createPart(payload: PartPayload) {
  const response = await api.post<Part>("/parts", payload)

  return response.data
}

export async function updatePart(id: string | number, payload: PartPayload) {
  const response = await api.patch<Part>(`/parts/${id}`, payload, {
    headers: { "Content-Type": "application/merge-patch+json" },
  })

  return response.data
}

export async function updatePartQuantity(
  id: string | number,
  quantity: number,
) {
  const response = await api.patch<Part>(`/parts/${id}`, { quantity }, {
    headers: { "Content-Type": "application/merge-patch+json" },
  })

  return response.data
}

export async function deletePart(id: string | number) {
  await api.delete(`/parts/${id}`)
}
