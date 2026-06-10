import { useState } from "react"
import { useNavigate, useParams } from "react-router-dom"

import { resetPassword } from "@/api/reset-password"
import { PasswordInput } from "@/components/password-input"
import { ResetPasswordCard } from "@/pages/reset-password/ResetPasswordCard"

const resetPasswordErrorMessage = [
  "Le lien est invalide, expiré ou le mot de passe n'a pas pu être",
  "réinitialisé.",
].join(" ")

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
      setError(resetPasswordErrorMessage)
    } finally {
      setLoading(false)
    }
  }

  return (
    <ResetPasswordCard
      title="Réinitialiser le mot de passe"
      message={message}
      error={error}
      isLoading={loading}
      submitLabel="Réinitialiser"
      loadingLabel="Réinitialisation..."
      onSubmit={handleSubmit}
    >
      <PasswordInput
        placeholder="Nouveau mot de passe"
        value={password}
        onChange={(event) => setPassword(event.target.value)}
      />

      <PasswordInput
        placeholder="Confirmer le mot de passe"
        value={confirmPassword}
        onChange={(event) => setConfirmPassword(event.target.value)}
      />
    </ResetPasswordCard>
  )
}
