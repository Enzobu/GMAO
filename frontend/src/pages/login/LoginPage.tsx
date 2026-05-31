import { useState } from "react"
import { useNavigate } from "react-router-dom"

import { getMe, login } from "@/api/auth"
import { useAuthStore } from "@/stores/auth-store"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { PasswordInput } from "@/components/password-input"

export default function LoginPage() {
  const navigate = useNavigate()

  const setToken = useAuthStore((state) => state.setToken)
  const setUser = useAuthStore((state) => state.setUser)

  const [email, setEmail] = useState("")
  const [password, setPassword] = useState("")
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState("")

  async function handleLogin(e: React.FormEvent) {
    e.preventDefault()

    try {
      setLoading(true)
      setError("")

      const response = await login({
        email,
        password,
      })

      setToken(response.token)

      const user = await getMe()

      setUser(user)

      navigate("/dashboard")
    } catch {
      setError("Identifiants invalides")
    } finally {
      setLoading(false)
    }
  }

  return (
    <Card className="w-full max-w-md rounded-3xl border-border bg-card shadow-xl">
      <CardHeader>
        <CardTitle className="text-2xl">
          Connexion
        </CardTitle>
      </CardHeader>

      <CardContent>
        <form
          onSubmit={handleLogin}
          className="space-y-4"
        >
          <Input
            type="email"
            placeholder="Email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
          />

          <PasswordInput
            placeholder="Mot de passe"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />

          {error && (
            <p className="text-sm text-red-500">
              {error}
            </p>
          )}

          <Button
            type="submit"
            className="w-full"
            disabled={loading}
          >
            {loading ? "Connexion..." : "Se connecter"}
          </Button>
        </form>
      </CardContent>
    </Card>
  )
}
