import { useState } from "react"
import { Link } from "react-router-dom"

import { requestPasswordReset } from "@/api/reset-password"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"

const ERROR_MESSAGE = [
  "Impossible d’envoyer la demande de réinitialisation.",
  "Vérifiez l’adresse email saisie.",
].join(" ")

export default function RequestResetPasswordPage() {
  const [email, setEmail] = useState("")
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState("")
  const [error, setError] = useState("")

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault()

    try {
      setLoading(true)
      setMessage("")
      setError("")

      const response = await requestPasswordReset(email)

      setMessage(response.message)
    } catch {
      setError(ERROR_MESSAGE)
    } finally {
      setLoading(false)
    }
  }

  return (
    <Card
      className="w-full max-w-md rounded-3xl border-border bg-card shadow-xl"
    >
      <CardHeader>
        <CardTitle className="text-2xl">
          Mot de passe oublié
        </CardTitle>
      </CardHeader>

      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <Input
            type="email"
            placeholder="Email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            required
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
            {loading ? "Envoi..." : "Envoyer le lien"}
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
