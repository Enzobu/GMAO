import { useEffect, useState } from "react"
import type { FormEvent } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { ArrowLeft, Check, Save } from "lucide-react"

import { createPart, getPart, getParts, updatePart, updatePartQuantity } from "@/api/parts"
import { getPartTypes } from "@/api/configuration"
import { getVehicles } from "@/api/vehicles"
import { FormDocumentsField } from "@/components/form-documents-field"
import { FormPagePlaceholder } from "@/components/loading-placeholders"
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
import { cn } from "@/lib/utils"

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
  const [conflicts, setConflicts] = useState<Part[]>([])
  const [typeSearch, setTypeSearch] = useState("")

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
      const existingParts = await getParts()
      const overlapping = existingParts.filter((part) => (
        part.id !== Number(id)
        && part.partType.id === Number(form.partTypeId)
        && part.vehicles.some((vehicle) => (
          form.vehicleIds.includes(String(vehicle.id))
        ))
      ))
      setConflicts(overlapping)
      if (overlapping.length > 0) {
        setError("Ce type de pièce est déjà présent pour ce véhicule. Réapprovisionnez le stock existant.")
        return
      }

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

  function selectAllVehicles() {
    setForm((current) => ({
      ...current,
      vehicleIds: vehicles.map((vehicle) => String(vehicle.id)),
    }))
  }

  function deselectAllVehicles() {
    setForm((current) => ({ ...current, vehicleIds: [] }))
  }

  if (isLoading) {
    return <FormPagePlaceholder sections={2} />
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
          {conflicts.map((part) => (
            <div key={part.id} className="mt-2 flex items-center gap-3">
              <Link className="underline" to={`/parts/${part.id}`}>
                Voir le stock existant : {part.partType.name}
              </Link>
              {!isEditing && conflicts.length === 1 && Number(form.quantity) > 0 && (
                <Button
                  type="button"
                  variant="outline"
                  disabled={isSaving}
                  onClick={async () => {
                    setIsSaving(true)
                    try {
                      await updatePartQuantity(
                        part.id,
                        part.quantity + Number(form.quantity),
                      )
                      navigate(`/parts/${part.id}`)
                    } catch {
                      setError("Impossible de réapprovisionner cette pièce.")
                    } finally {
                      setIsSaving(false)
                    }
                  }}
                >
                  Ajouter {form.quantity} au stock existant
                </Button>
              )}
            </div>
          ))}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <Card>
          <CardHeader>
            <CardTitle>Informations</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            <label className="grid gap-1.5 text-sm font-medium">
              Rechercher un type
              <Input value={typeSearch} onChange={(event) => setTypeSearch(event.target.value)} placeholder="Nom de la pièce..." />
            </label>
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
              options={partTypes.filter((type) => !typeSearch || type.name.toLocaleLowerCase("fr").includes(typeSearch.toLocaleLowerCase("fr")) || String(type.id) === form.partTypeId).map((type) => ({
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
          <CardHeader className="flex flex-wrap items-center justify-between gap-3">
            <CardTitle>Véhicules compatibles</CardTitle>
            <div className="flex flex-wrap gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={isSaving || vehicles.length === 0}
                onClick={selectAllVehicles}
              >
                Tout sélectionner
              </Button>
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={isSaving || form.vehicleIds.length === 0}
                onClick={deselectAllVehicles}
              >
                Tout désélectionner
              </Button>
            </div>
          </CardHeader>
          <CardContent className="grid gap-2 md:grid-cols-2">
            {vehicles.map((vehicle) => {
              const value = String(vehicle.id)
              const selected = form.vehicleIds.includes(value)

              return (
                <button
                  key={vehicle.id}
                  type="button"
                  aria-pressed={selected}
                  disabled={isSaving}
                  onClick={() => toggleVehicle(value)}
                  className={cn(
                    "flex min-h-12 items-center justify-between gap-3",
                    "rounded-lg border px-3 py-3 text-left text-sm",
                    "transition-colors hover:border-primary/40",
                    "focus-visible:outline-none focus-visible:ring-2",
                    "focus-visible:ring-ring/50 disabled:opacity-50",
                    selected
                      ? "border-primary/50 bg-primary/10 hover:bg-primary/15"
                      : "border-border bg-background hover:bg-muted/50",
                  )}
                >
                  <span className="min-w-0">
                    <span className="font-medium">{vehicleDisplayName(vehicle)}</span>
                    <span className="mx-2 text-muted-foreground">·</span>
                    <span className="text-muted-foreground">
                      {vehicle.registration.toUpperCase()}
                    </span>
                  </span>
                  {selected && <Check className="size-4 shrink-0 text-primary" />}
                </button>
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
