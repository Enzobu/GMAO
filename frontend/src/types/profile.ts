export interface ProfileAddress {
  line1: string
  line2: string
  postalCode: string
  city: string
  country: string
}

export interface Profile {
  id: number
  email: string
  firstname: string
  lastname: string
  displayName: string
  initials: string
  address: ProfileAddress
}

export interface UpdateProfilePayload {
  firstname: string
  lastname: string
  address: ProfileAddress
}
