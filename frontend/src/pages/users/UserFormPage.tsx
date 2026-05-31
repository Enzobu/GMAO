import { useEffect, useState } from "react"
import type { FormEvent } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { ArrowLeft, Save } from "lucide-react"

import { createUser, getUser, updateUser } from "@/api/users"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { LabelText } from "@/components/page-primitives"
import type { AppUser, UserPayload } from "@/types/user"

const emptyForm = {
  firstname: "",
  lastname: "",
  email: "",
  roles: ["ROLE_USER"],
  address: { line1: "", line2: "", postalCode: "", city: "", country: "France" },
}

export default function UserFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const isEditing = Boolean(id)
  const [form, setForm] = useState<UserPayload>(emptyForm)
  const [isLoading, setIsLoading] = useState(isEditing)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let ignore = false
    async function load() {
      if (!id) return
      try {
        const user = await getUser(id)
        if (!ignore) setForm(userToForm(user))
      } catch {
        if (!ignore) setError("Impossible de charger l’utilisateur.")
      } finally {
        if (!ignore) setIsLoading(false)
      }
    }
    load()
    return () => { ignore = true }
  }, [id])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setIsSaving(true)
    setError(null)
    try {
      const saved = id ? await updateUser(id, form) : await createUser(form)
      navigate(`/users/${saved.id}`)
    } catch {
      setError("Impossible d’enregistrer l’utilisateur. Vérifiez les champs saisis.")
    } finally {
      setIsSaving(false)
    }
  }

  function toggleRole(role: string) {
    setForm((current) => {
      const roles = current.roles.includes(role) ? current.roles.filter((item) => item !== role) : [...current.roles, role]
      return { ...current, roles: roles.includes("ROLE_USER") ? roles : ["ROLE_USER", ...roles] }
    })
  }

  if (isLoading) return <div className="text-sm text-muted-foreground">Chargement du formulaire...</div>

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><h1 className="text-2xl font-semibold tracking-tight">{isEditing ? "Modifier l’utilisateur" : "Ajouter un utilisateur"}</h1><p className="text-sm text-muted-foreground">{isEditing ? "Mettez à jour ses informations." : "Un email de définition du mot de passe sera envoyé."}</p></div><Button variant="outline" asChild><Link to={id ? `/users/${id}` : "/users"}><ArrowLeft />Retour</Link></Button></div>
      {error && <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{error}</div>}
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="grid gap-4 xl:grid-cols-2">
          <Card><CardHeader><CardTitle>Informations utilisateur</CardTitle></CardHeader><CardContent className="grid gap-4 md:grid-cols-2"><Field label="Prénom" value={form.firstname} onChange={(value) => setForm((current) => ({ ...current, firstname: value }))} required /><Field label="Nom" value={form.lastname} onChange={(value) => setForm((current) => ({ ...current, lastname: value }))} required /><div className="md:col-span-2"><Field label="Email" type="email" value={form.email} onChange={(value) => setForm((current) => ({ ...current, email: value }))} required /></div><div className="grid gap-2 md:col-span-2"><span className="text-sm font-medium">Rôles</span><label className="flex items-center gap-2 rounded-lg border p-3 text-sm"><input type="checkbox" checked disabled />Utilisateur</label><label className="flex items-center gap-2 rounded-lg border p-3 text-sm"><input type="checkbox" checked={form.roles.includes("ROLE_ADMIN")} onChange={() => toggleRole("ROLE_ADMIN")} />Administrateur</label></div></CardContent></Card>
          <Card><CardHeader><CardTitle>Adresse</CardTitle></CardHeader><CardContent className="grid gap-4 md:grid-cols-2"><div className="md:col-span-2"><Field label="Adresse" value={form.address.line1} onChange={(value) => setAddressField("line1", value, setForm)} required /></div><div className="md:col-span-2"><Field label="Complément" value={form.address.line2 ?? ""} onChange={(value) => setAddressField("line2", value, setForm)} /></div><Field label="Code postal" value={form.address.postalCode} maxLength={5} onChange={(value) => setAddressField("postalCode", formatPostalCode(value), setForm)} required /><Field label="Ville" value={form.address.city} onChange={(value) => setAddressField("city", value, setForm)} required /><Field label="Pays" value={form.address.country} onChange={(value) => setAddressField("country", value, setForm)} required /></CardContent></Card>
        </div>
        <div className="flex justify-end gap-2"><Button variant="outline" asChild><Link to={id ? `/users/${id}` : "/users"}>Annuler</Link></Button><Button type="submit" disabled={isSaving}><Save />{isSaving ? "Enregistrement..." : "Enregistrer"}</Button></div>
      </form>
    </div>
  )
}

function Field({ label, value, onChange, required, ...props }: Readonly<{ label: string; value: string; onChange: (value: string) => void } & Omit<React.ComponentProps<typeof Input>, "value" | "onChange">>) {
  return <label className="grid gap-1.5 text-sm font-medium"><LabelText label={label} required={required} /><Input value={value} required={required} onChange={(event) => onChange(event.target.value)} {...props} /></label>
}

function setAddressField(field: keyof UserPayload["address"], value: string, setForm: React.Dispatch<React.SetStateAction<UserPayload>>) {
  setForm((current) => ({ ...current, address: { ...current.address, [field]: value } }))
}

function formatPostalCode(value: string) {
  return value.replaceAll(/\D/g, "").slice(0, 5)
}

function userToForm(user: AppUser): UserPayload {
  return { firstname: user.firstname ?? "", lastname: user.lastname ?? "", email: user.email ?? "", roles: user.roles.includes("ROLE_USER") ? user.roles : ["ROLE_USER", ...user.roles], address: { line1: user.address?.line1 ?? "", line2: user.address?.line2 ?? "", postalCode: user.address?.postalCode ?? "", city: user.address?.city ?? "", country: user.address?.country ?? "France" } }
}
