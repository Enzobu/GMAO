import { useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"

import { resetPassword } from "@/api/reset-password"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"

export default function ResetPasswordPage() {
  const navigate = useNavigate()
  const { token } = useParams<{ token: string }>()
  const [password, setPassword] = useState("")
  const [confirmPassword, setConfirmPassword] = useState("")
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState("")
  const [error, setError] = useState("")

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault()

    if (!token) {
      setError("Lien de réinitialisation invalide.")
      return
    }

    if (password.length < 8) {
      setError("Le mot de passe doit contenir au moins 8 caractères.")
      return
    }

    if (password !== confirmPassword) {
      setError("Les mots de passe ne correspondent pas.")
      return
    }

    try {
      setLoading(true)
      setError("")
      setMessage("")

      const response = await resetPassword(token, password)

      setMessage(response.message)
      globalThis.setTimeout(() => navigate("/login"), 1200)
    } catch {
      setError("Le lien est invalide, expiré ou le mot de passe n'a pas pu être réinitialisé.")
    } finally {
      setLoading(false)
    }
  }

  return (
    <Card className="w-full max-w-md rounded-3xl border-border bg-card shadow-xl">
      <CardHeader>
        <CardTitle className="text-2xl">
          Réinitialiser le mot de passe
        </CardTitle>
      </CardHeader>

      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <Input
            type="password"
            placeholder="Nouveau mot de passe"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
          />

          <Input
            type="password"
            placeholder="Confirmer le mot de passe"
            value={confirmPassword}
            onChange={(event) => setConfirmPassword(event.target.value)}
          />

          {message && (
            <p className="text-sm text-primary">
              {message}
            </p>
          )}

          {error && (
            <p className="text-sm text-red-500">
              {error}
            </p>
          )}

          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? "Réinitialisation..." : "Réinitialiser"}
          </Button>

          <Button asChild variant="ghost" className="w-full">
            <Link to="/login">
              Retour à la connexion
            </Link>
          </Button>
        </form>
      </CardContent>
    </Card>
  )
}
