import { api } from "@/api/client"
import {
  collectionItems,
  collectionPage,
  collectionParams,
  collectionNextPage,
  COLLECTION_REQUEST_HEADERS,
  type ApiCollection,
  type CollectionParams,
} from "@/api/api-collection"
import type { Intervention, InterventionPayload } from "@/types/intervention"

export async function getInterventions() {
  const firstPage = await api.get<ApiCollection<Intervention>>("/maintenances", {
    headers: COLLECTION_REQUEST_HEADERS,
    params: { itemsPerPage: 36 },
  })
  const items = [...collectionItems(firstPage.data)]
  let nextPage = collectionNextPage(firstPage.data)

  while (nextPage !== null) {
    const response = await api.get<ApiCollection<Intervention>>(
      "/maintenances",
      {
        headers: COLLECTION_REQUEST_HEADERS,
        params: { page: nextPage, itemsPerPage: 36 },
      },
    )

    items.push(...collectionItems(response.data))
    nextPage = collectionNextPage(response.data)
  }

  return items
}

export async function getInterventionsPage(params: CollectionParams) {
  const response = await api.get<ApiCollection<Intervention>>("/maintenances", {
    headers: COLLECTION_REQUEST_HEADERS,
    params: collectionParams(params),
  })

  return collectionPage(response.data, params.page, params.itemsPerPage)
}

export async function getIntervention(id: string | number) {
  const response = await api.get<Intervention>(`/maintenances/${id}`)
  return response.data
}

export async function createIntervention(
  payload: InterventionPayload,
  forceMileage = false,
) {
  const response = await api.post<Intervention>("/maintenances", payload, {
    params: forceMileage ? { forceMileage: true } : undefined,
  })
  return response.data
}

export async function updateIntervention(
  id: string | number,
  payload: Partial<InterventionPayload>,
  forceMileage = false,
) {
  const response = await api.patch<Intervention>(
    `/maintenances/${id}`,
    payload,
    {
      headers: { "Content-Type": "application/merge-patch+json" },
      params: forceMileage ? { forceMileage: true } : undefined,
    },
  )
  return response.data
}

export async function deleteIntervention(id: string | number) {
  await api.delete(`/maintenances/${id}`)
}
