import { useEffect, useState } from "react"
import type { ComponentProps, FormEvent } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { ArrowLeft, Save } from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { NativeSelect } from "@/components/ui/native-select"
import { LabelText } from "@/components/page-primitives"
import { createVehicle, getUsers, getVehicle, updateVehicle } from "@/api/vehicles"
import { useAuthStore } from "@/stores/auth-store"
import type { Vehicle, VehiclePayload, VehicleUser } from "@/types/vehicle"
import { MIN_INPUT_DATE, MAX_INPUT_DATE } from "@/lib/date-limits"
import { capitalizeFirstLetter } from "@/lib/text-format"
import {
  VEHICLE_COLORS,
  VEHICLE_FUEL_TYPES,
  VEHICLE_STATUSES,
  VEHICLE_TRANSMISSIONS,
  VEHICLE_TYPES,
} from "@/lib/vehicle-labels"

const emptyForm = {
  name: "",
  registration: "",
  brand: "",
  model: "",
  type: "",
  year: "",
  vin: "",
  engine: "",
  fuelType: "",
  transmission: "",
  lastMileage: "0",
  color: "gray",
  purchaseDate: "",
  purchasePrice: "",
  status: "active",
  userId: "",
}

const VIN_MAX_LENGTH = 17
const REGISTRATION_MAX_LENGTH = 9
const MIN_VEHICLE_YEAR = 1800
const MAX_VEHICLE_YEAR = 2100

type VehicleFormState = typeof emptyForm

export default function VehicleFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const currentUser = useAuthStore((state) => state.user)
  const isAdmin = currentUser?.roles.includes("ROLE_ADMIN") ?? false
  const isEditing = Boolean(id)

  const [form, setForm] = useState<VehicleFormState>(emptyForm)
  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [users, setUsers] = useState<VehicleUser[]>([])
  const [isLoading, setIsLoading] = useState(isEditing)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (error) {
      globalThis.scrollTo({ top: 0, behavior: "smooth" })
    }
  }, [error])

  useEffect(() => {
    let ignore = false

    async function load() {
      try {
        const [vehicleData, usersData] = await Promise.all([
          id ? getVehicle(id) : Promise.resolve(null),
          isAdmin ? getUsers() : Promise.resolve([]),
        ])

        if (ignore) {
          return
        }

        setUsers(usersData)

        if (vehicleData) {
          setVehicle(vehicleData)
          setForm(vehicleToForm(vehicleData))
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger le véhicule.")
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
  }, [id, isAdmin])

  const canEdit = !vehicle || isAdmin || vehicle.user.id === currentUser?.id
  const disableMileage = isEditing && !isAdmin

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!canEdit) {
      return
    }

    setIsSaving(true)
    setError(null)

    try {
      const payload = formToPayload(form, isAdmin)
      const saved = id ? await updateVehicle(id, payload) : await createVehicle(payload)

      navigate(`/vehicles/${saved.id}`)
    } catch {
      setError("Impossible d’enregistrer le véhicule. Vérifiez les champs saisis.")
    } finally {
      setIsSaving(false)
    }
  }

  function updateField(field: keyof VehicleFormState, value: string) {
    setForm((current) => ({ ...current, [field]: value }))
  }

  function updateRegistration(value: string) {
    updateField("registration", formatRegistration(value))
  }

  function updateVin(value: string) {
    updateField("vin", value.toUpperCase())
  }

  if (isLoading) {
    return <div className="text-sm text-muted-foreground">Chargement du véhicule...</div>
  }

  if (error && isEditing && !vehicle) {
    return <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{error}</div>
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">
            {isEditing ? "Modifier le véhicule" : "Ajouter un véhicule"}
          </h1>
          <p className="text-sm text-muted-foreground">
            {isEditing ? "Mettez à jour les informations du véhicule." : "Créez un nouveau véhicule dans le parc."}
          </p>
        </div>

        <Button variant="outline" asChild>
          <Link to={id ? `/vehicles/${id}` : "/vehicles"}>
            <ArrowLeft />
            Retour
          </Link>
        </Button>
      </div>

      {!canEdit && (
        <div className="rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-700 dark:text-amber-300">
          Vous pouvez consulter ce véhicule, mais seul son propriétaire ou un administrateur peut le modifier.
        </div>
      )}

      {error && <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{error}</div>}

      <form onSubmit={handleSubmit} className="space-y-4">
        <Card>
          <CardHeader>
            <CardTitle>Identité</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            <Field label="Nom" value={form.name} onChange={(value) => updateField("name", capitalizeFirstLetter(value))} required disabled={!canEdit} />
            <Field label="Immatriculation" value={form.registration} maxLength={REGISTRATION_MAX_LENGTH} onChange={updateRegistration} required disabled={!canEdit} />
            <Field label="Marque" value={form.brand} onChange={(value) => updateField("brand", capitalizeFirstLetter(value))} required disabled={!canEdit} />
            <Field label="Modèle" value={form.model} onChange={(value) => updateField("model", capitalizeFirstLetter(value))} required disabled={!canEdit} />
            <SelectField label="Type" value={form.type} options={VEHICLE_TYPES} onChange={(value) => updateField("type", value)} disabled={!canEdit} />
            <SelectField label="Statut" value={form.status} options={VEHICLE_STATUSES} onChange={(value) => updateField("status", value)} required disabled={!canEdit} />
            {isAdmin && (
              <SelectField
                label="Propriétaire"
                value={form.userId}
                options={users.map((user) => ({ value: String(user.id), label: userLabel(user) }))}
                onChange={(value) => updateField("userId", value)}
                required
                disabled={!canEdit}
              />
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Caractéristiques</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            <Field label="Année" type="number" min={MIN_VEHICLE_YEAR} max={MAX_VEHICLE_YEAR} value={form.year} onChange={(value) => updateField("year", value)} disabled={!canEdit} />
            <Field label="VIN" value={form.vin} maxLength={VIN_MAX_LENGTH} onChange={updateVin} disabled={!canEdit} />
            <Field label="Moteur" value={form.engine} onChange={(value) => updateField("engine", value)} disabled={!canEdit} />
            <SelectField label="Carburant" value={form.fuelType} options={VEHICLE_FUEL_TYPES} onChange={(value) => updateField("fuelType", value)} disabled={!canEdit} />
            <SelectField label="Transmission" value={form.transmission} options={VEHICLE_TRANSMISSIONS} onChange={(value) => updateField("transmission", value)} disabled={!canEdit} />
            <SelectField label="Couleur" value={form.color} options={VEHICLE_COLORS} onChange={(value) => updateField("color", value)} required disabled={!canEdit} />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Achat et suivi</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            <Field label="Kilométrage" type="number" min="0" value={form.lastMileage} onChange={(value) => updateField("lastMileage", value)} required disabled={!canEdit || disableMileage} />
            <Field label="Date d’achat" type="date" min={MIN_INPUT_DATE} max={MAX_INPUT_DATE} value={form.purchaseDate} onChange={(value) => updateField("purchaseDate", value)} disabled={!canEdit} />
            <Field label="Prix d’achat" type="number" min="0" step="0.01" value={form.purchasePrice} onChange={(value) => updateField("purchasePrice", value)} disabled={!canEdit} />
          </CardContent>
        </Card>

        <div className="flex justify-end gap-2">
          <Button variant="outline" asChild>
            <Link to={id ? `/vehicles/${id}` : "/vehicles"}>Annuler</Link>
          </Button>
          <Button type="submit" disabled={!canEdit || isSaving}>
            <Save />
            {isSaving ? "Enregistrement..." : "Enregistrer"}
          </Button>
        </div>
      </form>
    </div>
  )
}

function Field({ label, value, onChange, required, ...props }: Readonly<{ label: string; value: string; onChange: (value: string) => void } & Omit<ComponentProps<typeof Input>, "value" | "onChange">>) {
  return (
    <label className="grid gap-1.5 text-sm font-medium">
      <LabelText label={label} required={required} />
      <Input value={value} required={required} onChange={(event) => onChange(event.target.value)} {...props} />
    </label>
  )
}

function SelectField({ label, value, options, onChange, required = false, disabled = false }: Readonly<{ label: string; value: string; options: readonly { value: string; label: string }[]; onChange: (value: string) => void; required?: boolean; disabled?: boolean }>) {
  return <NativeSelect label={label} value={value} options={options} onChange={(event) => onChange(event.target.value)} required={required} disabled={disabled} placeholder={required ? undefined : "—"} />
}

function vehicleToForm(vehicle: Vehicle): VehicleFormState {
  return {
    name: capitalizeFirstLetter(vehicle.name),
    registration: vehicle.registration ?? "",
    brand: capitalizeFirstLetter(vehicle.brand),
    model: capitalizeFirstLetter(vehicle.model),
    type: vehicle.type ?? "",
    year: vehicle.year ? String(vehicle.year) : "",
    vin: vehicle.vin ?? "",
    engine: vehicle.engine ?? "",
    fuelType: vehicle.fuelType ?? "",
    transmission: vehicle.transmission ?? "",
    lastMileage: vehicle.lastMileage !== null && vehicle.lastMileage !== undefined ? String(vehicle.lastMileage) : "",
    color: vehicle.color ?? "gray",
    purchaseDate: vehicle.purchaseDate?.slice(0, 10) ?? "",
    purchasePrice: vehicle.purchasePrice ?? "",
    status: vehicle.status ?? "active",
    userId: vehicle.user ? String(vehicle.user.id) : "",
  }
}

function formToPayload(form: VehicleFormState, isAdmin: boolean): VehiclePayload {
  const payload: VehiclePayload = {
    name: form.name,
    registration: form.registration,
    brand: form.brand,
    model: form.model,
    type: form.type || null,
    year: form.year ? Number(form.year) : null,
    vin: form.vin || null,
    engine: form.engine || null,
    fuelType: form.fuelType || null,
    transmission: form.transmission || null,
    lastMileage: form.lastMileage ? Number(form.lastMileage) : null,
    color: form.color || null,
    purchaseDate: form.purchaseDate || null,
    purchasePrice: form.purchasePrice || null,
    status: form.status,
  }

  if (isAdmin && form.userId) {
    payload.user = `/api/users/${form.userId}`
  }

  return payload
}

function userLabel(user: VehicleUser) {
  const name = `${capitalizeFirstLetter(user.firstname)} ${capitalizeFirstLetter(user.lastname)}`.trim()

  return name ? `${name} - ${user.email}` : user.email
}

function formatRegistration(value: string) {
  const characters = value.toUpperCase().replaceAll(/[^A-Z0-9]/g, "")
  let result = ""
  const expectedPatterns = [/^[A-Z]$/, /^\d$/, /^[A-Z]$/]

  for (const character of characters) {
    const groupIndex = registrationGroupIndex(result.length)

    if (result.length < 7 && expectedPatterns[groupIndex].test(character)) {
      result += character
    }
  }

  return [result.slice(0, 2), result.slice(2, 5), result.slice(5, 7)].filter(Boolean).join("-")
}

function registrationGroupIndex(length: number) {
  if (length < 2) {
    return 0
  }

  return length < 5 ? 1 : 2
}
