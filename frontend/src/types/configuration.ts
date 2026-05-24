export interface ConfigurationItem {
  id: number
  name: string
  description?: string | null
  isDeleted?: boolean
}

export interface ConfigurationPayload {
  name: string
  description?: string | null
}
