import { api } from "@/api/client"
import { collectionItems, type ApiCollection } from "@/api/api-collection"
import type { Part, PartPayload } from "@/types/part"

export async function getParts() {
  const response = await api.get<ApiCollection<Part>>("/parts")

  return collectionItems(response.data)
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
