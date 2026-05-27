import { useEffect, useMemo, useState } from "react"
import type { ComponentProps, FormEvent } from "react"
import { isAxiosError } from "axios"
import { Link, useNavigate, useParams } from "react-router-dom"
import { Plus, Save, Trash2 } from "lucide-react"

import { getMaintenanceTypes } from "@/api/configuration"
import { createIntervention, getIntervention, updateIntervention } from "@/api/interventions"
import { getParts } from "@/api/parts"
import { getVehicle } from "@/api/vehicles"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { NativeSelect } from "@/components/ui/native-select"
import { Textarea } from "@/components/ui/textarea"
import { useAuthStore } from "@/stores/auth-store"
import type { ConfigurationItem } from "@/types/configuration"
import type { Intervention, InterventionPayload, InterventionStatus } from "@/types/intervention"
import type { Part } from "@/types/part"
import type { Vehicle } from "@/types/vehicle"
import { INTERVENTION_STATUSES, vehicleDisplayName } from "@/lib/intervention-utils"
import { ErrorMessage, InterventionHeader } from "./components"

const emptyForm = {
  maintenanceTypeId: "",
  mileage: "",
  plannedAt: "",
  startedAt: "",
  finishedAt: "",
  status: "todo" as InterventionStatus,
  isExternal: "false",
  notes: "",
  nextDueMileage: "",
  nextDueAt: "",
  parts: [] as InterventionPartForm[],
}

interface InterventionPartForm {
  id?: number
  partId: string
  quantity: string
  notes: string
}

export default function InterventionFormPage() {
  const { vehicleId, interventionId } = useParams()
  const navigate = useNavigate()
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false
  const isEditing = Boolean(interventionId)
  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [intervention, setIntervention] = useState<Intervention | null>(null)
  const [types, setTypes] = useState<ConfigurationItem[]>([])
  const [parts, setParts] = useState<Part[]>([])
  const [form, setForm] = useState(emptyForm)
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [restoreDialogOpen, setRestoreDialogOpen] = useState(false)
  const [mileageDialogOpen, setMileageDialogOpen] = useState(false)
  const [mileageMessage, setMileageMessage] = useState("")

  useEffect(() => {
    let ignore = false

    async function load() {
      if (!vehicleId) return
      try {
        const [vehicleData, interventionData, typeData, partData] = await Promise.all([
          getVehicle(vehicleId),
          interventionId ? getIntervention(interventionId) : Promise.resolve(null),
          getMaintenanceTypes(),
          getParts(),
        ])
        if (ignore) return
        setVehicle(vehicleData)
        setIntervention(interventionData)
        setTypes(typeData)
        setParts(partData)
        if (interventionData) {
          setForm(interventionToForm(interventionData))
        } else if (typeData[0]) {
          setForm((current) => ({ ...current, maintenanceTypeId: String(typeData[0].id) }))
        }
      } catch {
        if (!ignore) setError("Impossible de charger le formulaire.")
      } finally {
        if (!ignore) setIsLoading(false)
      }
    }

    load()
    return () => { ignore = true }
  }, [vehicleId, interventionId])

  const canEdit = useMemo(() => isAdmin || vehicle?.user.id === user?.id, [isAdmin, vehicle?.user.id, user?.id])
  const isCompleted = form.status === "completed"
  const canEditStartDate = form.status === "in_progress" || form.status === "completed"
  const compatibleParts = useMemo(() => vehicle ? parts.filter((part) => isPartCompatibleWithVehicle(part, vehicle.id)) : [], [parts, vehicle])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (shouldAskStockRestore()) {
      setRestoreDialogOpen(true)
      return
    }
    await save(false)
  }

  async function save(forceMileage: boolean) {
    if (!vehicleId || !canEdit) return
    if (!form.maintenanceTypeId) {
      setError("Sélectionnez un type d’intervention.")
      return
    }
    if (isCompleted && !form.finishedAt) {
      setError("Saisissez la date de fin pour terminer l’intervention.")
      return
    }
    if (isCompleted && !form.mileage) {
      setError("Saisissez le kilométrage pour terminer l’intervention.")
      return
    }
    if (form.parts.some((line) => !line.partId)) {
      setError("Sélectionnez une pièce pour chaque ligne de pièces utilisées.")
      return
    }
    if (form.parts.some((line) => !compatibleParts.some((part) => String(part.id) === line.partId))) {
      setError("Une pièce sélectionnée n’est pas compatible avec ce véhicule.")
      return
    }

    setIsSaving(true)
    setError(null)
    try {
      const payload = formToPayload(form, vehicleId)
      const saved = interventionId
        ? await updateIntervention(interventionId, payload, forceMileage)
        : await createIntervention(payload, forceMileage)
      navigate(`/vehicles/${vehicleId}/interventions/${saved.id}`)
    } catch (caught) {
      if (isAxiosError(caught) && caught.response?.status === 409) {
        setMileageMessage(errorMessage(caught))
        setMileageDialogOpen(true)
      } else {
        setError(errorMessage(caught))
      }
    } finally {
      setIsSaving(false)
    }
  }

  function shouldAskStockRestore() {
    return Boolean(intervention?.finishedAt && (!isCompleted || !form.finishedAt) && intervention.maintenanceParts?.length)
  }

  function updateField<K extends keyof typeof emptyForm>(field: K, value: (typeof emptyForm)[K]) {
    setForm((current) => ({ ...current, [field]: value }))
  }

  function updateStatus(status: InterventionStatus) {
    setForm((current) => ({
      ...current,
      status,
      startedAt: status === "in_progress" || status === "completed" ? current.startedAt : "",
      finishedAt: status === "completed" ? current.finishedAt : "",
      mileage: status === "completed" ? current.mileage : "",
    }))
  }

  function addPartLine() {
    setForm((current) => ({
      ...current,
      parts: [...current.parts, { partId: compatibleParts[0] ? String(compatibleParts[0].id) : "", quantity: "1", notes: "" }],
    }))
  }

  function updatePartLine(index: number, line: InterventionPartForm) {
    setForm((current) => ({ ...current, parts: current.parts.map((item, itemIndex) => itemIndex === index ? line : item) }))
  }

  function removePartLine(index: number) {
    setForm((current) => ({ ...current, parts: current.parts.filter((_, itemIndex) => itemIndex !== index) }))
  }

  if (isLoading) return <div className="text-sm text-muted-foreground">Chargement du formulaire...</div>
  if (error && isEditing && !intervention) return <ErrorMessage>{error}</ErrorMessage>

  return (
    <div className="space-y-6">
      <StockRestoreDialog
        open={restoreDialogOpen}
        isLoading={isSaving}
        onOpenChange={setRestoreDialogOpen}
        onConfirm={() => { setRestoreDialogOpen(false); void save(false) }}
      />
      <MileageDialog
        open={mileageDialogOpen}
        message={mileageMessage}
        isAdmin={isAdmin}
        isLoading={isSaving}
        onOpenChange={setMileageDialogOpen}
        onForce={() => { setMileageDialogOpen(false); void save(true) }}
      />

      <InterventionHeader
        title={isEditing ? "Modifier l’intervention" : "Ajouter une intervention"}
        description={vehicle ? vehicleDisplayName(vehicle) : undefined}
        backTo={interventionId ? `/vehicles/${vehicleId}/interventions/${interventionId}` : `/vehicles/${vehicleId}/interventions`}
      />

      {!canEdit && <ErrorMessage>Vous pouvez consulter cette intervention, mais seul le propriétaire ou un administrateur peut la modifier.</ErrorMessage>}
      {error && <ErrorMessage>{error}</ErrorMessage>}

      <form onSubmit={handleSubmit} className="space-y-4">
        <Card>
          <CardHeader><CardTitle>Informations</CardTitle></CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            <NativeSelect
              label="Type"
              value={form.maintenanceTypeId}
              options={types.map((type) => ({ value: String(type.id), label: type.name }))}
              onChange={(event) => updateField("maintenanceTypeId", event.target.value)}
              required
              disabled={!canEdit}
            />
            <NativeSelect
              label="Statut"
              value={form.status}
              options={INTERVENTION_STATUSES}
              onChange={(event) => updateStatus(event.target.value as InterventionStatus)}
              required
              disabled={!canEdit}
            />
            <Field
              label="Kilométrage"
              type="number"
              value={form.mileage}
              onChange={(value) => updateField("mileage", value)}
              disabled={!canEdit || !isCompleted}
              required={isCompleted}
            />
            <NativeSelect
              label="Mode"
              value={form.isExternal}
              options={MODE_OPTIONS}
              onChange={(event) => updateField("isExternal", event.target.value)}
              disabled={!canEdit}
            />
            <Field label="Date prévue" type="datetime-local" value={form.plannedAt} onChange={(value) => updateField("plannedAt", value)} disabled={!canEdit} />
            <Field label="Date de début" type="datetime-local" value={form.startedAt} onChange={(value) => updateField("startedAt", value)} disabled={!canEdit || !canEditStartDate} />
            <Field label="Date de fin" type="datetime-local" value={form.finishedAt} onChange={(value) => updateField("finishedAt", value)} disabled={!canEdit || !isCompleted} required={isCompleted} />
            <Field label="Prochaine échéance km" type="number" value={form.nextDueMileage} onChange={(value) => updateField("nextDueMileage", value)} disabled={!canEdit} />
            <Field label="Prochaine échéance date" type="datetime-local" value={form.nextDueAt} onChange={(value) => updateField("nextDueAt", value)} disabled={!canEdit} />
            <label className="grid gap-1.5 text-sm font-medium md:col-span-2">
              <span>Notes</span>
              <Textarea value={form.notes} onChange={(event) => updateField("notes", event.target.value)} disabled={!canEdit} />
            </label>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Pièces utilisées</CardTitle>
            <Button type="button" variant="outline" size="sm" onClick={addPartLine} disabled={!canEdit || compatibleParts.length === 0}>
              <Plus />
              Ajouter
            </Button>
          </CardHeader>
          <CardContent className="space-y-3">
            {compatibleParts.length === 0 && <div className="text-sm text-destructive">Aucune pièce compatible avec ce véhicule.</div>}
            {form.parts.length === 0 && <div className="text-sm text-muted-foreground">Aucune pièce ajoutée.</div>}
            {form.parts.map((line, index) => (
              <div key={`${line.id ?? "new"}-${index}`} className="grid gap-3 rounded-lg border p-3 md:grid-cols-[1fr_8rem_auto]">
                <NativeSelect
                  label="Pièce"
                  value={line.partId}
                  options={partOptions(partsForLine(compatibleParts, parts, line.partId))}
                  onChange={(event) => updatePartLine(index, { ...line, partId: event.target.value })}
                  required
                  disabled={!canEdit}
                />
                <Field
                  label="Quantité"
                  type="number"
                  min="1"
                  value={line.quantity}
                  onChange={(value) => updatePartLine(index, { ...line, quantity: value })}
                  required
                  disabled={!canEdit}
                />
                <Button type="button" variant="destructive" className="self-end" onClick={() => removePartLine(index)} disabled={!canEdit}><Trash2 />Retirer</Button>
              </div>
            ))}
          </CardContent>
        </Card>

        <div className="flex justify-end gap-2">
          <Button variant="outline" asChild>
            <Link to={interventionId ? `/vehicles/${vehicleId}/interventions/${interventionId}` : `/vehicles/${vehicleId}/interventions`}>
              Annuler
            </Link>
          </Button>
          <Button type="submit" disabled={!canEdit || isSaving}><Save />{isSaving ? "Enregistrement..." : "Enregistrer"}</Button>
        </div>
      </form>
    </div>
  )
}

function Field({
  label,
  value,
  onChange,
  ...props
}: {
  label: string
  value: string
  onChange: (value: string) => void
} & Omit<ComponentProps<typeof Input>, "value" | "onChange">) {
  return (
    <label className="grid gap-1.5 text-sm font-medium">
      <span>{label}</span>
      <Input value={value} onChange={(event) => onChange(event.target.value)} {...props} />
    </label>
  )
}

function interventionToForm(intervention: Intervention) {
  return {
    maintenanceTypeId: String(intervention.maintenanceType.id),
    mileage: intervention.mileage != null ? String(intervention.mileage) : "",
    plannedAt: dateTimeInput(intervention.plannedAt),
    startedAt: dateTimeInput(intervention.startedAt),
    finishedAt: dateTimeInput(intervention.finishedAt),
    status: intervention.status,
    isExternal: String(Boolean(intervention.isExternal)),
    notes: intervention.notes ?? "",
    nextDueMileage: intervention.nextDueMileage != null ? String(intervention.nextDueMileage) : "",
    nextDueAt: dateTimeInput(intervention.nextDueAt),
    parts: intervention.maintenanceParts?.map((line) => ({
      id: line.id,
      partId: partId(line.part),
      quantity: String(line.quantity),
      notes: line.notes ?? "",
    })) ?? [],
  }
}

function formToPayload(form: typeof emptyForm, vehicleId: string): InterventionPayload {
  return {
    vehicle: `/api/vehicles/${vehicleId}`,
    maintenanceType: `/api/maintenance_types/${form.maintenanceTypeId}`,
    mileage: form.status === "completed" && form.mileage ? Number(form.mileage) : null,
    plannedAt: toApiDateTime(form.plannedAt),
    startedAt: form.status === "in_progress" || form.status === "completed" ? toApiDateTime(form.startedAt) : null,
    finishedAt: form.status === "completed" ? toApiDateTime(form.finishedAt) : null,
    status: form.status,
    isExternal: form.isExternal === "true",
    notes: form.notes || null,
    nextDueMileage: form.nextDueMileage ? Number(form.nextDueMileage) : null,
    nextDueAt: toApiDateTime(form.nextDueAt),
    maintenanceParts: form.parts
      .filter((line) => line.partId)
      .map((line) => ({
        id: line.id,
        part: `/api/parts/${line.partId}`,
        quantity: Number(line.quantity),
        notes: line.notes || null,
      })),
  }
}

function dateTimeInput(value?: string | null) {
  return value ? value.slice(0, 16) : ""
}

function partId(part: Intervention["maintenanceParts"] extends (infer T)[] | undefined ? T extends { part: infer P } ? P : never : never) {
  if (typeof part === "string") {
    return part.split("/").filter(Boolean).at(-1) ?? ""
  }

  return part.id ? String(part.id) : ""
}

function toApiDateTime(value: string) {
  return value ? new Date(value).toISOString() : null
}

function errorMessage(error: unknown) {
  if (isAxiosError(error)) {
    const data = error.response?.data as { detail?: string; description?: string; message?: string } | undefined
    return data?.detail ?? data?.description ?? data?.message ?? "Impossible d’enregistrer l’intervention."
  }
  return "Impossible d’enregistrer l’intervention."
}

function partOptions(parts: Part[]) {
  return parts.map((part) => ({ value: String(part.id), label: `${part.partType.name} ・ stock ${part.quantity}` }))
}

function isPartCompatibleWithVehicle(part: Part, vehicleId: number) {
  return part.vehicles.some((vehicle) => vehicle.id === vehicleId)
}

function partsForLine(compatibleParts: Part[], allParts: Part[], selectedPartId: string) {
  if (!selectedPartId || compatibleParts.some((part) => String(part.id) === selectedPartId)) {
    return compatibleParts
  }

  const selectedPart = allParts.find((part) => String(part.id) === selectedPartId)
  return selectedPart ? [selectedPart, ...compatibleParts] : compatibleParts
}

function StockRestoreDialog({
  open,
  isLoading,
  onOpenChange,
  onConfirm,
}: {
  open: boolean
  isLoading: boolean
  onOpenChange: (open: boolean) => void
  onConfirm: () => void
}) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Restaurer les pièces en stock ?</DialogTitle>
          <DialogDescription>
            Vous retirez la date de réalisation. Les pièces consommées par cette intervention seront restaurées en stock.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={isLoading}>Annuler</Button>
          <Button onClick={onConfirm} disabled={isLoading}>{isLoading ? "Enregistrement..." : "Confirmer"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

function MileageDialog({
  open,
  message,
  isAdmin,
  isLoading,
  onOpenChange,
  onForce,
}: {
  open: boolean
  message: string
  isAdmin: boolean
  isLoading: boolean
  onOpenChange: (open: boolean) => void
  onForce: () => void
}) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Kilométrage à vérifier</DialogTitle>
          <DialogDescription>{message}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={isLoading}>Fermer</Button>
          {isAdmin && <Button onClick={onForce} disabled={isLoading}>{isLoading ? "Enregistrement..." : "Forcer"}</Button>}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

const MODE_OPTIONS = [
  { value: "false", label: "Interne" },
  { value: "true", label: "Externe" },
]
