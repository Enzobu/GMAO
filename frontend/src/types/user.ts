export interface UserAddress {
  id?: number
  line1: string
  line2?: string | null
  postalCode: string
  city: string
  country: string
}

export interface AppUser {
  id: number
  email: string
  roles: string[]
  firstname: string
  lastname: string
  address?: UserAddress | null
  isDeleted?: boolean
}

export interface UserPayload {
  email: string
  roles: string[]
  firstname: string
  lastname: string
  address: UserAddress
}
