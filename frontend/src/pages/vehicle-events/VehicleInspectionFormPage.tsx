import { useEffect, useMemo, useState } from "react"
import type { FormEvent } from "react"
import { useNavigate, useParams } from "react-router-dom"

import { createVehicleInspection, getInspectionCenters, getVehicleInspection, updateVehicleInspection } from "@/api/vehicle-events"
import { getVehicle } from "@/api/vehicles"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { NativeSelect } from "@/components/ui/native-select"
import { Textarea } from "@/components/ui/textarea"
import { useAuthStore } from "@/stores/auth-store"
import type { Vehicle } from "@/types/vehicle"
import type { InspectionCenter, VehicleInspectionEvent, VehicleInspectionPayload } from "@/types/vehicle-events"
import { INSPECTION_RESULTS } from "@/lib/vehicle-events"
import { ErrorMessage, Field, FormActions, VehicleEventHeader, vehicleDescription, WarningMessage } from "./components"

const emptyForm = {
  inspectionDate: "",
  validUntil: "",
  mileage: "",
  result: "pass",
  counterVisitRequired: "false",
  counterVisitDueAt: "",
  notes: "",
  centerId: "",
}

type InspectionFormState = typeof emptyForm

export default function VehicleInspectionFormPage() {
  const { vehicleId, inspectionId } = useParams()
  const navigate = useNavigate()
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false
  const isEditing = Boolean(inspectionId)

  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [inspection, setInspection] = useState<VehicleInspectionEvent | null>(null)
  const [centers, setCenters] = useState<InspectionCenter[]>([])
  const [form, setForm] = useState<InspectionFormState>(emptyForm)
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let ignore = false

    async function loadFormData() {
      if (!vehicleId) {
        return
      }

      try {
        const [vehicleData, inspectionData, centerData] = await Promise.all([
          getVehicle(vehicleId),
          inspectionId ? getVehicleInspection(inspectionId) : Promise.resolve(null),
          getInspectionCenters().catch(() => []),
        ])

        if (ignore) {
          return
        }

        setVehicle(vehicleData)
        setInspection(inspectionData)
        setCenters(centerData)

        if (inspectionData) {
          setForm(inspectionToForm(inspectionData))
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger le contrôle technique.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    loadFormData()

    return () => {
      ignore = true
    }
  }, [vehicleId, inspectionId])

  const canEdit = useMemo(
    () => isAdmin || vehicle?.user.id === user?.id,
    [isAdmin, vehicle?.user.id, user?.id]
  )

  function updateField(field: keyof InspectionFormState, value: string) {
    setForm((current) => ({ ...current, [field]: value }))
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!vehicleId || !canEdit) {
      return
    }

    setIsSaving(true)
    setError(null)

    try {
      const payload = formToPayload(form, vehicleId)
      const saved = inspectionId
        ? await updateVehicleInspection(inspectionId, payload)
        : await createVehicleInspection(payload)

      navigate(`/vehicles/${vehicleId}/inspections/${saved.id}`)
    } catch {
      setError("Impossible d’enregistrer le contrôle technique. Vérifiez les champs saisis.")
    } finally {
      setIsSaving(false)
    }
  }

  if (isLoading) {
    return <div className="text-sm text-muted-foreground">Chargement du contrôle...</div>
  }

  if (error && isEditing && !inspection) {
    return <ErrorMessage>{error}</ErrorMessage>
  }

  return (
    <div className="space-y-6">
      <VehicleEventHeader
        title={isEditing ? "Modifier le contrôle technique" : "Ajouter un contrôle technique"}
        description={vehicleDescription(vehicle)}
        backTo={inspectionId ? `/vehicles/${vehicleId}/inspections/${inspectionId}` : `/vehicles/${vehicleId}/inspections`}
      />

      {!canEdit && (
        <WarningMessage>
          Seul le propriétaire ou un administrateur peut modifier ce contrôle technique.
        </WarningMessage>
      )}

      {error && <ErrorMessage>{error}</ErrorMessage>}

      <form onSubmit={handleSubmit} className="space-y-4">
        <Card>
          <CardHeader>
            <CardTitle>Informations</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            <Field
              label="Date du contrôle"
              type="date"
              value={form.inspectionDate}
              onChange={(value) => updateField("inspectionDate", value)}
              required
              disabled={!canEdit}
            />
            <Field
              label="Valide jusqu’au"
              type="date"
              value={form.validUntil}
              onChange={(value) => updateField("validUntil", value)}
              disabled={!canEdit}
            />
            <Field
              label="Kilométrage"
              type="number"
              value={form.mileage}
              onChange={(value) => updateField("mileage", value)}
              disabled={!canEdit}
            />
            <NativeSelect
              label="Résultat"
              value={form.result}
              options={INSPECTION_RESULTS}
              onChange={(event) => updateField("result", event.target.value)}
              required
              disabled={!canEdit}
            />
            <NativeSelect
              label="Contre-visite"
              value={form.counterVisitRequired}
              options={COUNTER_VISIT_OPTIONS}
              onChange={(event) => updateField("counterVisitRequired", event.target.value)}
              required
              disabled={!canEdit}
            />
            <Field
              label="Contre-visite avant"
              type="date"
              value={form.counterVisitDueAt}
              onChange={(value) => updateField("counterVisitDueAt", value)}
              disabled={!canEdit}
            />
            <NativeSelect
              label="Centre"
              value={form.centerId}
              options={centerOptions(centers)}
              onChange={(event) => updateField("centerId", event.target.value)}
              disabled={!canEdit}
              placeholder="—"
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Notes</CardTitle>
          </CardHeader>
          <CardContent>
            <Textarea
              value={form.notes}
              onChange={(event) => updateField("notes", event.target.value)}
              disabled={!canEdit}
              rows={5}
            />
          </CardContent>
        </Card>

        <FormActions
          cancelTo={inspectionId ? `/vehicles/${vehicleId}/inspections/${inspectionId}` : `/vehicles/${vehicleId}/inspections`}
          canEdit={canEdit}
          isSaving={isSaving}
        />
      </form>
    </div>
  )
}

function inspectionToForm(inspection: VehicleInspectionEvent): InspectionFormState {
  return {
    inspectionDate: inspection.inspectionDate?.slice(0, 10) ?? "",
    validUntil: inspection.validUntil?.slice(0, 10) ?? "",
    mileage: inspection.mileage != null ? String(inspection.mileage) : "",
    result: inspection.result ?? "pass",
    counterVisitRequired: String(inspection.counterVisitRequired),
    counterVisitDueAt: inspection.counterVisitDueAt?.slice(0, 10) ?? "",
    notes: inspection.notes ?? "",
    centerId: inspection.center ? String(inspection.center.id) : "",
  }
}

function formToPayload(form: InspectionFormState, vehicleId: string): VehicleInspectionPayload {
  return {
    vehicle: `/api/vehicles/${vehicleId}`,
    inspectionDate: form.inspectionDate,
    validUntil: form.validUntil || null,
    mileage: form.mileage ? Number(form.mileage) : null,
    result: form.result as VehicleInspectionPayload["result"],
    counterVisitRequired: form.counterVisitRequired === "true",
    counterVisitDueAt: form.counterVisitDueAt || null,
    notes: form.notes || null,
    center: form.centerId ? `/api/inspection_centers/${form.centerId}` : null,
  }
}

function centerOptions(centers: InspectionCenter[]) {
  return centers.map((center) => ({ value: String(center.id), label: center.name }))
}

const COUNTER_VISIT_OPTIONS = [
  { value: "false", label: "Non requise" },
  { value: "true", label: "Requise" },
]
