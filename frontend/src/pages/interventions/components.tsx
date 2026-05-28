import type { ComponentProps, ReactNode } from "react"
import { Link } from "react-router-dom"
import { ArrowLeft } from "lucide-react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import type { Intervention } from "@/types/intervention"
import { formatDate, formatNumber, interventionStatusLabel, interventionStatusVariant } from "@/lib/intervention-utils"

export function InterventionHeader({
  title,
  description,
  backTo,
  backLabel = "Retour",
  actions,
}: {
  title: string
  description?: string
  backTo?: string
  backLabel?: string
  actions?: ReactNode
}) {
  return (
    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
        {description && <p className="text-sm text-muted-foreground">{description}</p>}
      </div>
      <div className="flex flex-wrap gap-2">
        {backTo && (
          <Button variant="outline" asChild>
            <Link to={backTo}>
              <ArrowLeft />
              {backLabel}
            </Link>
          </Button>
        )}
        {actions}
      </div>
    </div>
  )
}

export function InterventionBadges({ intervention, readOnly = false }: { intervention: Intervention; readOnly?: boolean }) {
  return (
    <div className="flex flex-wrap gap-2">
      <Badge variant={interventionStatusVariant(intervention.status)}>{interventionStatusLabel(intervention.status)}</Badge>
      <Badge variant="outline">{intervention.maintenanceType?.name ?? "—"}</Badge>
      <Badge variant={intervention.isExternal ? "outline" : "secondary"}>{intervention.isExternal ? "Externe" : "Interne"}</Badge>
      {readOnly && (
        <Badge variant="outline" className="border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300">
          Lecture seule
        </Badge>
      )}
    </div>
  )
}

export function InterventionCard({
  intervention,
  to,
  actions,
  readOnly,
}: {
  intervention: Intervention
  to: string
  actions?: ReactNode
  readOnly?: boolean
}) {
  return (
    <Card className="relative border border-foreground/10 ring-0 transition-colors hover:border-primary/35 hover:bg-muted/30">
      <Link to={to} className="absolute inset-0 z-10 rounded-xl" />
      <CardContent className="space-y-4 p-5">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div className="space-y-2">
            <div className="text-lg font-semibold">{intervention.maintenanceType?.name ?? "Intervention"}</div>
            <InterventionBadges intervention={intervention} readOnly={readOnly} />
          </div>
          {actions && <div className="relative z-20 flex gap-2">{actions}</div>}
        </div>
        <div className="grid gap-3 text-sm md:grid-cols-4">
          <Detail label="Véhicule" value={intervention.vehicle?.registration?.toUpperCase() ?? "—"} />
          <Detail label="Kilométrage" value={`${formatNumber(intervention.mileage)} km`} />
          <Detail label="Début" value={formatDate(intervention.startedAt)} />
          <Detail label="Fin" value={formatDate(intervention.finishedAt)} />
        </div>
      </CardContent>
    </Card>
  )
}

export function Detail({ label, value, boxed = false }: { label: string; value: string; boxed?: boolean }) {
  return (
    <div className={boxed ? "rounded-lg border p-3" : undefined}>
      <div className="text-xs text-muted-foreground">{label}</div>
      <div className="mt-1 font-medium">{value}</div>
    </div>
  )
}

export function EmptyCard({ children }: { children: ReactNode }) {
  return (
    <Card>
      <CardContent className="py-8 text-sm text-muted-foreground">{children}</CardContent>
    </Card>
  )
}

export function ErrorMessage({ children }: { children: ReactNode }) {
  return <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{children}</div>
}

export function Field({
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
