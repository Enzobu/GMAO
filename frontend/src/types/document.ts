export interface AppDocument {
  id: number
  publicId: string
  name: string
  description?: string | null
  originalFilename?: string | null
  mimeType?: string | null
  size?: number | null
  extension?: string | null
  createdAt: string
  updatedAt: string
}

export interface DocumentMetadataPayload {
  name: string
  description?: string | null
}
