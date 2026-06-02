import { api } from "@/api/client"
import type { AppDocument, DocumentMetadataPayload } from "@/types/document"

export type DocumentParentType =
  | "users"
  | "vehicles"
  | "vehicle_insurances"
  | "vehicle_inspections"
  | "maintenances"
  | "parts"

export type DocumentParent = Readonly<{
  type: DocumentParentType
  id: string | number
}>

function parentPath(parent: DocumentParent) {
  return `/${parent.type}/${parent.id}/documents`
}

export async function getUserDocuments(userId: string | number) {
  return getParentDocuments({ type: "users", id: userId })
}

export async function getParentDocuments(parent: DocumentParent) {
  const response = await api.get<AppDocument[]>(parentPath(parent))

  return response.data
}

export async function createUserDocument(
  userId: string | number,
  payload: DocumentMetadataPayload & { file: File },
) {
  return createParentDocument({ type: "users", id: userId }, payload)
}

export async function createParentDocument(
  parent: DocumentParent,
  payload: DocumentMetadataPayload & { file: File },
) {
  const formData = new FormData()
  formData.append("file", payload.file)
  formData.append("name", payload.name)

  if (payload.description) {
    formData.append("description", payload.description)
  }

  const response = await api.post<AppDocument>(parentPath(parent), formData)

  return response.data
}

export async function updateUserDocument(
  userId: string | number,
  publicId: string,
  payload: DocumentMetadataPayload,
) {
  return updateParentDocument({ type: "users", id: userId }, publicId, payload)
}

export async function updateParentDocument(
  parent: DocumentParent,
  publicId: string,
  payload: DocumentMetadataPayload,
) {
  const response = await api.patch<AppDocument>(
    `${parentPath(parent)}/${publicId}`,
    payload,
    { headers: { "Content-Type": "application/merge-patch+json" } },
  )

  return response.data
}

export async function deleteUserDocument(
  userId: string | number,
  publicId: string,
) {
  await deleteParentDocument({ type: "users", id: userId }, publicId)
}

export async function deleteParentDocument(
  parent: DocumentParent,
  publicId: string,
) {
  await api.delete(`${parentPath(parent)}/${publicId}`)
}

export async function getUserDocumentBlob(
  userId: string | number,
  publicId: string,
  download = false,
) {
  return getParentDocumentBlob(
    { type: "users", id: userId },
    publicId,
    download,
  )
}

export async function getParentDocumentBlob(
  parent: DocumentParent,
  publicId: string,
  download = false,
) {
  const action = download ? "download" : "file"
  const response = await api.get<Blob>(
    `${parentPath(parent)}/${publicId}/${action}`,
    { responseType: "blob" },
  )

  return response.data
}
