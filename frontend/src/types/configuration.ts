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

export interface InspectionCenterAddress {
  line1: string
  line2?: string | null
  postalCode: string
  city: string
  country: string
}

export interface InspectionCenterConfigurationItem {
  id: number
  name: string
  phone?: string | null
  email?: string | null
  address: InspectionCenterAddress
  isDeleted?: boolean
}

export interface InspectionCenterConfigurationPayload {
  name: string
  phone?: string | null
  email?: string | null
  address: InspectionCenterAddress
}
