import { useEffect, useState } from "react"
import type { FormEvent } from "react"
import { useNavigate, useParams } from "react-router-dom"

import { createUser, getUser, updateUser } from "@/api/users"
import { FormDocumentsField } from "@/components/form-documents-field"
import { FormActions } from "@/components/form-actions"
import { ErrorMessage, Field, PageHeader } from "@/components/page-primitives"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  createDocumentFileInput,
  FILE_TOO_LARGE_MESSAGE,
  hasTooLargeDocument,
  selectedDocumentFiles,
  type DocumentFileInput,
  uploadParentDocuments,
} from "@/lib/form-documents"
import { capitalizeFirstLetter } from "@/lib/text-format"
import type { AppUser, UserPayload } from "@/types/user"

const emptyForm = {
  firstname: "",
  lastname: "",
  email: "",
  roles: ["ROLE_USER"],
  address: {
    line1: "",
    line2: "",
    postalCode: "",
    city: "",
    country: "France",
  },
}

const SAVE_ERROR = [
  "Impossible d’enregistrer l’utilisateur.",
  "Vérifiez les champs saisis.",
].join(" ")

export default function UserFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const isEditing = Boolean(id)
  const showDocumentFields = !isEditing
  const [form, setForm] = useState<UserPayload>(emptyForm)
  const [documentFiles, setDocumentFiles] = useState<DocumentFileInput[]>([
    createDocumentFileInput(),
  ])
  const [isLoading, setIsLoading] = useState(isEditing)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let ignore = false

    async function load() {
      if (!id) {
        return
      }

      try {
        const user = await getUser(id)
        if (!ignore) {
          setForm(userToForm(user))
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger l’utilisateur.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    load()

    return () => {
      ignore = true
    }
  }, [id])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setIsSaving(true)
    setError(null)

    if (showDocumentFields && hasTooLargeDocument(documentFiles)) {
      setError(FILE_TOO_LARGE_MESSAGE)
      setIsSaving(false)

      return
    }

    try {
      const saved = id ? await updateUser(id, form) : await createUser(form)

      if (showDocumentFields) {
        await uploadParentDocuments(
          { type: "users", id: saved.id },
          selectedDocumentFiles(documentFiles),
        )
      }

      navigate(`/users/${saved.id}`)
    } catch {
      setError(SAVE_ERROR)
    } finally {
      setIsSaving(false)
    }
  }

  function updateField(
    field: "firstname" | "lastname" | "email",
    value: string,
  ) {
    setForm((current) => ({
      ...current,
      [field]: field === "email" ? value : capitalizeFirstLetter(value),
    }))
  }

  function toggleRole(role: string) {
    setForm((current) => {
      const roles = current.roles.includes(role)
        ? current.roles.filter((item) => item !== role)
        : [...current.roles, role]

      return {
        ...current,
        roles: roles.includes("ROLE_USER") ? roles : ["ROLE_USER", ...roles],
      }
    })
  }

  if (isLoading) {
    return (
      <div className="text-sm text-muted-foreground">
        Chargement du formulaire...
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={userFormTitle(isEditing)}
        description={formDescription(isEditing)}
        backTo={id ? `/users/${id}` : "/users"}
      />

      {error && <ErrorMessage>{error}</ErrorMessage>}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="grid gap-4 xl:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Informations utilisateur</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4 md:grid-cols-2">
              <Field
                label="Prénom"
                value={form.firstname}
                onChange={(value) => updateField("firstname", value)}
                required
              />
              <Field
                label="Nom"
                value={form.lastname}
                onChange={(value) => updateField("lastname", value)}
                required
              />
              <div className="md:col-span-2">
                <Field
                  label="Email"
                  type="email"
                  value={form.email}
                  onChange={(value) => updateField("email", value)}
                  required
                />
              </div>
              <RolesField
                isAdmin={form.roles.includes("ROLE_ADMIN")}
                onToggleAdmin={() => toggleRole("ROLE_ADMIN")}
              />
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Adresse</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4 md:grid-cols-2">
              <div className="md:col-span-2">
                <Field
                  label="Adresse"
                  value={form.address.line1}
                  onChange={(value) => setAddressField("line1", value, setForm)}
                  required
                />
              </div>
              <div className="md:col-span-2">
                <Field
                  label="Complément"
                  value={form.address.line2 ?? ""}
                  onChange={(value) => setAddressField("line2", value, setForm)}
                />
              </div>
              <Field
                label="Code postal"
                value={form.address.postalCode}
                maxLength={5}
                onChange={(value) => {
                  setAddressField(
                    "postalCode",
                    formatPostalCode(value),
                    setForm,
                  )
                }}
                required
              />
              <Field
                label="Ville"
                value={form.address.city}
                onChange={(value) => setAddressField("city", value, setForm)}
                required
              />
              <Field
                label="Pays"
                value={form.address.country}
                onChange={(value) => setAddressField("country", value, setForm)}
                required
              />
            </CardContent>
          </Card>
        </div>

        {showDocumentFields && (
          <FormDocumentsField
            canEdit
            documentFiles={documentFiles}
            isSaving={isSaving}
            setDocumentFiles={setDocumentFiles}
          />
        )}

        <FormActions
          cancelTo={id ? `/users/${id}` : "/users"}
          canEdit
          isSaving={isSaving}
        />
      </form>
    </div>
  )
}

function RolesField({
  isAdmin,
  onToggleAdmin,
}: Readonly<{ isAdmin: boolean; onToggleAdmin: () => void }>) {
  return (
    <div className="grid gap-2 md:col-span-2">
      <span className="text-sm font-medium">Rôles</span>
      <label className="flex items-center gap-2 rounded-lg border p-3 text-sm">
        <input type="checkbox" checked disabled />
        <span>Utilisateur</span>
      </label>
      <label className="flex items-center gap-2 rounded-lg border p-3 text-sm">
        <input type="checkbox" checked={isAdmin} onChange={onToggleAdmin} />
        <span>Administrateur</span>
      </label>
    </div>
  )
}

function setAddressField(
  field: keyof UserPayload["address"],
  value: string,
  setForm: React.Dispatch<React.SetStateAction<UserPayload>>,
) {
  setForm((current) => ({
    ...current,
    address: { ...current.address, [field]: value },
  }))
}

function formatPostalCode(value: string) {
  return value.replaceAll(/\D/g, "").slice(0, 5)
}

function userToForm(user: AppUser): UserPayload {
  return {
    firstname: capitalizeFirstLetter(user.firstname),
    lastname: capitalizeFirstLetter(user.lastname),
    email: user.email ?? "",
    roles: user.roles.includes("ROLE_USER")
      ? user.roles
      : ["ROLE_USER", ...user.roles],
    address: {
      line1: user.address?.line1 ?? "",
      line2: user.address?.line2 ?? "",
      postalCode: user.address?.postalCode ?? "",
      city: user.address?.city ?? "",
      country: user.address?.country ?? "France",
    },
  }
}

function formDescription(isEditing: boolean) {
  return isEditing
    ? "Mettez à jour ses informations."
    : "Un email de définition du mot de passe sera envoyé."
}

function userFormTitle(isEditing: boolean) {
  return isEditing ? "Modifier l’utilisateur" : "Ajouter un utilisateur"
}
