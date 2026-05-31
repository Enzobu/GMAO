export const PAYMENT_FREQUENCIES = [
  { value: "monthly", label: "Mensuel" },
  { value: "yearly", label: "Annuel" },
] as const

export const INSPECTION_RESULTS = [
  { value: "pass", label: "Favorable", variant: "secondary" },
  { value: "counter_visit", label: "Contre-visite", variant: "outline" },
  { value: "fail", label: "Défavorable", variant: "destructive" },
] as const

export function optionLabel(options: readonly { value: string; label: string }[], value?: string | null) {
  return options.find((option) => option.value === value)?.label ?? "—"
}

export function inspectionResultVariant(value?: string | null) {
  const variant = INSPECTION_RESULTS.find((result) => result.value === value)?.variant

  if (variant === "destructive") {
    return "destructive"
  }

  if (variant === "outline") {
    return "outline"
  }

  return "secondary"
}

export function isInsuranceActive(insurance?: { active?: boolean; isActive?: boolean; endDate?: string | null } | null) {
  if (typeof insurance?.active === "boolean") {
    return insurance.active
  }

  if (typeof insurance?.isActive === "boolean") {
    return insurance.isActive
  }

  if (!insurance?.endDate) {
    return true
  }

  return new Date(insurance.endDate) > startOfToday()
}

export function todayInputValue() {
  return new Date().toISOString().slice(0, 10)
}

function startOfToday() {
  const date = new Date()
  date.setHours(0, 0, 0, 0)
  return date
}

export function formatDate(value?: string | null) {
  if (!value) return "—"
  return new Intl.DateTimeFormat("fr-FR").format(new Date(value))
}

export function formatDateTime(value?: string | null) {
  if (!value) return "—"
  return new Intl.DateTimeFormat("fr-FR", { dateStyle: "short", timeStyle: "short" }).format(new Date(value))
}
