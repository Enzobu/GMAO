import { useState } from "react"

import { requestPasswordReset } from "@/api/reset-password"
import { Input } from "@/components/ui/input"
import { ResetPasswordCard } from "@/pages/reset-password/ResetPasswordCard"

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
    <ResetPasswordCard
      title="Mot de passe oublié"
      message={message}
      error={error}
      isLoading={loading}
      submitLabel="Envoyer le lien"
      loadingLabel="Envoi..."
      onSubmit={handleSubmit}
    >
      <Input
        type="email"
        placeholder="Email"
        value={email}
        onChange={(event) => setEmail(event.target.value)}
        required
      />
    </ResetPasswordCard>
  )
}
