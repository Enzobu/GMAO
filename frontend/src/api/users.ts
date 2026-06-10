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
import type { AppUser, UserPayload } from "@/types/user"

export async function getUsers() {
  const response = await api.get<ApiCollection<AppUser>>("/users", {
    headers: COLLECTION_REQUEST_HEADERS,
    params: { itemsPerPage: 36 },
  })
  const items = [...collectionItems(response.data)]
  let nextPage = collectionNextPage(response.data)

  while (nextPage !== null) {
    const nextResponse = await api.get<ApiCollection<AppUser>>("/users", {
      headers: COLLECTION_REQUEST_HEADERS,
      params: { page: nextPage, itemsPerPage: 36 },
    })

    items.push(...collectionItems(nextResponse.data))
    nextPage = collectionNextPage(nextResponse.data)
  }

  return items
}

export async function getUsersPage(params: CollectionParams) {
  const response = await api.get<ApiCollection<AppUser>>("/users", {
    headers: COLLECTION_REQUEST_HEADERS,
    params: collectionParams(params),
  })

  return collectionPage(response.data, params.page, params.itemsPerPage)
}

export async function getUser(id: string | number) {
  const response = await api.get<AppUser>(`/users/${id}`)

  return response.data
}

export async function createUser(payload: UserPayload) {
  const response = await api.post<AppUser>("/users", payload)

  return response.data
}

export async function updateUser(id: string | number, payload: UserPayload) {
  const response = await api.patch<AppUser>(`/users/${id}`, payload, {
    headers: { "Content-Type": "application/merge-patch+json" },
  })

  return response.data
}

export async function deleteUser(id: string | number) {
  await api.delete(`/users/${id}`)
}
