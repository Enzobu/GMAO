import { api } from "@/api/client"

export async function login(payload: {
  email: string
  password: string
}) {
  const response = await api.post("/login", payload)

  return response.data
}

export async function getMe() {
  const response = await api.get("/me")

  return response.data
}
