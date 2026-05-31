import { useEffect, useState } from "react"
import { KeyRound, Mail, Save, UserRound } from "lucide-react"

import { getMe } from "@/api/auth"
import {
  getProfile,
  requestProfilePasswordReset,
  updateProfile,
} from "@/api/profile"
import { useAuthStore } from "@/stores/auth-store"
import type { Profile, UpdateProfilePayload } from "@/types/profile"

import { DocumentsPanel } from "@/components/documents-panel"
import { LabelText } from "@/components/page-primitives"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"

const emptyForm: UpdateProfilePayload = {
  firstname: "",
  lastname: "",
  address: {
    line1: "",
    line2: "",
    postalCode: "",
    city: "",
    country: "",
  },
}

export default function ProfilePage() {
  const setUser = useAuthStore((state) => state.setUser)
  const currentUser = useAuthStore((state) => state.user)
  const isAdmin = currentUser?.roles.includes("ROLE_ADMIN") ?? false
  const [profile, setProfile] = useState<Profile | null>(null)
  const [form, setForm] = useState<UpdateProfilePayload>(emptyForm)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [resettingPassword, setResettingPassword] = useState(false)
  const [message, setMessage] = useState("")
  const [error, setError] = useState("")

  useEffect(() => {
    let isMounted = true

    async function loadProfile() {
      try {
        setLoading(true)
        setError("")

        const data = await getProfile()

        if (isMounted) {
          setProfile(data)
          setForm({
            firstname: data.firstname,
            lastname: data.lastname,
            address: data.address,
          })
        }
      } catch {
        if (isMounted) {
          setError("Impossible de charger le profil.")
        }
      } finally {
        if (isMounted) {
          setLoading(false)
        }
      }
    }

    void loadProfile()

    return () => {
      isMounted = false
    }
  }, [])

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault()

    try {
      setSaving(true)
      setMessage("")
      setError("")

      const updatedProfile = await updateProfile(form)
      const user = await getMe()

      setProfile(updatedProfile)
      setUser(user)
      setMessage("Profil mis à jour.")
    } catch {
      setError("Impossible d’enregistrer les modifications.")
    } finally {
      setSaving(false)
    }
  }

  async function handlePasswordReset() {
    try {
      setResettingPassword(true)
      setMessage("")
      setError("")

      const response = await requestProfilePasswordReset()

      setMessage(response.message)
    } catch {
      setError("Impossible d’envoyer l’email de réinitialisation.")
    } finally {
      setResettingPassword(false)
    }
  }

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-4xl font-bold tracking-tight">
          Profil
        </h1>

        <p className="mt-2 text-muted-foreground">
          Gérez vos informations personnelles et la sécurité du compte.
        </p>
      </div>

      {message && (
        <div className="rounded-2xl border border-primary/30 bg-primary/10 p-4 text-sm text-primary">
          {message}
        </div>
      )}

      {error && (
        <div className="rounded-2xl border border-red-500/40 bg-red-500/10 p-4 text-sm text-red-500">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-8">
        <div className="grid gap-6 xl:grid-cols-2">
          <Card className="rounded-3xl border-border bg-card shadow-sm">
            <CardHeader>
              <CardTitle className="flex items-center gap-3">
                <UserRound className="h-5 w-5 text-primary" />
                Informations
              </CardTitle>
            </CardHeader>

            <CardContent className="space-y-4">
              <Field label="Email">
                <Input value={profile?.email ?? ""} disabled />
              </Field>

              <div className="grid gap-4 md:grid-cols-2">
                <Field label="Prénom" required>
                  <Input
                    value={form.firstname}
                    required
                    disabled={loading || saving}
                    onChange={(event) => setForm((current) => ({
                      ...current,
                      firstname: event.target.value,
                    }))}
                  />
                </Field>

                <Field label="Nom" required>
                  <Input
                    value={form.lastname}
                    required
                    disabled={loading || saving}
                    onChange={(event) => setForm((current) => ({
                      ...current,
                      lastname: event.target.value,
                    }))}
                  />
                </Field>
              </div>
            </CardContent>
          </Card>

          <Card className="rounded-3xl border-border bg-card shadow-sm">
            <CardHeader>
              <CardTitle className="flex items-center gap-3">
                <Mail className="h-5 w-5 text-primary" />
                Adresse
              </CardTitle>
            </CardHeader>

            <CardContent className="space-y-4">
              <Field label="Adresse" required>
                <Input
                  value={form.address.line1}
                  required
                  disabled={loading || saving}
                  onChange={(event) => updateAddress("line1", event.target.value)}
                />
              </Field>

              <Field label="Complément">
                <Input
                  value={form.address.line2}
                  disabled={loading || saving}
                  onChange={(event) => updateAddress("line2", event.target.value)}
                />
              </Field>

              <div className="grid gap-4 md:grid-cols-3">
                <Field label="Code postal" required>
                  <Input
                    value={form.address.postalCode}
                    maxLength={5}
                    required
                    disabled={loading || saving}
                    onChange={(event) => updateAddress("postalCode", formatPostalCode(event.target.value))}
                  />
                </Field>

                <Field label="Ville" required>
                  <Input
                    value={form.address.city}
                    required
                    disabled={loading || saving}
                    onChange={(event) => updateAddress("city", event.target.value)}
                  />
                </Field>

                <Field label="Pays" required>
                  <Input
                    value={form.address.country}
                    required
                    disabled={loading || saving}
                    onChange={(event) => updateAddress("country", event.target.value)}
                  />
                </Field>
              </div>
            </CardContent>
          </Card>
        </div>

        <div className="flex justify-end">
          <Button type="submit" disabled={loading || saving}>
            <Save className="mr-2 h-4 w-4" />
            {saving ? "Enregistrement..." : "Enregistrer"}
          </Button>
        </div>
      </form>

      <Card className="rounded-3xl border-border bg-card shadow-sm">
        <CardHeader>
          <CardTitle className="flex items-center gap-3">
            <KeyRound className="h-5 w-5 text-primary" />
            Sécurité
          </CardTitle>
        </CardHeader>

        <CardContent className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <p className="font-medium">
              Réinitialisation du mot de passe
            </p>

            <p className="mt-1 text-sm text-muted-foreground">
              Un lien sécurisé sera envoyé à votre adresse email.
            </p>
          </div>

          <Button
            type="button"
            variant="outline"
            disabled={resettingPassword}
            onClick={handlePasswordReset}
          >
            {resettingPassword ? "Envoi..." : "Recevoir un lien"}
          </Button>
        </CardContent>
      </Card>

      {profile && (
        <DocumentsPanel parent={{ type: "users", id: profile.id }} canDelete={isAdmin} />
      )}
    </div>
  )

  function updateAddress(key: keyof UpdateProfilePayload["address"], value: string) {
    setForm((current) => ({
      ...current,
      address: {
        ...current.address,
        [key]: value,
      },
    }))
  }
}

function formatPostalCode(value: string) {
  return value.replace(/\D/g, "").slice(0, 5)
}

function Field({
  label,
  required = false,
  children,
}: Readonly<{
  label: string
  required?: boolean
  children: React.ReactNode
}>) {
  return (
    <label className="block space-y-2">
      <span className="text-sm font-medium text-muted-foreground">
        <LabelText label={label} required={required} />
      </span>
      {children}
    </label>
  )
}
