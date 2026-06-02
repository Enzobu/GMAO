import { api } from "@/api/client"
import { collectionItems, type ApiCollection } from "@/api/api-collection"
import type { AppUser, UserPayload } from "@/types/user"

export async function getUsers() {
  const response = await api.get<ApiCollection<AppUser>>("/users")

  return collectionItems(response.data)
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
