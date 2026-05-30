import type { Intervention, InterventionStatus } from "@/types/intervention"
import type { Vehicle } from "@/types/vehicle"

export const INTERVENTION_STATUSES: { value: InterventionStatus; label: string; variant: "destructive" | "outline" | "secondary" }[] = [
  { value: "todo", label: "À faire", variant: "destructive" },
  { value: "in_progress", label: "En cours", variant: "outline" },
  { value: "completed", label: "Terminé", variant: "secondary" },
  { value: "cancelled", label: "Annulé", variant: "outline" },
]

export function interventionStatusLabel(value?: string | null) {
  return INTERVENTION_STATUSES.find((status) => status.value === value)?.label ?? "—"
}

export function interventionStatusVariant(value?: string | null) {
  return INTERVENTION_STATUSES.find((status) => status.value === value)?.variant ?? "outline"
}

export function formatDate(value?: string | null) {
  if (!value) return "—"
  return new Intl.DateTimeFormat("fr-FR").format(new Date(value))
}

export function formatDateTime(value?: string | null) {
  if (!value) return "—"
  return new Intl.DateTimeFormat("fr-FR", { dateStyle: "short", timeStyle: "short" }).format(new Date(value))
}

export function formatNumber(value?: number | null) {
  return value == null ? "—" : new Intl.NumberFormat("fr-FR").format(value)
}

export function vehicleDisplayName(vehicle: Vehicle) {
  return vehicle.name || `${vehicle.brand} ${vehicle.model}`.trim() || vehicle.registration
}

export function latestIntervention(interventions?: Intervention[]) {
  return interventions
    ?.filter((intervention) => !intervention.isDeleted)
    .sort((a, b) => String(b.finishedAt ?? b.startedAt ?? b.plannedAt ?? b.createdAt ?? "").localeCompare(String(a.finishedAt ?? a.startedAt ?? a.plannedAt ?? a.createdAt ?? "")))[0]
}

export function isPerformed(intervention: Intervention) {
  return Boolean(intervention.finishedAt)
}

export function nowInputValue() {
  const date = new Date()
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}
