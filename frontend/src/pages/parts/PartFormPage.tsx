import { useEffect, useState } from "react"
import type { FormEvent } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { ArrowLeft, Save } from "lucide-react"

import { createPart, getPart, updatePart } from "@/api/parts"
import { getPartTypes } from "@/api/configuration"
import { getVehicles } from "@/api/vehicles"
import { FormDocumentsField } from "@/components/form-documents-field"
import { LabelText } from "@/components/page-primitives"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { NativeSelect } from "@/components/ui/native-select"
import { Textarea } from "@/components/ui/textarea"
import type { ConfigurationItem } from "@/types/configuration"
import type { Part, PartPayload } from "@/types/part"
import type { Vehicle } from "@/types/vehicle"
import {
  createDocumentFileInput,
  FILE_TOO_LARGE_MESSAGE,
  hasTooLargeDocument,
  selectedDocumentFiles,
  type DocumentFileInput,
  uploadParentDocuments,
} from "@/lib/form-documents"
import { vehicleDisplayName } from "@/lib/part-utils"

const emptyForm = {
  partTypeId: "",
  quantity: "0",
  vehicleIds: [] as string[],
  note: "",
}

const SAVE_ERROR = [
  "Impossible d’enregistrer le stock.",
  "Vérifiez les champs saisis.",
].join(" ")

const DESTRUCTIVE_MESSAGE_CLASS =
  "rounded-lg border border-destructive/30 bg-destructive/10 p-4 " +
  "text-sm text-destructive"

export default function PartFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const isEditing = Boolean(id)
  const showDocumentFields = !isEditing
  const [form, setForm] = useState(emptyForm)
  const [documentFiles, setDocumentFiles] = useState<DocumentFileInput[]>([
    createDocumentFileInput(),
  ])
  const [partTypes, setPartTypes] = useState<ConfigurationItem[]>([])
  const [vehicles, setVehicles] = useState<Vehicle[]>([])
  const [isLoading, setIsLoading] = useState(isEditing)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let ignore = false
    async function load() {
      try {
        const [part, types, vehicleData] = await Promise.all([
          id ? getPart(id) : Promise.resolve(null),
          getPartTypes(),
          getVehicles(),
        ])
        if (ignore) return
        setPartTypes(types)
        setVehicles(vehicleData)
        if (part) setForm(partToForm(part))
      } catch {
        if (!ignore) setError("Impossible de charger le formulaire.")
      } finally {
        if (!ignore) setIsLoading(false)
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
      const saved = id
        ? await updatePart(id, formToPayload(form))
        : await createPart(formToPayload(form))

      if (showDocumentFields) {
        await uploadParentDocuments(
          { type: "parts", id: saved.id },
          selectedDocumentFiles(documentFiles),
        )
      }

      navigate(`/parts/${saved.id}`)
    } catch {
      setError(SAVE_ERROR)
    } finally {
      setIsSaving(false)
    }
  }

  function toggleVehicle(vehicleId: string) {
    setForm((current) => ({
      ...current,
      vehicleIds: current.vehicleIds.includes(vehicleId)
        ? current.vehicleIds.filter((id) => id !== vehicleId)
        : [...current.vehicleIds, vehicleId],
    }))
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
      <div
        className={
          "flex flex-col gap-3 sm:flex-row sm:items-start " +
          "sm:justify-between"
        }
      >
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">
            {isEditing ? "Modifier le stock" : "Ajouter un stock"}
          </h1>
          <p className="text-sm text-muted-foreground">
            Gérez la quantité et les véhicules compatibles.
          </p>
        </div>
        <Button variant="outline" asChild>
          <Link to={id ? `/parts/${id}` : "/parts"}>
            <ArrowLeft />
            Retour
          </Link>
        </Button>
      </div>

      {error && (
        <div className={DESTRUCTIVE_MESSAGE_CLASS}>
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <Card>
          <CardHeader>
            <CardTitle>Informations</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            <NativeSelect
              label="Type de pièce"
              value={form.partTypeId}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  partTypeId: event.target.value,
                }))
              }
              required
              options={partTypes.map((type) => ({
                value: String(type.id),
                label: type.name,
              }))}
              placeholder="Sélectionner"
            />
            <label className="grid gap-1.5 text-sm font-medium">
              <LabelText label="Quantité" required />
              <Input
                type="number"
                min="0"
                value={form.quantity}
                onChange={(event) =>
                  setForm((current) => ({
                    ...current,
                    quantity: event.target.value,
                  }))
                }
                required
              />
            </label>
            <label className="grid gap-1.5 text-sm font-medium md:col-span-2">
              <span>Note</span>
              <Textarea
                value={form.note}
                onChange={(event) =>
                  setForm((current) => ({
                    ...current,
                    note: event.target.value,
                  }))
                }
              />
            </label>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Véhicules compatibles</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-2 md:grid-cols-2">
            {vehicles.map((vehicle) => {
              const value = String(vehicle.id)
              return (
                <label
                  key={vehicle.id}
                  className={
                    "flex items-center gap-2 rounded-lg border p-3 " +
                    "text-sm"
                  }
                >
                  <input
                    type="checkbox"
                    checked={form.vehicleIds.includes(value)}
                    onChange={() => toggleVehicle(value)}
                  />
                  <span>
                    {vehicleDisplayName(vehicle)} ・{" "}
                    {vehicle.registration.toUpperCase()}
                  </span>
                </label>
              )
            })}
          </CardContent>
        </Card>

        {showDocumentFields && (
          <FormDocumentsField
            canEdit
            documentFiles={documentFiles}
            isSaving={isSaving}
            setDocumentFiles={setDocumentFiles}
          />
        )}

        <div className="flex justify-end gap-2">
          <Button variant="outline" asChild>
            <Link to={id ? `/parts/${id}` : "/parts"}>Annuler</Link>
          </Button>
          <Button type="submit" disabled={isSaving}>
            <Save />
            {isSaving ? "Enregistrement..." : "Enregistrer"}
          </Button>
        </div>
      </form>
    </div>
  )
}

function partToForm(part: Part) {
  return {
    partTypeId: String(part.partType.id),
    quantity: String(part.quantity),
    vehicleIds: part.vehicles.map((vehicle) => String(vehicle.id)),
    note: part.note ?? "",
  }
}

function formToPayload(form: typeof emptyForm): PartPayload {
  return {
    partType: `/api/part_types/${form.partTypeId}`,
    quantity: Number(form.quantity),
    vehicles: form.vehicleIds.map((id) => `/api/vehicles/${id}`),
    note: form.note || null,
  }
}
