import type { ComponentProps, ReactNode } from "react"
import { Link } from "react-router-dom"
import { ArrowLeft } from "lucide-react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import type { Vehicle } from "@/types/vehicle"

export function VehicleEventHeader({
  title,
  description,
  backTo,
  backLabel = "Retour",
  actions,
}: {
  title: string
  description?: string
  backTo: string
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
        <Button variant="outline" asChild>
          <Link to={backTo}>
            <ArrowLeft />
            {backLabel}
          </Link>
        </Button>
        {actions}
      </div>
    </div>
  )
}

export function ReadOnlyBadge() {
  return (
    <Badge
      variant="outline"
      className="border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300"
    >
      Lecture seule
    </Badge>
  )
}

export function WarningMessage({ children }: { children: ReactNode }) {
  return (
    <div className="rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-700 dark:text-amber-300">
      {children}
    </div>
  )
}

export function ErrorMessage({ children }: { children: ReactNode }) {
  return (
    <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
      {children}
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

export function DetailItem({ label, value, boxed = false }: { label: string; value: string; boxed?: boolean }) {
  return (
    <div className={boxed ? "rounded-lg border p-3" : undefined}>
      <div className="text-xs text-muted-foreground">{label}</div>
      <div className="mt-1 font-medium">{value}</div>
    </div>
  )
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

export function vehicleDescription(vehicle: Vehicle | null) {
  if (!vehicle) {
    return "Véhicule"
  }

  return `${vehicle.brand} ${vehicle.model} - ${vehicle.registration}`
}

export function canEditVehicle(vehicle: Vehicle | null, userId?: number, isAdmin = false) {
  return isAdmin || vehicle?.user.id === userId
}
