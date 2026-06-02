import { api } from "@/api/client"
import { collectionItems } from "@/api/api-collection"
import type { Intervention, InterventionPayload } from "@/types/intervention"

type ApiCollection<T> =
  | T[]
  | {
      member?: T[]
      "hydra:member"?: T[]
      view?: { next?: string }
      "hydra:view"?: { "hydra:next"?: string }
    }

export async function getInterventions() {
  const firstPage = await api.get<ApiCollection<Intervention>>("/maintenances")
  const items = [...collectionItems(firstPage.data)]
  let nextPage = collectionNextPage(firstPage.data)

  while (nextPage !== null) {
    const response = await api.get<ApiCollection<Intervention>>(
      "/maintenances",
      { params: { page: nextPage } },
    )

    items.push(...collectionItems(response.data))
    nextPage = collectionNextPage(response.data)
  }

  return items
}

function collectionNextPage<T>(data: ApiCollection<T>) {
  if (Array.isArray(data)) return null

  const next = data.view?.next ?? data["hydra:view"]?.["hydra:next"]
  if (!next) return null

  const url = new URL(next, globalThis.location.origin)
  const page = Number(url.searchParams.get("page"))

  return Number.isFinite(page) && page > 0 ? page : null
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
