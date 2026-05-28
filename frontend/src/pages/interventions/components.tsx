import type { ReactNode } from "react"
import { Link } from "react-router-dom"
import { Save } from "lucide-react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { DetailItem, EmptyCard, ErrorMessage, Field, PageHeader } from "@/components/page-primitives"
import type { Intervention } from "@/types/intervention"
import { formatDate, formatNumber, interventionStatusLabel, interventionStatusVariant } from "@/lib/intervention-utils"

export const InterventionHeader = PageHeader

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

export { EmptyCard, ErrorMessage, Field }
export const Detail = DetailItem

export function InterventionFormActions({ cancelTo, canEdit, isSaving }: { cancelTo: string; canEdit: boolean; isSaving: boolean }) {
  return (
    <div className="flex justify-end gap-2">
      <Button variant="outline" asChild>
        <Link to={cancelTo}>Annuler</Link>
      </Button>
      <Button type="submit" disabled={!canEdit || isSaving}>
        <Save />
        {isSaving ? "Enregistrement..." : "Enregistrer"}
      </Button>
    </div>
  )
}

export function MileageWarningDialog({
  open,
  message,
  isAdmin,
  isLoading,
  onOpenChange,
  onForce,
  forceLabel = "Forcer",
}: {
  open: boolean
  message: string
  isAdmin: boolean
  isLoading: boolean
  onOpenChange: (open: boolean) => void
  onForce: () => void
  forceLabel?: string
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
          {isAdmin && <Button onClick={onForce} disabled={isLoading}>{isLoading ? "Enregistrement..." : forceLabel}</Button>}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
