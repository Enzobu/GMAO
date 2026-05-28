import { useEffect, useMemo, useState } from "react"
import type { FormEvent } from "react"
import { useNavigate, useParams } from "react-router-dom"

import {
  closeVehicleInsurance,
  createVehicleInsurance,
  getVehicleInsurance,
  getVehicleInsurances,
  updateVehicleInsurance,
} from "@/api/vehicle-events"
import { getVehicle } from "@/api/vehicles"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { NativeSelect } from "@/components/ui/native-select"
import { useAuthStore } from "@/stores/auth-store"
import type { Vehicle } from "@/types/vehicle"
import type { VehicleInsuranceEvent, VehicleInsurancePayload } from "@/types/vehicle-events"
import { formatDate, isInsuranceActive, PAYMENT_FREQUENCIES, todayInputValue } from "@/lib/vehicle-events"
import { ErrorMessage, Field, FormActions, VehicleEventHeader, vehicleDescription, WarningMessage } from "./components"

const emptyForm = {
  providerName: "",
  policyNumber: "",
  startDate: "",
  endDate: "",
  paymentFrequency: "yearly",
}

type InsuranceFormState = typeof emptyForm

export default function VehicleInsuranceFormPage() {
  const { vehicleId, insuranceId } = useParams()
  const navigate = useNavigate()
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false
  const isEditing = Boolean(insuranceId)

  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [insurance, setInsurance] = useState<VehicleInsuranceEvent | null>(null)
  const [previousActiveInsurance, setPreviousActiveInsurance] = useState<VehicleInsuranceEvent | null>(null)
  const [form, setForm] = useState<InsuranceFormState>(emptyForm)
  const [closePreviousEndDate, setClosePreviousEndDate] = useState(todayInputValue())
  const [isCloseDialogOpen, setIsCloseDialogOpen] = useState(false)
  const [isClosingPrevious, setIsClosingPrevious] = useState(false)
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
        const [vehicleData, insuranceData, insuranceDataCollection] = await Promise.all([
          getVehicle(vehicleId),
          insuranceId ? getVehicleInsurance(insuranceId) : Promise.resolve(null),
          insuranceId ? Promise.resolve([]) : getVehicleInsurances(),
        ])

        if (ignore) {
          return
        }

        setVehicle(vehicleData)
        setInsurance(insuranceData)

        const activeInsurance = findActiveVehicleInsurance(insuranceDataCollection, vehicleId)
        setPreviousActiveInsurance(activeInsurance)

        if (activeInsurance) {
          setIsCloseDialogOpen(true)
        }

        if (insuranceData) {
          setForm(insuranceToForm(insuranceData))
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger l’assurance.")
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
  }, [vehicleId, insuranceId])

  const canEdit = useMemo(
    () => isAdmin || vehicle?.user.id === user?.id,
    [isAdmin, vehicle?.user.id, user?.id]
  )

  function updateField(field: keyof InsuranceFormState, value: string) {
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
      const saved = insuranceId
        ? await updateVehicleInsurance(insuranceId, payload)
        : await createVehicleInsurance(payload)

      navigate(`/vehicles/${vehicleId}/insurances/${saved.id}`)
    } catch {
      setError("Impossible d’enregistrer l’assurance. Vérifiez les champs saisis.")
    } finally {
      setIsSaving(false)
    }
  }

  async function closePreviousInsurance() {
    if (!previousActiveInsurance) {
      return
    }

    setIsClosingPrevious(true)
    setError(null)

    try {
      const closedInsurance = await closeVehicleInsurance(previousActiveInsurance.id, closePreviousEndDate)
      setPreviousActiveInsurance(closedInsurance)
      setIsCloseDialogOpen(false)
    } catch {
      setError("Impossible de clôturer l’assurance active existante.")
    } finally {
      setIsClosingPrevious(false)
    }
  }

  if (isLoading) {
    return <div className="text-sm text-muted-foreground">Chargement de l’assurance...</div>
  }

  if (error && isEditing && !insurance) {
    return <ErrorMessage>{error}</ErrorMessage>
  }

  return (
    <div className="space-y-6">
      <ClosePreviousInsuranceDialog
        open={isCloseDialogOpen}
        insurance={previousActiveInsurance}
        endDate={closePreviousEndDate}
        isLoading={isClosingPrevious}
        onEndDateChange={setClosePreviousEndDate}
        onOpenChange={setIsCloseDialogOpen}
        onConfirm={closePreviousInsurance}
      />

      <VehicleEventHeader
        title={isEditing ? "Modifier l’assurance" : "Ajouter une assurance"}
        description={vehicleDescription(vehicle)}
        backTo={insuranceId ? `/vehicles/${vehicleId}/insurances/${insuranceId}` : `/vehicles/${vehicleId}/insurances`}
      />

      {!canEdit && (
        <WarningMessage>
          Seul le propriétaire ou un administrateur peut modifier cette assurance.
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
              label="Assureur"
              value={form.providerName}
              onChange={(value) => updateField("providerName", value)}
              required
              disabled={!canEdit}
            />
            <Field
              label="Numéro de police"
              value={form.policyNumber}
              onChange={(value) => updateField("policyNumber", value)}
              disabled={!canEdit}
            />
            <Field
              label="Début"
              type="date"
              value={form.startDate}
              onChange={(value) => updateField("startDate", value)}
              disabled={!canEdit}
            />
            <Field
              label="Fin"
              type="date"
              value={form.endDate}
              onChange={(value) => updateField("endDate", value)}
              disabled={!canEdit}
            />
            <NativeSelect
              label="Paiement"
              value={form.paymentFrequency}
              options={PAYMENT_FREQUENCIES}
              onChange={(event) => updateField("paymentFrequency", event.target.value)}
              required
              disabled={!canEdit}
            />
          </CardContent>
        </Card>

        <FormActions
          cancelTo={insuranceId ? `/vehicles/${vehicleId}/insurances/${insuranceId}` : `/vehicles/${vehicleId}/insurances`}
          canEdit={canEdit}
          isSaving={isSaving}
        />
      </form>
    </div>
  )
}

function insuranceToForm(insurance: VehicleInsuranceEvent): InsuranceFormState {
  return {
    providerName: insurance.providerName ?? "",
    policyNumber: insurance.policyNumber ?? "",
    startDate: insurance.startDate?.slice(0, 10) ?? "",
    endDate: insurance.endDate?.slice(0, 10) ?? "",
    paymentFrequency: insurance.paymentFrequency ?? "yearly",
  }
}

function formToPayload(form: InsuranceFormState, vehicleId: string): VehicleInsurancePayload {
  return {
    vehicle: `/api/vehicles/${vehicleId}`,
    providerName: form.providerName,
    policyNumber: form.policyNumber || null,
    startDate: form.startDate || null,
    endDate: form.endDate || null,
    paymentFrequency: form.paymentFrequency as VehicleInsurancePayload["paymentFrequency"],
  }
}

function findActiveVehicleInsurance(items: VehicleInsuranceEvent[], vehicleId: string) {
  return items
    .filter((item) => item.vehicle.id === Number(vehicleId) && !item.isDeleted && isInsuranceActive(item))
    .sort((a, b) => String(b.startDate ?? "").localeCompare(String(a.startDate ?? "")))[0] ?? null
}

function ClosePreviousInsuranceDialog({
  open,
  insurance,
  endDate,
  isLoading,
  onEndDateChange,
  onOpenChange,
  onConfirm,
}: {
  open: boolean
  insurance: VehicleInsuranceEvent | null
  endDate: string
  isLoading: boolean
  onEndDateChange: (value: string) => void
  onOpenChange: (open: boolean) => void
  onConfirm: () => void
}) {
  if (!insurance) {
    return null
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Clôturer l’assurance active ?</DialogTitle>
          <DialogDescription>
            Une assurance active existe déjà pour ce véhicule : {insurance.providerName}
            {insurance.startDate ? ` depuis le ${formatDate(insurance.startDate)}` : ""}.
          </DialogDescription>
        </DialogHeader>

        <Field
          label="Date de fin de l’assurance actuelle"
          type="date"
          value={endDate}
          onChange={onEndDateChange}
          disabled={isLoading}
        />

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={isLoading}>
            Ignorer
          </Button>
          <Button onClick={onConfirm} disabled={isLoading || !endDate}>
            {isLoading ? "Clôture..." : "Clôturer"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
