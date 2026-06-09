import { forwardRef, useEffect, useMemo, useRef, useState } from "react"
import type { FormEvent } from "react"
import { isAxiosError } from "axios"
import { useNavigate, useParams } from "react-router-dom"

import {
  createVehicleInspection,
  getInspectionCenters,
  getVehicleInspection,
  updateVehicleInspection,
} from "@/api/vehicle-events"
import { getVehicle } from "@/api/vehicles"
import { FormDocumentsField } from "@/components/form-documents-field"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { NativeSelect } from "@/components/ui/native-select"
import { Textarea } from "@/components/ui/textarea"
import { useAuthStore } from "@/stores/auth-store"
import type { Vehicle } from "@/types/vehicle"
import type {
  InspectionCenter,
  VehicleInspectionEvent,
  VehicleInspectionPayload,
} from "@/types/vehicle-events"
import { MIN_INPUT_DATE, MAX_INPUT_DATE } from "@/lib/date-limits"
import {
  createDocumentFileInput,
  FILE_TOO_LARGE_MESSAGE,
  hasTooLargeDocument,
  PDF_COMPRESSOR_URL,
  selectedDocumentFiles,
  type DocumentFileInput,
  uploadParentDocuments,
} from "@/lib/form-documents"
import { INSPECTION_RESULTS } from "@/lib/vehicle-events"
import {
  ErrorMessage,
  Field,
  FormActions,
  MileageWarningDialog,
  VehicleEventHeader,
  WarningMessage,
} from "./components"
import { vehicleDescription } from "./utils"

const ERROR_SCROLL_OFFSET = 112

const emptyForm = {
  inspectionDate: "",
  validUntil: "",
  mileage: "",
  result: "pass",
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
  const showDocumentFields = !isEditing

  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [inspection, setInspection] =
    useState<VehicleInspectionEvent | null>(null)
  const [centers, setCenters] = useState<InspectionCenter[]>([])
  const [form, setForm] = useState<InspectionFormState>(emptyForm)
  const [documentFiles, setDocumentFiles] = useState<DocumentFileInput[]>([
    createDocumentFileInput(),
  ])
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const errorRef = useRef<HTMLDivElement>(null)
  const [mileageDialogOpen, setMileageDialogOpen] = useState(false)
  const [mileageMessage, setMileageMessage] = useState("")

  useEffect(() => {
    let ignore = false

    async function loadFormData() {
      if (!vehicleId) {
        return
      }

      try {
        const [vehicleData, inspectionData, centerData] = await Promise.all([
          getVehicle(vehicleId),
          inspectionId
            ? getVehicleInspection(inspectionId)
            : Promise.resolve(null),
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

  useEffect(() => {
    if (!error) {
      return
    }

    const errorElement = errorRef.current

    if (!errorElement) {
      return
    }

    const top = errorElement.getBoundingClientRect().top
      + window.scrollY
      - ERROR_SCROLL_OFFSET

    window.scrollTo({
      behavior: "smooth",
      top: Math.max(0, top),
    })
  }, [error])

  const canEdit = useMemo(
    () => isAdmin || vehicle?.user.id === user?.id,
    [isAdmin, vehicle?.user.id, user?.id]
  )

  function updateField(field: keyof InspectionFormState, value: string) {
    setForm((current) => ({ ...current, [field]: value }))
  }

  function updateResult(value: string) {
    setForm((current) => ({
      ...current,
      result: value,
      counterVisitDueAt:
        isCounterVisitRequired(value) && !current.counterVisitDueAt
          ? defaultCounterVisitDueAt(current.inspectionDate)
          : "",
    }))
  }

  function updateInspectionDate(value: string) {
    setForm((current) => {
      const previousDefault = defaultValidUntil(current.inspectionDate)
      const previousCounterVisitDefault = defaultCounterVisitDueAt(
        current.inspectionDate
      )

      return {
        ...current,
        inspectionDate: value,
        validUntil:
          !current.validUntil || current.validUntil === previousDefault
            ? defaultValidUntil(value)
            : current.validUntil,
        counterVisitDueAt:
          isCounterVisitRequired(current.result)
          && (!current.counterVisitDueAt
            || current.counterVisitDueAt === previousCounterVisitDefault)
            ? defaultCounterVisitDueAt(value)
            : current.counterVisitDueAt,
      }
    })
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!vehicleId || !canEdit) {
      return
    }

    await save(false)
  }

  async function save(forceMileage: boolean) {
    if (!vehicleId || !canEdit) {
      return
    }

    setError(null)

    if (showDocumentFields && hasTooLargeDocument(documentFiles)) {
      setError(FILE_TOO_LARGE_MESSAGE)

      return
    }

    setIsSaving(true)

    try {
      const payload = formToPayload(form, vehicleId)
      const saved = inspectionId
        ? await updateVehicleInspection(inspectionId, payload, forceMileage)
        : await createVehicleInspection(payload, forceMileage)

      if (showDocumentFields) {
        await uploadParentDocuments(
          { type: "vehicle_inspections", id: saved.id },
          selectedDocumentFiles(documentFiles),
        )
      }

      navigate(`/vehicles/${vehicleId}/inspections/${saved.id}`)
    } catch (error_) {
      if (isAxiosError(error_) && error_.response?.status === 409) {
        setMileageMessage(errorMessage(error_))
        setMileageDialogOpen(true)
      } else {
        setError(errorMessage(error_))
      }
    } finally {
      setIsSaving(false)
    }
  }

  if (isLoading) {
    return (
      <div className="text-sm text-muted-foreground">
        Chargement du contrôle...
      </div>
    )
  }

  if (error && isEditing && !inspection) {
    return <ErrorMessage>{error}</ErrorMessage>
  }

  return (
    <div className="space-y-6">
      <MileageWarningDialog
        open={mileageDialogOpen}
        message={mileageMessage}
        isAdmin={isAdmin}
        isLoading={isSaving}
        onOpenChange={setMileageDialogOpen}
        onForce={() => {
          setMileageDialogOpen(false)
          void save(true)
        }}
      />

      <VehicleEventHeader
        title={
          isEditing
            ? "Modifier le contrôle technique"
            : "Ajouter un contrôle technique"
        }
        description={vehicleDescription(vehicle)}
        backTo={
          inspectionId
            ? `/vehicles/${vehicleId}/inspections/${inspectionId}`
            : `/vehicles/${vehicleId}/inspections`
        }
      />

      {!canEdit && (
        <WarningMessage>
          Seul le propriétaire ou un administrateur peut modifier ce contrôle
          technique.
        </WarningMessage>
      )}

      {error && (
        <InspectionFormErrorMessage ref={errorRef} message={error} />
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <Card>
          <CardHeader>
            <CardTitle>Informations</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            <Field
              label="Date du contrôle"
              type="date"
              min={MIN_INPUT_DATE}
              max={MAX_INPUT_DATE}
              value={form.inspectionDate}
              onChange={updateInspectionDate}
              required
              disabled={!canEdit}
            />
            <Field
              label="Valide jusqu’au"
              type="date"
              min={MIN_INPUT_DATE}
              max={MAX_INPUT_DATE}
              value={form.validUntil}
              onChange={(value) => updateField("validUntil", value)}
              disabled={!canEdit}
            />
            <Field
              label="Kilométrage"
              type="number"
              min="0"
              value={form.mileage}
              onChange={(value) => updateField("mileage", value)}
              required
              disabled={!canEdit}
            />
            <NativeSelect
              label="Résultat"
              value={form.result}
              options={INSPECTION_RESULTS}
              onChange={(event) => updateResult(event.target.value)}
              required
              disabled={!canEdit}
            />
            <Field
              label="Contre-visite avant"
              type="date"
              min={MIN_INPUT_DATE}
              max={MAX_INPUT_DATE}
              value={form.counterVisitDueAt}
              onChange={(value) => updateField("counterVisitDueAt", value)}
              disabled={!canEdit || !isCounterVisitRequired(form.result)}
              required={isCounterVisitRequired(form.result)}
            />
            <NativeSelect
              label="Centre"
              value={form.centerId}
              options={centerOptions(centers)}
              onChange={(event) => updateField("centerId", event.target.value)}
              required
              disabled={!canEdit}
              placeholder="Sélectionner"
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

        {showDocumentFields && (
          <FormDocumentsField
            canEdit={canEdit}
            documentFiles={documentFiles}
            isSaving={isSaving}
            setDocumentFiles={setDocumentFiles}
          />
        )}

        <FormActions
          cancelTo={
            inspectionId
              ? `/vehicles/${vehicleId}/inspections/${inspectionId}`
              : `/vehicles/${vehicleId}/inspections`
          }
          canEdit={canEdit}
          isSaving={isSaving}
        />
      </form>
    </div>
  )
}

const InspectionFormErrorMessage = forwardRef<
  HTMLDivElement,
  Readonly<{ message: string }>
>(function InspectionFormErrorMessage({ message }, ref) {
  return (
    <div ref={ref}>
      <ErrorMessage>
        {message}
        {message === FILE_TOO_LARGE_MESSAGE && (
          <>
            {" "}
            <a
              href={PDF_COMPRESSOR_URL}
              target="_blank"
              rel="noreferrer"
              className="font-semibold underline underline-offset-2"
            >
              Compresser PDF
            </a>
          </>
        )}
      </ErrorMessage>
    </div>
  )
})

function inspectionToForm(
  inspection: VehicleInspectionEvent
): InspectionFormState {
  const result = inspection.result ?? "pass"

  return {
    inspectionDate: inspection.inspectionDate?.slice(0, 10) ?? "",
    validUntil: inspection.validUntil?.slice(0, 10) ?? "",
    mileage: inspection.mileage == null ? "" : String(inspection.mileage),
    result,
    counterVisitDueAt: isCounterVisitRequired(result)
      ? inspection.counterVisitDueAt?.slice(0, 10) ?? ""
      : "",
    notes: inspection.notes ?? "",
    centerId: inspection.center ? String(inspection.center.id) : "",
  }
}

function formToPayload(
  form: InspectionFormState,
  vehicleId: string
): VehicleInspectionPayload {
  const payload: VehicleInspectionPayload = {
    vehicle: `/api/vehicles/${vehicleId}`,
    inspectionDate: form.inspectionDate,
    validUntil: form.validUntil || null,
    mileage: Number(form.mileage),
    result: form.result as VehicleInspectionPayload["result"],
    counterVisitDueAt: isCounterVisitRequired(form.result)
      ? form.counterVisitDueAt || null
      : null,
    notes: form.notes || null,
  }

  if (form.centerId) {
    payload.center = `/api/inspection_centers/${form.centerId}`
  }

  return payload
}

function centerOptions(centers: InspectionCenter[]) {
  return centers.map((center) => ({
    value: String(center.id),
    label: center.name,
  }))
}

function defaultValidUntil(value: string) {
  if (!value) {
    return ""
  }

  const date = new Date(`${value}T00:00:00`)
  date.setFullYear(date.getFullYear() + 2)
  date.setDate(date.getDate() - 1)

  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, "0")
  const day = String(date.getDate()).padStart(2, "0")

  return `${year}-${month}-${day}`
}

function defaultCounterVisitDueAt(value: string) {
  if (!value) {
    return ""
  }

  const date = new Date(`${value}T00:00:00`)
  date.setDate(date.getDate() + 14)

  return formatInputDate(date)
}

function formatInputDate(date: Date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, "0")
  const day = String(date.getDate()).padStart(2, "0")

  return `${year}-${month}-${day}`
}

function errorMessage(error: unknown) {
  if (isAxiosError(error)) {
    const data = error.response?.data as
      | { detail?: string; description?: string; message?: string }
      | undefined

    return (
      data?.detail
      ?? data?.description
      ?? data?.message
      ?? "Impossible d’enregistrer le contrôle technique. Vérifiez les "
        + "champs saisis."
    )
  }

  return "Impossible d’enregistrer le contrôle technique. Vérifiez les "
    + "champs saisis."
}

function isCounterVisitRequired(result: string) {
  return result !== "pass"
}
