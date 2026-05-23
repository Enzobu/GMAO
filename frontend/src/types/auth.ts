export interface User {
  id: number
  email: string
  roles: string[]
  displayName: string
  initials: string
}

export interface LoginPayload {
  email: string
  password: string
}

export interface LoginResponse {
  token: string
}