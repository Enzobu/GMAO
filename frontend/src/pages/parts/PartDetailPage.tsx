import { useEffect, useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { ArrowLeft, Pencil, Trash2 } from "lucide-react"

import { deletePart, getPart } from "@/api/parts"
import { DocumentsPanel } from "@/components/documents-panel"
import { DetailPagePlaceholder } from "@/components/loading-placeholders"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { useAuthStore } from "@/stores/auth-store"
import type { Part } from "@/types/part"
import {
  formatDateTime,
  partName,
  stockStatus,
  vehicleDisplayName,
} from "@/lib/part-utils"

const LOW_STOCK_BADGE_CLASS =
  "border-amber-500/30 bg-amber-500/10 text-amber-700 " +
  "dark:text-amber-300"
const DESTRUCTIVE_MESSAGE_CLASS =
  "rounded-lg border border-destructive/30 bg-destructive/10 p-4 " +
  "text-sm text-destructive"

export default function PartDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const isAdmin = useAuthStore(
    (state) => state.user?.roles.includes("ROLE_ADMIN") ?? false,
  )
  const [part, setPart] = useState<Part | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [isDeleteOpen, setIsDeleteOpen] = useState(false)
  const [isDeleting, setIsDeleting] = useState(false)

  useEffect(() => {
    let ignore = false

    async function load() {
      if (!id) return

      try {
        const data = await getPart(id)
        if (!ignore) setPart(data)
      } catch {
        if (!ignore) setError("Impossible de charger cette ligne de stock.")
      } finally {
        if (!ignore) setIsLoading(false)
      }
    }

    load()
    return () => {
      ignore = true
    }
  }, [id])

  async function confirmDelete() {
    if (!part) return
    setIsDeleting(true)
    try {
      await deletePart(part.id)
      navigate("/parts")
    } finally {
      setIsDeleting(false)
    }
  }

  if (isLoading) {
    return <DetailPagePlaceholder />
  }

  if (error || !part) {
    return (
      <div className={DESTRUCTIVE_MESSAGE_CLASS}>
        {error ?? "Stock introuvable."}
      </div>
    )
  }

  const status = stockStatus(part.quantity)

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={isDeleteOpen}
        title="Supprimer le stock ?"
        description={
          `${partName(part)} sera masqué de la plateforme. ` +
          "Aucune donnée ne sera supprimée définitivement."
        }
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => !isDeleting && setIsDeleteOpen(open)}
        onConfirm={confirmDelete}
      />

      <div
        className={
          "flex flex-col gap-4 lg:flex-row lg:items-start " +
          "lg:justify-between"
        }
      >
        <div className="space-y-2">
          <div className="text-sm text-muted-foreground">Stock</div>
          <h1 className="text-2xl font-semibold tracking-tight">
            {partName(part)}
          </h1>
          <div className="flex flex-wrap items-center gap-2">
            <Badge
              variant={status.variant}
              className={
                status.value === "low"
                  ? LOW_STOCK_BADGE_CLASS
                  : undefined
              }
            >
              {status.label}
            </Badge>
            {!isAdmin && (
              <Badge
                variant="outline"
                className={LOW_STOCK_BADGE_CLASS}
              >
                Lecture seule
              </Badge>
            )}
          </div>
        </div>

        <div className="flex flex-col gap-2 sm:flex-row">
          <Button variant="outline" asChild>
            <Link to="/parts">
              <ArrowLeft />
              Retour
            </Link>
          </Button>
          {isAdmin && (
            <Button asChild>
              <Link to={`/parts/${part.id}/edit`}>
                <Pencil />
                Modifier
              </Link>
            </Button>
          )}
          {isAdmin && (
            <Button
              variant="destructive"
              onClick={() => setIsDeleteOpen(true)}
            >
              <Trash2 />
              Supprimer
            </Button>
          )}
        </div>
      </div>

      <div className="grid gap-4 xl:grid-cols-[2fr_1fr]">
        <Card>
          <CardHeader>
            <CardTitle>Informations</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-3 md:grid-cols-2">
            <Metric label="Type de pièce" value={partName(part)} />
            <Metric label="Quantité" value={String(part.quantity)} />
            <div className="md:col-span-2">
              <Metric
                label="Note"
                value={part.note || "Aucune note renseignée."}
              />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Véhicules compatibles</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            {part.vehicles.length > 0 ? (
              part.vehicles.map((vehicle) => (
                <div key={vehicle.id} className="rounded-lg border p-3">
                  <div className="font-medium">
                    {vehicleDisplayName(vehicle)}
                  </div>
                  <div className="text-sm text-muted-foreground">
                    {vehicle.registration?.toUpperCase() ||
                      "Immatriculation non renseignée"}
                  </div>
                </div>
              ))
            ) : (
              <div className="text-sm text-muted-foreground">
                Aucun véhicule compatible renseigné.
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Traçabilité</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-2">
          <Metric label="Créé le" value={formatDateTime(part.createdAt)} />
          <Metric
            label="Mis à jour le"
            value={formatDateTime(part.updatedAt)}
          />
        </CardContent>
      </Card>

      <DocumentsPanel
        parent={{ type: "parts", id: part.id }}
        canManage={isAdmin}
        canDelete={isAdmin}
        emptyLabel="Aucun document disponible pour cette pièce."
      />
    </div>
  )
}

function Metric({ label, value }: Readonly<{ label: string; value: string }>) {
  return (
    <div className="rounded-lg border p-3">
      <div className="text-xs text-muted-foreground">{label}</div>
      <div className="mt-1 font-medium">{value}</div>
    </div>
  )
}
